
@if (!$pagina)

    <div class="alert alert-danger">
        Página não encontrada.
    </div>

@else

<div class="card mb-0">

    <div class="card-header bg-white border-bottom">

        <h3 class="card-title text-primary">
            <i class="fas fa-edit"></i>
            Editando:
            <strong>{{ $pagina->pagina_titulo }}</strong>
        </h3>

    </div>

    <div
        class="card-body p-0 totem-editor-viewport"
        id="totemEditorViewport"
    >

        {{-- Mesmo canvas da exibição pública --}}
        @include('partials.canvas', [
            'pagina' => $pagina,
            'editavel' => true
        ])

        {{-- Uploads --}}
        <input
            type="file"
            id="inputUploadImagem"
            accept="image/*"
            hidden
        >

        <input
            type="file"
            id="inputUploadFundo"
            accept="image/*"
            hidden
        >

        {{-- Desfazer e refazer --}}
        <div class="floating-controls-left">

            <button
                type="button"
                class="btn-floating"
                id="btnUndo"
                disabled
                title="Desfazer"
            >
                <i class="fas fa-undo"></i>
            </button>

            <button
                type="button"
                class="btn-floating"
                id="btnRedo"
                disabled
                title="Refazer"
            >
                <i class="fas fa-redo"></i>
            </button>

        </div>

        {{-- Ferramentas --}}
        <div class="floating-controls">

            <button
                type="button"
                class="btn-floating btn-add-text"
                id="btnAddTextoLivre"
                title="Adicionar texto"
            >
                <i class="fas fa-font"></i>
            </button>

            <button
                type="button"
                class="btn-floating btn-adjust-bg"
                id="btnAjustarFundo"
                title="Bloquear/desbloquear fundo"
            >
                <i class="fas fa-lock"></i>
            </button>

            <button
                type="button"
                class="btn-floating btn-change-bg"
                id="btnTrocarFundo"
                title="Alterar fundo"
            >
                <i class="fas fa-palette"></i>
            </button>

            <button
                type="button"
                class="btn-floating btn-publish"
                id="btnPublicarTotem"
                title="Publicar"
            >
                <i class="fas fa-paper-plane"></i>
            </button>

            <button
                type="button"
                class="btn-floating btn-add-block"
                id="btnAddBotao"
                data-toggle="modal"
                data-target="#modalEdicao"
                title="Adicionar botão"
            >
                <i class="fas fa-plus"></i>
            </button>

            <button
                type="button"
                class="btn-floating btn-save-mode"
                id="btnSalvarCanvas"
                title="Salvar posições"
            >
                <i class="fas fa-check"></i>
            </button>

            <button
                type="button"
                class="btn-floating"
                id="btnToggleEdicao"
                title="Ativar edição"
            >
                <i class="fas fa-pencil-alt"></i>
            </button>

        </div>

    </div>
</div>

{{-- Modal: adicionar botão --}}
<div
    class="modal fade"
    id="modalEdicao"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content text-dark">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">Novo Botão</h5>

                <button
                    type="button"
                    class="close text-white"
                    data-dismiss="modal"
                >
                    <span>&times;</span>
                </button>

            </div>

            <form
                action="{{ route('conteudos.store') }}"
                method="POST"
            >
                @csrf

                <input
                    type="hidden"
                    name="pagina_id"
                    value="{{ $pagina->id }}"
                >

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 form-group">

                            <label>Título</label>

                            <input
                                type="text"
                                name="titulo"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="col-md-6 form-group">

                            <label>Destino</label>

                            <select
                                name="pagina_destino_id"
                                id="selectPaginaDestino"
                                class="form-control"
                                required
                            >

                                <option value="" disabled selected>
                                    Selecione...
                                </option>

                                <option value="nova">
                                    ➕ Criar Nova Página
                                </option>

                                <optgroup label="Existentes">

                                    @foreach (($todasPaginas ?? []) as $pag)

                                        <option value="{{ $pag->id }}">
                                            {{ $pag->pagina_titulo }}
                                        </option>

                                    @endforeach

                                </optgroup>

                            </select>

                        </div>

                    </div>

                    <div
                        class="row"
                        id="divNovaPagina"
                        style="display:none"
                    >

                        <div class="col-md-6 form-group">

                            <label>Nome da Nova Página</label>

                            <input
                                type="text"
                                name="nova_pagina_titulo"
                                id="inputNovaPagina"
                                class="form-control"
                            >

                        </div>

                        <div class="col-md-6 form-group">

                            <label>Grupo</label>

                            <input
                                type="text"
                                name="grupo_slide"
                                class="form-control"
                                placeholder="Deixe vazio se for menu"
                            >

                        </div>

                    </div>

                    <div class="form-group">

                        <label>Ícone Inicial</label>

                        <input
                            type="text"
                            name="icone"
                            class="form-control"
                            value="totem/img/botoes/BTN_Historia.png"
                        >

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        <i class="fas fa-save"></i>
                        Adicionar
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

{{-- Modal: edição de texto --}}
<div
    class="modal fade"
    id="modalEdicaoTexto"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content text-dark">

            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">Formatação</h5>

                <button
                    type="button"
                    class="close text-white"
                    data-dismiss="modal"
                >
                    <span>&times;</span>
                </button>

            </div>

            <div class="modal-body p-0">

                <textarea id="summernoteTexto"></textarea>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-success"
                    id="btnSalvarTextoFormato"
                >
                    <i class="fas fa-save"></i>
                    Aplicar
                </button>

            </div>

        </div>

    </div>
</div>

<script>
    window.TotemEditor = {
        routes: {
            upload: @json(route('conteudos.upload-imagem')),
            salvar: @json(route('conteudos.salvar-em-massa')),
            publicar: @json(route('conteudos.publicar'))
        },

        csrfToken: @json(csrf_token())
    };
</script>

@endif
