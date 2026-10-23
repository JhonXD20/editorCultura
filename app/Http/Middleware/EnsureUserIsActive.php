<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionVersion = $request->session()->get('auth_session_version');
        $revoked = $user && ($sessionVersion === null
            ? $user->versao_sessao > 1
            : (int) $sessionVersion !== $user->versao_sessao);
        $expired = $user?->troca_senha_obrigatoria && $user->senha_temporaria_expira_em?->isPast();
        if ($user && (! $user->ativo || $user->convite_pendente || $revoked || $expired)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Sessão revogada. Entre novamente.'], 401)
                : redirect()->route('login')->with('error', 'Sessão revogada. Entre novamente.');
        }

        if ($user && $sessionVersion === null) {
            // Compatibilidade com sessões abertas antes desta migração.
            $request->session()->put('auth_session_version', $user->versao_sessao);
        }

        if ($user?->troca_senha_obrigatoria && ! $request->routeIs('account.password.*', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Altere sua senha temporária para continuar.', 'redirect' => route('account.password.edit')], 403)
                : redirect()->route('account.password.edit');
        }

        return $next($request);
    }
}
