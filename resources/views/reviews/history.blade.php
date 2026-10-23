@extends('layouts.template')
@section('page_title', 'Histórico de revisões - TotemSync')
@section('header_title', 'Histórico de revisões')
@section('header_subtitle', $pagina->pagina_titulo)
@section('conteudo')
@php($revisionLabels = ['draft' => 'Rascunho', 'pending_review' => 'Aguardando aprovação', 'published' => 'Publicada', 'changes_requested' => 'Devolvida para ajustes'])
<div class="card"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Versão</th><th>Situação</th><th>Criada</th><th>Observação</th><th>Ação</th></tr></thead><tbody>
@forelse($revisions as $revision)<tr><td>#{{ $revision->versao }}</td><td>{{ $revisionLabels[$revision->estado] ?? $revision->estado }}</td><td>{{ $revision->created_at->format('d/m/Y H:i') }}</td><td>{{ $revision->observacao }}</td><td>@can('publish-content')<form method="POST" action="{{ route('reviews.restore', [$pagina, $revision]) }}">@csrf<button class="btn btn-sm btn-outline-primary">Restaurar como rascunho</button></form>@endcan</td></tr>@empty<tr><td colspan="5">Nenhuma revisão salva manualmente.</td></tr>@endforelse
</tbody></table>{{ $revisions->links() }}<a href="{{ route('gestao-conteudo.edit', $pagina) }}" class="btn btn-default">Voltar ao editor</a></div></div>
@stop
