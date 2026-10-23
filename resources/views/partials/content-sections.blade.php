<section class="dashboard-card content-sections" aria-labelledby="sectionsTitle">
    <h2 id="sectionsTitle">Seções e sequência de leitura</h2>
    <p>Organize as páginas de cada assunto. No totem, o visitante toca à esquerda ou à direita para navegar.</p>
    @can('administer')
        <form method="POST" action="{{ route('secoes.store') }}" class="content-section-create content-section-create--new">
            @csrf
            <label for="sectionName">Nova seção</label>
            <input id="sectionName" class="form-control" name="nome" maxlength="100" required placeholder="Ex.: História de Porto Nacional">
            <button class="btn btn-primary" type="submit">Criar seção</button>
        </form>
        <small>A organização salva é aplicada à navegação publicada. Rascunhos e páginas inativas são ignorados pelo totem.</small>
    @endcan
    @foreach ($secoes as $secao)
        <details class="content-section" data-section-id="{{ $secao->id }}">
            <summary>{{ $secao->nome }} <span>({{ $secao->paginas->count() }} páginas)</span></summary>
            @can('administer')
                <form method="POST" action="{{ route('secoes.update', $secao) }}" class="content-section-create">
                    @csrf @method('PUT')
                    <input type="hidden" name="versao" value="{{ $secao->versao }}">
                    <label class="sr-only" for="section-name-{{ $secao->id }}">Nome da seção</label>
                    <input class="form-control" id="section-name-{{ $secao->id }}" name="nome" maxlength="100" required value="{{ $secao->nome }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Renomear</button>
                </form>
            @endcan
            <button type="button" class="btn btn-sm btn-outline-primary mb-2" data-open-create-page data-create-section="{{ $secao->id }}">Adicionar página nesta seção</button>
            @if ($secao->paginas->isNotEmpty())
                <form method="POST" action="{{ route('secoes.reorder', $secao) }}" data-section-order-form>
                    @csrf @method('PUT')
                    <input type="hidden" name="versao" value="{{ $secao->versao }}">
                    <ol class="content-section-pages" data-section-order-list>
                        @foreach ($secao->paginas as $item)
                            <li data-section-page="{{ $item->id }}" @can('administer') draggable="true" @endcan>
                                <input type="hidden" name="paginas[]" value="{{ $item->id }}">
                                <a href="{{ route('gestao-conteudo.edit', $item) }}">{{ $item->pagina_titulo }}</a>
                                <small>{{ !$item->pagina_ativo ? 'Inativa' : ($item->pagina_publicada_em ? 'Publicada' : 'Rascunho') }}</small>
                                @can('administer')
                                    <span class="content-section-order-buttons">
                                        <button type="button" class="btn btn-sm btn-light" data-move-page="-1" aria-label="Mover {{ $item->pagina_titulo }} para cima">↑</button>
                                        <button type="button" class="btn btn-sm btn-light" data-move-page="1" aria-label="Mover {{ $item->pagina_titulo }} para baixo">↓</button>
                                    </span>
                                @endcan
                            </li>
                        @endforeach
                    </ol>
                    @can('administer')
                        <button type="submit" class="btn btn-sm btn-primary" data-save-section-order disabled>Salvar ordem</button>
                        <small data-section-order-status role="status">Arraste as páginas ou use as setas para organizar.</small>
                    @endcan
                </form>
            @else
                <p>Esta seção ainda não possui páginas.</p>
            @endif
        </details>
    @endforeach
    @if ($secoes->isEmpty())<p>Nenhuma seção criada. As páginas existentes continuam disponíveis.</p>@endif
</section>
