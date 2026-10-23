<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function loggedOut(Request $request)
    {
        return redirect('/login');
    }

    protected function credentials(Request $request): array
    {
        return [
            $this->username() => $request->input($this->username()),
            'password' => $request->input('password'),
            'ativo' => true,
            'convite_pendente' => false,
        ];
    }

    protected function authenticated(Request $request, $user)
    {
        $legacyPassword = $user->defaultPassword() !== null && Hash::check($user->defaultPassword(), $user->password);
        if ($legacyPassword || ($user->troca_senha_obrigatoria && $user->senha_temporaria_expira_em?->isPast())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => 'Esta senha expirou ou foi desativada. Solicite um link de recuperação.']);
        }
        $request->session()->put('auth_session_version', $user->versao_sessao);
        if ($user->troca_senha_obrigatoria) {
            return redirect()->route('account.password.edit');
        }
        if ($user->perfil === 'pairing') {
            return redirect()->route('pairing.index');
        }

        return null;
    }
}
