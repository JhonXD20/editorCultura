@extends('layouts.template')
@section('page_title', 'Meu perfil - TotemSync')
@section('header_title', 'Meu perfil')
@section('conteudo')
<div class="card card-outline card-primary">
    <form method="POST" action="{{ route('account.update') }}" data-async-json data-confirm="Confirma as alterações no seu perfil?" data-confirm-title="Salvar perfil?">
        @csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label for="profile-name">Nome</label><input id="profile-name" class="form-control" name="name" required maxlength="120" value="{{ auth()->user()->name }}"></div>
            <div class="form-group"><label for="profile-email">E-mail</label><input id="profile-email" class="form-control" type="email" name="email" required value="{{ auth()->user()->email }}"></div>
            <div class="form-group"><label for="profile-password">Senha atual</label><input id="profile-password" class="form-control" type="password" name="current_password" required autocomplete="current-password"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary" data-requires-js disabled>Salvar perfil</button> <a href="{{ route('account.password.edit') }}" class="btn btn-outline-secondary">Alterar senha</a></div>
    </form>
</div>
@stop
