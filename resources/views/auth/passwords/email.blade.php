@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', 'Recuperar acesso | TotemSync')
@section('auth_header', 'Recuperar acesso')
@section('auth_body')
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    <p>Informe o e-mail da sua conta para receber um link e definir uma nova senha.</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label for="email">E-mail</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" autofocus>
            @error('email')<span class="text-danger" role="alert">{{ $message }}</span>@enderror
        </div>
        <button class="btn btn-primary btn-block" type="submit">Enviar link</button>
    </form>
@stop
@section('auth_footer')
    <a href="{{ route('login') }}">Voltar ao login</a>
@stop
