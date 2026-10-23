<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AccessPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.passwords.reset', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ]);
        $user = DB::transaction(function () use ($data): User {
            // Issuance and consumption lock the same user first; only one concurrent redemption can succeed.
            $user = User::where('email', $data['email'])->lockForUpdate()->first();
            DB::table(config('auth.passwords.users.table'))->where('email', $data['email'])->lockForUpdate()->first();
            if (! $user || ! $user->ativo || ! Password::tokenExists($user, $data['token'])) {
                throw ValidationException::withMessages(['email' => 'Link inválido ou expirado. Solicite um novo link de acesso.']);
            }
            if ($data['password'] === $user->defaultPassword()) {
                throw ValidationException::withMessages(['password' => 'A nova senha não pode ser sua data de nascimento.']);
            }
            $invitation = $user->convite_pendente;
            $user->forceFill([
                'password' => $data['password'], 'convite_pendente' => false,
                'troca_senha_obrigatoria' => false, 'senha_temporaria_expira_em' => null,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'versao_sessao' => $user->versao_sessao + 1, 'remember_token' => null,
            ])->save();
            Password::deleteToken($user);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Audit::record($invitation ? 'user.invitation_accepted' : 'user.password_recovered', $user);

            return $user;
        });
        event(new PasswordReset($user));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $message = 'Senha definida com sucesso. Entre com seu e-mail e a nova senha.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'redirect' => route('login')])
            : redirect()->route('login')->with('status', $message);
    }
}
