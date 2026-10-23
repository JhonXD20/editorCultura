@extends('layouts.template')
@section('page_title', 'Notificações - TotemSync')
@section('header_title', 'Notificações')
@section('header_subtitle', 'Acompanhe os envios e os retornos das revisões.')
@section('conteudo')
@forelse($notifications as $notification)
<div class="card card-outline card-{{ $notification->read_at ? 'secondary' : 'primary' }}"><div class="card-body">
    <strong>{{ $notification->data['pagina_titulo'] ?? 'Conteúdo' }}</strong>
    <p>{{ ['submitted' => 'Enviado para revisão', 'approved' => 'Aprovado e publicado', 'changes_requested' => 'Devolvido para ajustes'][$notification->data['event'] ?? ''] ?? 'Atualização do conteúdo' }}</p>
    @if($notification->data['comment'] ?? null)<p>{{ $notification->data['comment'] }}</p>@endif
    <small>{{ $notification->created_at->format('d/m/Y H:i') }}</small>
    @if($notification->data['pagina_id'] ?? null)<a class="btn btn-sm btn-outline-primary" href="{{ route('gestao-conteudo.edit', $notification->data['pagina_id']) }}">Abrir conteúdo</a>@endif
    @unless($notification->read_at)<form class="d-inline" method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="btn btn-sm btn-default">Marcar como lida</button></form>@endunless
</div></div>
@empty<div class="alert alert-info">Você ainda não possui notificações.</div>@endforelse
{{ $notifications->links() }}
@stop
