@extends('layouts.template')

@section('page_title', 'Usuários - TotemSync')
@section('header_title', 'Usuários e permissões')
@section('header_subtitle', 'Contas administrativas, editoriais e de pareamento.')

@section('conteudo')
<div class="row">
    @unless($emailDeliveryConfigured)
        <div class="col-12"><div class="alert alert-warning" role="alert">O envio de e-mail ainda não está configurado. Configure o SMTP para criar contas ou resetar senhas.</div></div>
    @endunless
    <div class="col-lg-4"><div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Novo usuário</h3></div>
        <form method="POST" action="{{ route('usuarios.store') }}" data-async-json>@csrf
            <div class="card-body">
                <div class="form-group"><label for="new-user-name">Nome</label><input id="new-user-name" class="form-control" name="name" required maxlength="120" value="{{ old('name') }}"></div>
                <div class="form-group"><label for="new-user-email">E-mail</label><input id="new-user-email" class="form-control" type="email" name="email" required value="{{ old('email') }}"></div>
                <div class="form-group"><label for="new-user-role">Papel</label><select id="new-user-role" class="form-control" name="perfil"><option value="editor">Editor</option><option value="admin">Administrador</option><option value="pairing">Pareamento</option></select></div>
                <p class="text-muted">O usuário receberá um convite por e-mail para definir a própria senha. O link vence em 30 minutos. A conta ficará pendente até a conclusão.</p>
            </div>
            <div class="card-footer"><button class="btn btn-primary" type="submit">Criar usuário</button></div>
        </form>
    </div></div>
    <div class="col-lg-8">
        @foreach($users as $user)
        <div class="card"><div class="card-body" data-user-email="{{ $user->email }}">
            <strong>{{ $user->name }}</strong> · {{ $user->perfil }} · {{ $user->ativo ? 'Ativo' : 'Inativo' }}
            @if($user->convite_pendente)<span class="badge badge-warning">Convite pendente</span>@endif
            <p class="text-muted">{{ $user->email }} · Último acesso: {{ $user->ultimo_acesso_em?->format('d/m/Y H:i') ?? 'Nunca' }}</p>
            <details><summary>Editar conta</summary>
                <form class="mt-3" method="POST" action="{{ route('usuarios.update', $user) }}" data-async-json data-confirm="Confirma as alterações nesta conta? As sessões do usuário serão encerradas." data-confirm-title="Salvar conta?">@csrf @method('PUT')
                    <div class="form-group"><label for="name-{{ $user->id }}">Nome</label><input class="form-control" id="name-{{ $user->id }}" name="name" value="{{ $user->name }}" required maxlength="120"></div>
                    <div class="form-group"><label for="email-{{ $user->id }}">E-mail</label><input class="form-control" id="email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required></div>
                    <div class="form-group"><label for="role-{{ $user->id }}">Papel</label><select class="form-control" id="role-{{ $user->id }}" name="perfil">@foreach(['admin' => 'Administrador', 'editor' => 'Editor', 'pairing' => 'Pareamento'] as $value => $label)<option value="{{ $value }}" @selected($user->perfil === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="active-{{ $user->id }}">Estado</label><select class="form-control" id="active-{{ $user->id }}" name="ativo"><option value="1" @selected($user->ativo)>Ativo</option><option value="0" @selected(!$user->ativo)>Inativo</option></select></div>
                    <div class="form-group"><label for="birth-{{ $user->id }}">Data de nascimento</label><input class="form-control" id="birth-{{ $user->id }}" name="data_nascimento" type="date" value="{{ $user->data_nascimento?->format('Y-m-d') }}"><small class="text-muted">Alterar esta data não altera a senha. Envie um link de recuperação quando necessário.</small></div>
                    <button class="btn btn-primary" type="submit" data-requires-js disabled>Salvar conta</button>
                </form>
            </details>
            <form method="POST" action="{{ route('usuarios.revoke-sessions', $user) }}" class="mt-3" data-async-json data-confirm="Todas as sessões deste usuário serão encerradas imediatamente, inclusive esta se for sua própria conta. Confirme a identidade antes de continuar." data-confirm-title="Revogar todas as sessões?" data-confirm-label="Revogar sessões" data-confirm-email="{{ $user->email }}">@csrf<button class="btn btn-sm btn-outline-danger" type="submit" data-requires-js disabled>Revogar todas as sessões</button></form>
            <form method="POST" action="{{ route('usuarios.reset-password', $user) }}" class="mt-2" data-async-json data-confirm="Um link de uso único será enviado por e-mail e vencerá em 30 minutos. A senha e as sessões atuais só mudarão quando o usuário definir a nova senha. Para bloquear o acesso imediatamente, revogue as sessões ou desative a conta." data-confirm-title="Enviar link de acesso?" data-confirm-label="Enviar link" data-confirm-email="{{ $user->email }}">@csrf<button class="btn btn-sm btn-outline-warning" type="submit" data-requires-js disabled>{{ $user->convite_pendente ? 'Reenviar convite' : 'Enviar link' }}</button></form>
        </div></div>
        @endforeach
    </div>
</div>
@stop
