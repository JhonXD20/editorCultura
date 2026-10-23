<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateDevice;
use App\Models\DeviceCredential;
use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\PairingCode;
use App\Models\Totem;
use App\Support\Audit;
use App\Support\EventTime;
use App\Support\PlayerManifest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    public function pairingIndex(): View
    {
        $this->authorize('pair-device');

        return view('pairing.index', ['totens' => Totem::orderBy('totem_nome_identificacao')->get()]);
    }

    public function pairWithAccount(Request $request): RedirectResponse
    {
        $this->authorize('pair-device');
        $validated = $request->validate(['totem_id' => ['required', 'integer', 'exists:totens,id']]);
        $totem = Totem::findOrFail($validated['totem_id']);
        $home = $totem->paginaInicialDisponivel();
        abort_if($home === null, 422, 'Publique uma página inicial antes de parear.');
        $token = Str::random(80);
        $credential = DeviceCredential::create([
            'totem_id' => $totem->id, 'hash_token' => hash('sha256', $token), 'ultimo_contato_em' => now(),
        ]);
        Audit::record('device.paired_with_account', $credential);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->pairedResponse($home, $token);
    }

    public function pairingForm(string $code): View
    {
        return view('device-pair', ['code' => $code]);
    }

    public function pair(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $pairing = PairingCode::where('hash_codigo', hash('sha256', $validated['code']))
            ->whereNull('usado_em')
            ->where('expira_em', '>', now())
            ->first();

        if (! $pairing) {
            return back()->withErrors(['code' => 'Código inválido, expirado ou já utilizado.']);
        }

        [$credential, $plainToken] = DB::transaction(function () use ($pairing): array {
            $locked = PairingCode::whereKey($pairing->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->usado_em || $locked->expira_em->isPast(), 422, 'Código de pareamento indisponível.');
            abort_if(Totem::findOrFail($locked->totem_id)->paginaInicialDisponivel() === null, 422, 'Publique uma página inicial antes de parear.');
            $token = Str::random(80);
            $credential = DeviceCredential::create([
                'totem_id' => $locked->totem_id,
                'hash_token' => hash('sha256', $token),
                'ultimo_contato_em' => now(),
            ]);
            $locked->update(['usado_em' => now()]);
            Audit::record('device.paired', $credential, ['totem_id' => $locked->totem_id]);

            return [$credential, $token];
        });

        $home = $credential->totem->paginaInicialDisponivel();
        if ($home === null) {
            abort(422, 'O totem não possui página inicial ativa.');
        }

        return $this->pairedResponse($home, $plainToken);
    }

    private function pairedResponse(Pagina $home, string $plainToken): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return redirect()->route('totem.exibir', $home)->header('Clear-Site-Data', '"cache", "storage"')->withCookie(cookie(
            AuthenticateDevice::COOKIE,
            $plainToken,
            60 * 24 * 365 * 5,
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'Strict'
        ));
    }

    public function manifest(Request $request): JsonResponse
    {
        /** @var Totem $totem */
        $totem = $request->attributes->get('totem');

        return response()->json(PlayerManifest::make($totem))->header('Cache-Control', 'no-store');
    }

    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Totem $totem */
        $totem = $request->attributes->get('totem');
        Totem::withoutTimestamps(fn () => $totem->update(['totem_ping' => now()]));

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'reload_requested_at' => $totem->totem_recarga_solicitada_em?->toIso8601String(),
        ]);
    }

    public function telemetry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.uuid' => ['required', 'uuid'],
            'events.*.pagina_id' => ['required', 'integer', 'min:1'],
            'events.*.tipo' => ['required', Rule::in(['toque', 'tempo_pagina'])],
            'events.*.duracao_segundos' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'events.*.occurred_at' => ['nullable', 'string', 'max:40', 'date'],
        ]);
        /** @var Totem $totem */
        $totem = $request->attributes->get('totem');
        $accepted = 0;
        // A page can have been disabled while a device was offline.
        $publishedPageIds = Pagina::whereNotNull('pagina_publicada_em')->pluck('id')->all();
        $acknowledged = [];
        $rejected = [];

        foreach ($validated['events'] as $event) {
            if ($event['tipo'] === 'tempo_pagina' && empty($event['duracao_segundos'])) {
                throw ValidationException::withMessages(['events' => 'Eventos de permanência exigem duração.']);
            }
        }
        foreach ($validated['events'] as $event) {
            $existing = InteracaoTotem::where('evento_uuid', $event['uuid'])->first();
            if ($existing) {
                if ((int) $existing->totem_id === $totem->id) {
                    $acknowledged[] = $event['uuid'];
                } else {
                    $rejected[] = ['uuid' => $event['uuid'], 'reason' => 'uuid_conflict', 'retryable' => false];
                }

                continue;
            }
            if (! in_array($event['pagina_id'], $publishedPageIds, true)) {
                $rejected[] = ['uuid' => $event['uuid'], 'reason' => 'page_unavailable', 'retryable' => false];

                continue;
            }
            [$occurred, $trusted] = EventTime::resolve($event['occurred_at'] ?? null);
            $inserted = InteracaoTotem::query()->insertOrIgnore([
                'evento_uuid' => $event['uuid'],
                'totem_id' => $totem->id,
                'pagina_id' => $event['pagina_id'],
                'tipo' => $event['tipo'],
                'duracao_segundos' => $event['tipo'] === 'tempo_pagina' ? ($event['duracao_segundos'] ?? null) : null,
                'created_at' => now(),
                'updated_at' => now(),
                'ocorrido_em' => $occurred,
                'horario_validado' => $trusted,
            ]);
            $accepted += $inserted;
            // Only acknowledge a durable event owned by this device, including a concurrent retry.
            if ($inserted || InteracaoTotem::where('evento_uuid', $event['uuid'])->where('totem_id', $totem->id)->exists()) {
                $acknowledged[] = $event['uuid'];
            } else {
                $rejected[] = ['uuid' => $event['uuid'], 'reason' => 'uuid_conflict', 'retryable' => false];
            }
        }

        return response()->json(compact('accepted', 'acknowledged', 'rejected'));
    }
}
