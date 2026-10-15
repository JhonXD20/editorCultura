@extends('layouts.template')

@section('page_title', 'Gestão de páginas - TotemSync')
@section('header_title', 'Gestão de conteúdo')
@section('header_subtitle', 'Crie, encontre e administre todas as páginas do aplicativo.')

@section('css_custom')
    @vite('resources/css/content-manager.css')
@endsection

@section('conteudo')
    <div
        class="dashboard-theme content-manager pb-4"
        id="contentManager"
        data-open-create="{{ $errors->any() ? '1' : '0' }}"
    >
        <section class="content-manager-summary" aria-label="Resumo das páginas">
            <article class="content-summary-card">
                <span class="content-summary-icon"><i class="fas fa-file-alt" aria-hidden="true"></i></span>
                <div><strong>{{ $resumo['todas'] }}</strong><span>Total de páginas</span></div>
            </article>
            <article class="content-summary-card">
                <span class="content-summary-icon is-published"><i class="fas fa-cloud" aria-hidden="true"></i></span>
                <div><strong>{{ $resumo['publicadas'] }}</strong><span>Com publicação</span></div>
            </article>
            <article class="content-summary-card">
                <span class="content-summary-icon is-draft"><i class="fas fa-pencil-alt" aria-hidden="true"></i></span>
                <div><strong>{{ $resumo['rascunhos'] }}</strong><span>Somente rascunho</span></div>
            </article>
            <article class="content-summary-card">
                <span class="content-summary-icon is-inactive"><i class="fas fa-eye-slash" aria-hidden="true"></i></span>
                <div><strong>{{ $resumo['inativas'] }}</strong><span>Inativas</span></div>
            </article>
        </section>

        <div class="content-manager-toolbar">
            <div class="content-manager-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <label class="sr-only" for="pageSearch">Buscar página</label>
                <input id="pageSearch" type="search" placeholder="Buscar pelo nome da página" autocomplete="off">
            </div>

            <div class="content-manager-filters" aria-label="Filtrar páginas">
                <button type="button" class="is-active" data-page-filter="todas" aria-pressed="true">Todas</button>
                <button type="button" data-page-filter="publicada" aria-pressed="false">Publicadas</button>
                <button type="button" data-page-filter="rascunho" aria-pressed="false">Rascunhos</button>
                <button type="button" data-page-filter="inativa" aria-pressed="false">Inativas</button>
            </div>

            <button type="button" class="content-manager-create" id="openCreatePage">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Nova página
            </button>
        </div>

        <section class="dashboard-card content-manager-card" aria-labelledby="pagesListTitle">
            <div class="content-manager-card-heading">
                <div>
                    <h2 id="pagesListTitle">Páginas criadas</h2>
                    <p>Abra uma página para editar ou use as ações para visualizar e excluir.</p>
                </div>
                <span id="visiblePagesCount">{{ $resumo['todas'] }} exibidas</span>
            </div>

            <div class="table-responsive">
                <table class="table content-manager-table mb-0">
                    <thead>
                        <tr>
                            <th>Página</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Última alteração</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="pagesTableBody">
                        @forelse ($paginas as $pagina)
                            @php
                                $hasPendingChanges = $pagina->published_at
                                    && $pagina->updated_at->greaterThan($pagina->published_at);
                                $publicationStatus = $pagina->published_at ? 'publicada' : 'rascunho';
                                $filterTerms = [$publicationStatus];

                                if (! $pagina->pagina_ativo) {
                                    $filterTerms[] = 'inativa';
                                }
                            @endphp
                            <tr
                                data-page-row
                                data-page-name="{{ Illuminate\Support\Str::lower($pagina->pagina_titulo) }}"
                                data-page-filters="{{ implode(' ', $filterTerms) }}"
                            >
                                <td>
                                    <div class="content-page-identity">
                                        <span class="content-page-icon">
                                            <i class="{{ $pagina->pagina_tipo_layout === 'home' ? 'fas fa-home' : 'far fa-file-alt' }}" aria-hidden="true"></i>
                                        </span>
                                        <div>
                                            <a href="{{ route('gestao-conteudo.edit', $pagina) }}">{{ $pagina->pagina_titulo }}</a>
                                            <small>
                                                #{{ $pagina->id }} ·
                                                {{ $pagina->published_at ? 'Publicada em '.$pagina->published_at->format('d/m/Y') : 'Ainda não publicada' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="content-layout-badge">
                                        {{ $pagina->pagina_tipo_layout === 'home' ? 'Página inicial' : 'Página interna' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="content-status-list">
                                        @if (! $pagina->pagina_ativo)
                                            <span class="content-status is-inactive"><i class="fas fa-circle" aria-hidden="true"></i> Inativa</span>
                                        @elseif ($pagina->published_at)
                                            <span class="content-status is-published"><i class="fas fa-circle" aria-hidden="true"></i> Publicada</span>
                                        @else
                                            <span class="content-status is-draft"><i class="fas fa-circle" aria-hidden="true"></i> Rascunho</span>
                                        @endif

                                        @if ($hasPendingChanges)
                                            <small>Possui alterações não publicadas</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="content-page-date">
                                        <span>{{ $pagina->updated_at->format('d/m/Y H:i') }}</span>
                                        <small>{{ $pagina->updated_at->diffForHumans() }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="content-page-actions">
                                        @if ($pagina->pagina_ativo)
                                            <a href="{{ route('totem.exibir', $pagina) }}" target="_blank" rel="noopener" title="Visualizar página" aria-label="Visualizar {{ $pagina->pagina_titulo }}">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <button type="button" disabled title="Ative a página para visualizá-la" aria-label="Página inativa">
                                                <i class="fas fa-eye-slash" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                        <a class="is-primary" href="{{ route('gestao-conteudo.edit', $pagina) }}" title="Editar página" aria-label="Editar {{ $pagina->pagina_titulo }}">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                            <span>Editar</span>
                                        </a>
                                        <form
                                            method="POST"
                                            action="{{ route('gestao-conteudo.destroy', $pagina) }}"
                                            data-delete-page-form
                                            data-page-title="{{ $pagina->pagina_titulo }}"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="is-danger"
                                                title="{{ $paginas->count() > 1 ? 'Excluir página' : 'A última página não pode ser excluída' }}"
                                                aria-label="Excluir {{ $pagina->pagina_titulo }}"
                                                @disabled($paginas->count() <= 1)
                                            >
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="content-manager-empty">
                                        <i class="far fa-file-alt" aria-hidden="true"></i>
                                        <h3>Nenhuma página criada</h3>
                                        <p>Crie a primeira página para começar a montar o conteúdo.</p>
                                        <button type="button" data-open-create-page>Criar primeira página</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="content-manager-no-results" id="noPageResults" hidden>
                <i class="fas fa-search" aria-hidden="true"></i>
                Nenhuma página corresponde à busca ou ao filtro selecionado.
            </div>
        </section>

        <dialog class="content-manager-dialog" id="createPageDialog" aria-labelledby="createPageTitle">
            <form method="POST" action="{{ route('gestao-conteudo.store') }}" id="createPageForm">
                @csrf
                <div class="content-dialog-heading">
                    <div>
                        <span class="content-dialog-icon"><i class="fas fa-file-medical" aria-hidden="true"></i></span>
                        <div>
                            <h2 id="createPageTitle">Criar uma página</h2>
                            <p>Depois de criar, o editor visual será aberto automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" data-close-dialog aria-label="Fechar">&times;</button>
                </div>

                <div class="content-dialog-body">
                    <label for="newPageTitle">Nome da página</label>
                    <input
                        id="newPageTitle"
                        name="pagina_titulo"
                        type="text"
                        value="{{ old('pagina_titulo') }}"
                        maxlength="100"
                        required
                        autofocus
                        placeholder="Ex.: História de Porto Nacional"
                    >
                    @error('pagina_titulo')
                        <small class="content-field-error">{{ $message }}</small>
                    @enderror

                    <label for="newPageType">Tipo da página</label>
                    <select id="newPageType" name="pagina_tipo_layout" required>
                        <option value="interna" @selected(old('pagina_tipo_layout', 'interna') === 'interna')>Página interna</option>
                        <option value="home" @selected(old('pagina_tipo_layout') === 'home')>Página inicial</option>
                    </select>
                    <small>Página inicial é a tela de entrada do totem. Para conteúdos comuns, use página interna.</small>
                    @error('pagina_tipo_layout')
                        <small class="content-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="content-dialog-actions">
                    <button type="button" class="is-secondary" data-close-dialog>Cancelar</button>
                    <button type="submit" class="is-primary" id="createPageSubmit">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Criar e abrir editor
                    </button>
                </div>
            </form>
        </dialog>
    </div>
@stop

@section('js_custom')
    @vite('resources/js/content-manager.js')
@stop
