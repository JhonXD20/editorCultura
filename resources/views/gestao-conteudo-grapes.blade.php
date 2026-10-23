@extends('layouts.template')

@section('page_title', 'Editor visual - TotemSync')
@section('header_title', 'Editor visual de páginas')
@section('header_subtitle', 'Monte o conteúdo arrastando blocos, sem precisar programar.')

@section('conteudo')
    @php
        $editorConfig = [
            'pageId' => $pagina->id,
            'pageTitle' => $pagina->pagina_titulo,
            'projectData' => $pagina->pagina_dados_editor,
            'saveUrl' => route('editor-visual.save', $pagina),
            'submitUrl' => route('editor-visual.submit', $pagina),
            'publishUrl' => route('editor-visual.publish', $pagina),
            'uploadUrl' => route('editor-visual.upload'),
            'publicUrl' => route('gestao-conteudo.preview', $pagina),
            'draftVersion' => $pagina->pagina_versao_rascunho,
            'canPublish' => auth()->user()->can('publish-content'),
            'mediaAssets' => \App\Models\MediaAsset::where('tipo', 'image')->latest()->get()->map(fn ($asset) => [
                'src' => '/storage/'.$asset->caminho, 'name' => $asset->nome_original, 'type' => 'image',
            ]),
            'videoAssets' => \App\Models\MediaAsset::where('tipo', 'video')->latest()->get()->map(fn ($asset) => [
                'id' => '/storage/'.$asset->caminho, 'label' => $asset->nome_original,
            ]),
            'csrfToken' => csrf_token(),
            'publishedAt' => $pagina->pagina_publicada_em?->format('d/m/Y H:i'),
            'sections' => $secoes->map(fn ($item) => ['id' => $item->id, 'label' => $item->nome])->values(),
            'pages' => $paginas->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->pagina_titulo,
                'active' => (bool) $item->pagina_ativo,
                'editUrl' => route('gestao-conteudo.edit', $item),
            ])->values(),
        ];
    @endphp

    <div class="visual-editor-shell is-focus-mode" id="visualEditorApp">
        <div class="visual-editor-notice {{ $pagina->pagina_dados_editor ? 'd-none' : '' }}" id="firstDraftNotice">
            Esta página ainda usa o editor anterior na exibição pública. O novo modelo só ficará visível depois que você clicar em <strong>Publicar</strong>.
        </div>

        <div class="visual-editor-toolbar" aria-label="Ferramentas do editor">
            <div class="visual-editor-toolbar__group">
                <a class="visual-editor-icon-button" href="{{ route('gestao-conteudo.index') }}" title="Voltar ao gerenciador" aria-label="Voltar ao gerenciador de páginas">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                </a>
                <details class="visual-editor-app-menu" id="editorAppMenu">
                    <summary>
                        <i class="fas fa-bars" aria-hidden="true"></i>
                        <span>Menu</span>
                    </summary>
                    <nav aria-label="Navegação principal do sistema">
                        <a href="{{ route('admin') }}"><i class="fas fa-tachometer-alt" aria-hidden="true"></i> Painel</a>
                        <a href="{{ route('gestao-conteudo.index') }}" class="is-current"><i class="fas fa-object-group" aria-hidden="true"></i> Conteúdos</a>
                        <a href="{{ route('admin.totens.index') }}"><i class="fas fa-server" aria-hidden="true"></i> Totens</a>
                        <a href="{{ route('relatorios') }}"><i class="fas fa-chart-line" aria-hidden="true"></i> Relatórios</a>
                    </nav>
                </details>
                <div>
                    <strong class="visual-editor-page-title">{{ $pagina->pagina_titulo }}</strong>
                    <span class="visual-editor-status" id="editorStatus" role="status">Pronto para editar</span>
                </div>
            </div>

            <div class="visual-editor-toolbar__group visual-editor-toolbar__center">
                <button type="button" class="visual-editor-icon-button is-active" id="toggleBlocksButton" title="Mostrar ou ocultar blocos" aria-label="Mostrar ou ocultar blocos" aria-pressed="true">
                    <i class="fas fa-th-large" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button is-active" id="freePositionButton" title="Movimentação livre" aria-label="Ativar ou desativar movimentação livre" aria-pressed="true">
                    <i class="fas fa-arrows-alt" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button" id="undoButton" title="Desfazer" aria-label="Desfazer">
                    <i class="fas fa-undo" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button" id="redoButton" title="Refazer" aria-label="Refazer">
                    <i class="fas fa-redo" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button is-active" id="togglePropertiesButton" title="Mostrar ou ocultar propriedades" aria-label="Mostrar ou ocultar propriedades" aria-pressed="true">
                    <i class="fas fa-sliders-h" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button" id="editElementButton" title="Editar texto ou imagem" aria-label="Editar o elemento selecionado" disabled>
                    <i class="fas fa-edit" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button" id="duplicateElementButton" title="Duplicar elemento" aria-label="Duplicar o elemento selecionado" disabled>
                    <i class="fas fa-copy" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button" id="sendBackwardButton" title="Enviar para trás" aria-label="Enviar elemento para trás" disabled>
                    <i class="fas fa-level-down-alt" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button" id="bringForwardButton" title="Trazer para frente" aria-label="Trazer elemento para frente" disabled>
                    <i class="fas fa-level-up-alt" aria-hidden="true"></i>
                </button>
                <button type="button" class="visual-editor-icon-button visual-editor-icon-button--danger" id="deleteElementButton" title="Excluir elemento" aria-label="Excluir o elemento selecionado" disabled>
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                </button>
                <span class="visual-editor-divider" aria-hidden="true"></span>
                <label class="sr-only" for="deviceSelector">Tamanho da tela</label>
                <select class="visual-editor-device" id="deviceSelector">
                    <option value="totem">Tablet horizontal · 1366 × 768</option>
                    <option value="tablet">Tablet vertical</option>
                    <option value="mobile">Celular</option>
                </select>
                <button type="button" class="visual-editor-icon-button" id="responsiveFitButton" title="Recalcular adaptação para esta tela" aria-label="Recalcular adaptação para esta tela">
                    <i class="fas fa-magic" aria-hidden="true"></i>
                </button>
            </div>

            <div class="visual-editor-toolbar__group visual-editor-toolbar__actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="focusModeButton" aria-pressed="true">
                    <i class="fas fa-compress-alt mr-1" aria-hidden="true"></i>
                    <span>Sair da tela inteira</span>
                </button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('gestao-conteudo.preview', $pagina) }}" target="_blank" rel="noopener">
                    <i class="fas fa-eye mr-1" aria-hidden="true"></i> Visualizar
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('revisions.index', $pagina) }}">Histórico</a>
                <button class="btn btn-warning btn-sm" id="downloadConflictCopy" type="button" hidden>Baixar cópia local</button>
                @can('administer')
                <form
                    method="POST"
                    action="{{ route('gestao-conteudo.destroy', $pagina) }}"
                    class="visual-editor-delete-page-form"
                    id="deleteCurrentPageForm"
                    data-page-title="{{ $pagina->pagina_titulo }}"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="btn btn-outline-danger btn-sm"
                        id="deleteCurrentPageButton"
                        title="{{ $paginas->count() > 1 ? 'Excluir esta página' : 'A última página não pode ser excluída' }}"
                        @disabled($paginas->count() <= 1)
                    >
                        <i class="fas fa-trash-alt mr-1" aria-hidden="true"></i><span>Excluir página</span>
                    </button>
                </form>
                @endcan
                <button type="button" class="btn btn-outline-primary btn-sm" id="saveDraftButton">
                    <i class="fas fa-save mr-1" aria-hidden="true"></i> Salvar rascunho
                </button>
                <button type="button" class="btn btn-success btn-sm" id="publishButton">
                    <i class="fas fa-cloud-upload-alt mr-1" aria-hidden="true"></i>
                    {{ auth()->user()->can('publish-content') ? 'Publicar' : 'Enviar para revisão' }}
                </button>
            </div>
        </div>

        <div class="visual-editor-layout">
            <aside class="visual-editor-sidebar visual-editor-sidebar--left" aria-label="Páginas e blocos">
                <details class="visual-editor-pages" open>
                    <summary>Páginas</summary>
                    <nav class="visual-editor-page-list" aria-label="Páginas cadastradas">
                        @foreach ($paginas as $item)
                            <a href="{{ route('gestao-conteudo.edit', $item) }}"
                               class="visual-editor-page-link {{ $item->is($pagina) ? 'is-current' : '' }}">
                                <span>{{ $item->pagina_titulo }}</span>
                                @unless ($item->pagina_ativo)
                                    <small>inativa</small>
                                @endunless
                            </a>
                        @endforeach
                    </nav>

                    <form method="POST" action="{{ route('gestao-conteudo.store') }}" class="visual-editor-new-page">
                        @csrf
                        <input type="hidden" name="pagina_tipo_layout" value="interna">
                        <label class="sr-only" for="newPageTitle">Nome da nova página</label>
                        <input id="newPageTitle" name="pagina_titulo" type="text" maxlength="100" required placeholder="Nome da nova página">
                        <button type="submit" title="Criar página" aria-label="Criar página">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        </button>
                    </form>
                </details>

                <div class="visual-editor-panel-heading">
                    <strong>Blocos</strong>
                    <small>Arraste para a página</small>
                </div>
                <div id="gjsBlocks" class="visual-editor-blocks"></div>
            </aside>

            <section class="visual-editor-canvas-wrap" aria-label="Área de edição">
                <div id="gjs"></div>
            </section>

            <aside class="visual-editor-sidebar visual-editor-sidebar--right" aria-label="Configurações do elemento">
                <div class="visual-editor-tabs" role="tablist">
                    <button type="button" class="is-active" data-editor-tab="styles" role="tab">Estilo</button>
                    <button type="button" data-editor-tab="traits" role="tab">Conteúdo</button>
                    <button type="button" data-editor-tab="layers" role="tab">Camadas</button>
                </div>
                <div class="visual-editor-tab-panel is-active" data-editor-panel="styles" id="gjsStyles"></div>
                <div class="visual-editor-tab-panel" data-editor-panel="traits">
                    <div class="cultura-content-inspector" id="culturaContentInspector">
                        <div class="cultura-inspector-empty" id="inspectorEmptyState">
                            <i class="fas fa-mouse-pointer" aria-hidden="true"></i>
                            <strong>Selecione um elemento</strong>
                            <span>As opções de conteúdo aparecerão aqui.</span>
                        </div>

                        <div class="cultura-inspector-fields d-none" id="inspectorFields">
                            <div class="cultura-inspector-heading">
                                <strong id="inspectorElementName">Elemento</strong>
                                <span id="inspectorElementTag">HTML</span>
                            </div>

                            <div class="cultura-inspector-group" id="inspectorPositionGroup">
                                <label>Posição e tamanho</label>
                                <div class="cultura-position-grid">
                                    <div>
                                        <label for="elementPositionX">X</label>
                                        <input id="elementPositionX" type="number" step="1">
                                    </div>
                                    <div>
                                        <label for="elementPositionY">Y</label>
                                        <input id="elementPositionY" type="number" step="1">
                                    </div>
                                    <div>
                                        <label for="elementWidthField">Largura</label>
                                        <input id="elementWidthField" type="number" min="1" step="1">
                                    </div>
                                    <div>
                                        <label for="elementHeightField">Altura</label>
                                        <input id="elementHeightField" type="number" min="1" step="1">
                                    </div>
                                </div>
                                <small>Também é possível arrastar e redimensionar diretamente no canvas.</small>
                            </div>

                            <div class="cultura-inspector-group d-none" id="inspectorTextGroup">
                                <label for="elementTextField">Texto</label>
                                <textarea id="elementTextField" rows="5" placeholder="Digite o conteúdo"></textarea>
                                <div class="cultura-format-actions" aria-label="Formatação rápida">
                                    <button type="button" data-quick-style="font-weight" data-style-value="700" title="Negrito"><strong>B</strong></button>
                                    <button type="button" data-quick-style="font-style" data-style-value="italic" title="Itálico"><em>I</em></button>
                                    <button type="button" data-quick-style="text-decoration" data-style-value="underline" title="Sublinhado"><u>U</u></button>
                                    <button type="button" data-quick-style="text-align" data-style-value="left" title="Alinhar à esquerda"><i class="fas fa-align-left"></i></button>
                                    <button type="button" data-quick-style="text-align" data-style-value="center" title="Centralizar"><i class="fas fa-align-center"></i></button>
                                    <button type="button" data-quick-style="text-align" data-style-value="right" title="Alinhar à direita"><i class="fas fa-align-right"></i></button>
                                </div>
                                <small>Use duplo clique no elemento para formatação avançada.</small>
                            </div>

                            <div class="cultura-inspector-group d-none" id="inspectorButtonGroup">
                                <label for="buttonIconField">Ícone do botão</label>
                                <select id="buttonIconField">
                                    <option value="">Sem ícone</option>
                                    <option value="fas fa-home">Início</option>
                                    <option value="fas fa-info-circle">Informações</option>
                                    <option value="fas fa-landmark">Cultura / monumento</option>
                                    <option value="fas fa-image">Imagem</option>
                                    <option value="fas fa-map-marker-alt">Localização</option>
                                    <option value="fas fa-arrow-right">Seta</option>
                                    <option value="fas fa-play">Reproduzir</option>
                                    <option value="fas fa-book-open">Livro</option>
                                    <option value="fas fa-calendar-alt">Calendário</option>
                                </select>

                                <div class="cultura-inspector-row">
                                    <div>
                                        <label for="buttonIconPosition">Posição</label>
                                        <select id="buttonIconPosition">
                                            <option value="start">Antes do texto</option>
                                            <option value="end">Depois do texto</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="buttonIconSize">Tamanho</label>
                                        <input id="buttonIconSize" type="number" min="8" max="200" value="22">
                                    </div>
                                </div>

                                <button type="button" class="cultura-inspector-button" id="buttonCustomImageButton">
                                    <i class="fas fa-upload" aria-hidden="true"></i> Usar imagem personalizada
                                </button>
                                <button type="button" class="cultura-inspector-link-button d-none" id="removeButtonImageButton">
                                    Remover imagem do botão
                                </button>
                            </div>

                            <div class="cultura-inspector-group d-none" id="inspectorNavigationGroup">
                                <label for="navigationActionField">Ao tocar neste elemento</label>
                                <select id="navigationActionField">
                                    <option value="">Sem navegação</option>
                                    <option value="page">Abrir página</option>
                                    <option value="section">Abrir seção</option>
                                </select>
                                <label for="buttonDestinationField">Página de destino</label>
                                <select id="buttonDestinationField"></select>
                                <label for="sectionDestinationField">Seção de destino</label>
                                <select id="sectionDestinationField"></select>
                                <small>Ao abrir uma seção, o totem encontra sua primeira página ativa e publicada.</small>
                            </div>

                            <div class="cultura-inspector-group d-none" id="inspectorImageGroup">
                                <label>Imagem</label>
                                <div class="cultura-image-preview" id="imagePreview"></div>
                                <button type="button" class="cultura-inspector-button" id="changeImageButton">
                                    <i class="fas fa-images" aria-hidden="true"></i> Escolher ou enviar imagem
                                </button>
                                <label for="imageAltField">Descrição da imagem</label>
                                <input id="imageAltField" type="text" maxlength="300" placeholder="Descreva a imagem para acessibilidade">
                            </div>

                            <div class="cultura-inspector-group d-none" id="inspectorLinkGroup">
                                <label for="linkUrlField">Endereço do link</label>
                                <input id="linkUrlField" type="url" placeholder="https://...">
                                <label class="cultura-check-label">
                                    <input id="linkNewWindowField" type="checkbox"> Abrir em nova janela
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="gjsTraits" class="cultura-native-traits"></div>
                </div>
                <div class="visual-editor-tab-panel" data-editor-panel="layers" id="gjsLayers"></div>
            </aside>
        </div>

        <div class="visual-editor-help">
            <span><i class="fas fa-mouse-pointer" aria-hidden="true"></i> Clique para selecionar; clique duas vezes para editar</span>
            <span><i class="fas fa-arrows-alt" aria-hidden="true"></i> Selecione e arraste pelo ícone azul de quatro setas</span>
            <span><kbd>Shift</kbd> + seta move 10 px</span>
            <span><i class="fas fa-mobile-alt" aria-hidden="true"></i> Cada dispositivo mantém seus próprios ajustes</span>
            <span><kbd>Ctrl</kbd> + <kbd>S</kbd> salva o rascunho</span>
            @if ($pagina->pagina_publicada_em)
                <span>Última publicação: {{ $pagina->pagina_publicada_em->format('d/m/Y H:i') }}</span>
            @endif
        </div>
    </div>

    <script id="visualEditorConfig" type="application/json">{!! json_encode($editorConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@stop

@section('js_custom')
    @vite('resources/js/grapes-editor.js')
@stop
