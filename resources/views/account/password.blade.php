@extends('layouts.template')
@section('page_title', 'Alterar senha - TotemSync')
@section('header_title', 'Alterar senha')
@section('conteudo')
@if(auth()->user()->troca_senha_obrigatoria)
    <div class="alert alert-warning" role="alert">Esta conta exige uma nova senha para continuar. Informe sua senha atual ou <a href="{{ route('password.request') }}">solicite um link de recuperação por e-mail</a>.</div>
@endif
<div class="card card-outline card-primary">
    <form method="POST" action="{{ route('account.password.update') }}" data-async-json>
        @csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label for="current-password">Senha atual</label><input id="current-password" class="form-control" type="password" name="current_password" required autocomplete="current-password"></div>
            <div class="form-group"><label for="new-password">Nova senha</label><input id="new-password" class="form-control" type="password" name="password" minlength="8" maxlength="128" required autocomplete="new-password"><small class="form-text text-muted">Use pelo menos 8 caracteres. A senha não pode ser a data de nascimento nem repetir a senha atual.</small></div>
            <div class="form-group"><label for="password-confirmation">Confirmar nova senha</label><input id="password-confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Alterar senha</button></div>
    </form>
</div>
@stop
