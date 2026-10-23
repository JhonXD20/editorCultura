<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccessLinkDelivery;
use App\Support\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('administer');

        return view('users.index', [
            'users' => User::orderBy('name')->get(),
            'emailDeliveryConfigured' => AccessLinkDelivery::isConfigured(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('administer');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'data_nascimento' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'perfil' => ['required', Rule::in(['admin', 'editor', 'pairing'])],
        ]);
        AccessLinkDelivery::assertConfigured();
        DB::transaction(function () use ($validated): void {
            $user = new User($validated + ['ativo' => true]);
            $user->forceFill([
                'password' => Str::random(64), 'convite_pendente' => true,
                'troca_senha_obrigatoria' => false, 'senha_temporaria_expira_em' => null,
            ])->save();
            Audit::record('user.created', $user, ['perfil' => $user->perfil]);
            AccessLinkDelivery::send($user);
        });

        return $this->success($request, 'Usuário criado. O convite para definir a senha foi enviado por e-mail e vence em 30 minutos.');
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorize('administer');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'perfil' => ['required', Rule::in(['admin', 'editor', 'pairing'])],
            'ativo' => ['required', 'boolean'],
            'data_nascimento' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
        ]);
        if ($user->is(auth()->user()) && ! $validated['ativo']) {
            throw ValidationException::withMessages(['ativo' => 'Você não pode desativar a própria conta.']);
        }
        DB::transaction(function () use ($user, $validated): void {
            // Lock in stable order so two administrators cannot simultaneously remove each other.
            $admins = User::where('perfil', 'admin')->where('ativo', true)->where('convite_pendente', false)->orderBy('id')->lockForUpdate()->get();
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->perfil === 'admin' && $locked->ativo && ! $locked->convite_pendente
                && ($validated['perfil'] !== 'admin' || ! $validated['ativo']) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['perfil' => 'O último administrador ativo não pode ser removido.']);
            }
            $locked->fill($validated);
            if ($locked->isDirty('email')) {
                DB::table(config('auth.passwords.users.table'))->where('email', $locked->getOriginal('email'))->delete();
                $locked->email_verified_at = null;
            }
            if (! $locked->ativo) {
                Password::deleteToken($locked);
            }
            $locked->versao_sessao++;
            $locked->forceFill(['remember_token' => null])->save();
            DB::table('sessions')->where('user_id', $locked->id)->delete();
            Audit::record('user.updated', $locked, ['perfil' => $locked->perfil, 'active' => $locked->ativo]);
        }, 3);

        return $this->success($request, 'Usuário atualizado.', $user);
    }

    public function revokeSessions(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorize('administer');
        $this->validateCriticalAction($request, $user);
        DB::transaction(function () use ($user): void {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['versao_sessao' => $locked->versao_sessao + 1, 'remember_token' => null])->save();
            DB::table('sessions')->where('user_id', $locked->id)->delete();
            Audit::record('user.sessions_revoked', $locked);
        });

        return $this->success($request, 'Sessões do usuário revogadas.', $user);
    }

    public function resetPassword(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorize('administer');
        $this->validateCriticalAction($request, $user);
        AccessLinkDelivery::assertConfigured();
        DB::transaction(function () use ($user): void {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $locked->ativo) {
                throw ValidationException::withMessages(['email' => 'Ative a conta antes de enviar um link de acesso.']);
            }
            if (Password::getRepository()->recentlyCreatedToken($locked)) {
                throw ValidationException::withMessages(['email' => 'Aguarde um minuto antes de reenviar o link.']);
            }
            AccessLinkDelivery::send($locked);
            Audit::record('user.access_link_requested', $locked);
        });

        return $this->success($request, 'Link enviado por e-mail, válido por 30 minutos. A senha e as sessões atuais permanecem válidas até a definição da nova senha.');
    }

    private function validateCriticalAction(Request $request, User $user): void
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirmation_email' => ['required', 'string', Rule::in([$user->email])],
        ]);
    }

    private function success(Request $request, string $message, ?User $target = null): JsonResponse|RedirectResponse
    {
        $result = ['message' => $message];
        if ($target?->is($request->user())) {
            Auth::setUser($target->fresh());
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $result['redirect'] = route('login');
            $result['message'] .= ' Entre novamente.';
        }

        if ($request->expectsJson()) {
            return response()->json($result)->header('Cache-Control', 'no-store, private');
        }

        return redirect($result['redirect'] ?? route('usuarios.index'))->with('success', $message);
    }
}
