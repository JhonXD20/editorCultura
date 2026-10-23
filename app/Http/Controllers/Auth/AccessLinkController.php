<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccessLinkDelivery;
use App\Support\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AccessLinkController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            DB::transaction(function () use ($data): void {
                $user = User::where('email', $data['email'])->where('ativo', true)->lockForUpdate()->first();
                if ($user && ! Password::getRepository()->recentlyCreatedToken($user)) {
                    AccessLinkDelivery::send($user);
                    Audit::record('user.access_link_requested', $user);
                }
            });
        } catch (ValidationException) {
            // Keep account existence and transport details out of the public response and logs.
            Log::warning('Não foi possível entregar um link de acesso. Verifique a configuração de e-mail.');
        }
        $message = 'Se houver uma conta ativa com este e-mail, você receberá um link de acesso. Aguarde um minuto antes de solicitar outro.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }
}
