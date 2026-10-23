@extends('layouts.template')
@section('page_title', 'Mídias - TotemSync')
@section('header_title', 'Biblioteca de mídia')
@section('header_subtitle', 'Imagens e vídeos armazenados com validação e rastreabilidade.')
@section('conteudo')
<div class="card card-outline card-primary">
    <form method="POST" enctype="multipart/form-data" action="{{ route('midias.store') }}" data-async-json>
        @csrf
        <div class="card-body form-inline">
            <input class="form-control-file mr-3" type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" required aria-label="Arquivo de mídia">
            <button class="btn btn-primary">Enviar arquivo</button>
        </div>
    </form>
</div>
<div class="row" data-media-library>
    @forelse($media as $item)
        <div class="col-md-4 col-lg-3 mb-4" data-media-card="{{ $item->id }}">
            <div class="card h-100">
                @if($item->tipo === 'image')
                    <img class="card-img-top" style="height:180px;object-fit:contain"
                         src="{{ Storage::disk($item->disco)->url($item->caminho) }}"
                         alt="{{ $item->nome_original }}" loading="lazy" decoding="async">
                @else
                    <video class="card-img-top" style="height:180px" controls preload="metadata"
                           src="{{ Storage::disk($item->disco)->url($item->caminho) }}"></video>
                @endif
                <div class="card-body text-break">
                    <strong>{{ $item->nome_original }}</strong><br>
                    <small>{{ number_format($item->tamanho / 1024 / 1024, 2, ',', '.') }} MB · {{ $item->paginas_count }} referência(s)</small>
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('midias.destroy', $item) }}" data-async-json data-remove-card
                          data-confirm="Tem certeza de que deseja excluir este arquivo? Essa ação não pode ser desfeita."
                          data-confirm-title="Excluir mídia?" data-confirm-label="Excluir arquivo">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" data-requires-js @disabled(true) data-unavailable="{{ $item->paginas_count ? '1' : '0' }}">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">A biblioteca ainda está vazia.</div></div>
    @endforelse
</div>
{{ $media->links() }}
@stop
