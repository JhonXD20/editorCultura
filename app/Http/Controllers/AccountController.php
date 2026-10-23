<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.profile');
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'current_password' => ['required', 'current_password'],
        ]);
        DB::transaction(function () use ($user, $data): void {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($data['current_password'], $locked->password) || $locked->versao_sessao !== $user->versao_sessao) {
                throw ValidationException::withMessages(['current_password' => 'A conta foi alterada. Entre novamente antes de continuar.']);
            }
            $locked->fill(collect($data)->only(['name', 'email'])->all());
            if ($locked->isDirty('email')) {
                DB::table(config('auth.passwords.users.table'))->where('email', $locked->getOriginal('email'))->delete();
                $locked->email_verified_at = null;
            }
            $locked->save();
            Audit::record('user.profile_updated', $locked);
        });

        return $request->expectsJson()
            ? response()->json(['message' => 'Perfil atualizado.'])
            : back()->with('success', 'Perfil atualizado.');
    }

    public function password(): View
    {
        return view('account.password');
    }

    public function changePassword(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
        ]);
        $user = DB::transaction(function () use ($request, $data): User {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'A senha atual foi alterada. Entre novamente.']);
            }
            if ($data['password'] === $user->defaultPassword()) {
                throw ValidationException::withMessages(['password' => 'A nova senha não pode ser sua data de nascimento.']);
            }
            $user->forceFill([
                'password' => $data['password'], 'troca_senha_obrigatoria' => false,
                'senha_temporaria_expira_em' => null,
                'versao_sessao' => $user->versao_sessao + 1, 'remember_token' => null,
            ])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Password::deleteToken($user);
            Audit::record('user.password_changed', $user);

            return $user;
        });
        $request->session()->regenerate();
        $request->session()->put('auth_session_version', $user->versao_sessao);
        Auth::setUser($user);
        $url = route($user->perfil === 'pairing' ? 'pairing.index' : 'admin');

        return $request->expectsJson()
            ? response()->json(['message' => 'Senha alterada. As outras sessões foram encerradas.', 'redirect' => $url])
            : redirect($url)->with('success', 'Senha alterada com sucesso.');
    }
}
