@extends('layouts.template')
@section('page_title', 'Revisões - TotemSync')
@section('header_title', 'Revisão de conteúdo')
@section('header_subtitle', 'Confira a versão enviada pelo editor e decida quando ela pode ser publicada.')
@section('conteudo')
@php
    $statusLabels = ['pending' => 'Aguardando aprovação', 'approved' => 'Aprovado e publicado', 'changes_requested' => 'Devolvido para ajustes'];
    $statusColors = ['pending' => 'warning', 'approved' => 'success', 'changes_requested' => 'secondary'];
@endphp
<div class="alert alert-light border mb-3">
    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
    A prévia mostra a versão exata enviada. Ao aprovar, essa versão passa a ser exibida nos totens.
    Ao devolver, informe o que o editor precisa ajustar.
</div>
<div class="card card-outline card-primary">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th scope="col">Página</th><th scope="col">Revisão</th><th scope="col">Situação</th><th scope="col">Enviada</th><th scope="col">Ações e orientações</th></tr></thead>
            <tbody>
            @forelse($reviews as $review)
                <tr>
                    <td>{{ $review->pagina->pagina_titulo }}</td>
                    <td>#{{ $review->revision->versao }}</td>
                    <td><span class="badge badge-{{ $statusColors[$review->situacao] ?? 'secondary' }}">{{ $statusLabels[$review->situacao] ?? $review->situacao }}</span></td>
                    <td class="text-nowrap">{{ $review->enviado_em->format('d/m/Y H:i') }}</td>
                    <td style="min-width:280px">
                        <a class="btn btn-sm btn-outline-primary mb-2" target="_blank" rel="noopener" href="{{ route('reviews.preview', $review) }}">Visualizar versão enviada</a>
                        @if($review->situacao === 'pending')
                            <form class="d-inline" method="POST" action="{{ route('reviews.approve', $review) }}">
                                @csrf
                                <button class="btn btn-sm btn-success mb-2">Aprovar e publicar</button>
                            </form>
                            <form method="POST" action="{{ route('reviews.reject', $review) }}">
                                @csrf
                                <label class="small mb-1" for="review-comment-{{ $review->id }}">Orientações para devolver ao editor</label>
                                <div class="input-group input-group-sm">
                                    <input class="form-control" id="review-comment-{{ $review->id }}" name="comment" maxlength="2000" required placeholder="Descreva os ajustes necessários">
                                    <div class="input-group-append"><button class="btn btn-warning">Devolver</button></div>
                                </div>
                            </form>
                        @elseif($review->comentario_revisao)
                            <p class="small mb-0">{{ $review->comentario_revisao }}</p>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">Nenhuma revisão encontrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $reviews->links() }}</div>
</div>
@stop
