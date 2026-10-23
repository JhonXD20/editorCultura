@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', 'Definir senha | TotemSync')
@section('auth_header', 'Defina sua senha')
@section('auth_body')
    <p>Use o link recebido por e-mail para escolher sua senha. Ele vale por 30 minutos e só pode ser usado uma vez.</p>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-group">
            <label for="email">E-mail</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" maxlength="255">
            @error('email')<span class="text-danger" role="alert">{{ $message }}</span>@enderror
            @error('token')<span class="text-danger" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="password">Nova senha</label>
            <input id="password" class="form-control" type="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password" aria-describedby="password-help">
            <small id="password-help">Use pelo menos 8 caracteres.</small>
            @error('password')<span class="text-danger" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirmar nova senha</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required minlength="8" maxlength="128" autocomplete="new-password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Definir senha</button>
    </form>
@stop
@section('auth_footer')
    <p><a href="{{ route('password.request') }}">Solicitar novo link</a></p>
    <a href="{{ route('login') }}">Voltar ao login</a>
@stop
