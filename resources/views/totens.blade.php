
@extends('layouts.template')

@section('page_title', 'Monitoramento - Editor Cultura')
@section('header_title', 'Monitoramento de Totens')

@section(
    'header_subtitle',
    $resumo['todos'] . ' dispositivos cadastrados · situação calculada pelo último contato'
)

@section('css_custom')
    @vite('resources/css/totens.css')
@endsection

@section('conteudo')

<div
    class="dashboard-theme totens-page pb-4"
    id="paginaTotens"
    data-reabrir-formulario="{{ $errors->formTotem->any() ? '1' : '0' }}"
    data-modo-anterior="{{ old('totem_form_modo', 'criar') }}"
    data-id-anterior="{{ old('totem_id_edicao') }}"
>

    {{-- FILTROS E BOTÕES --}}
    <div class="totens-toolbar">

        <div class="filters-container">

            <button
                type="button"
                class="filter-btn active"
                data-filtro="todos"
                aria-pressed="true"
            >
                Todos ({{ $resumo['todos'] }})
            </button>

            <button
                type="button"
                class="filter-btn"
                data-filtro="online"
                aria-pressed="false"
            >
                Online ({{ $resumo['online'] }})
            </button>

            <button
                type="button"
                class="filter-btn"
                data-filtro="offline"
                aria-pressed="false"
            >
                Offline ({{ $resumo['offline'] }})
            </button>

        </div>

        <div class="totens-toolbar-acoes">

            <button
                type="button"
                class="btn-cadastrar-totem"
                id="btnCadastrarTotem"
            >
                <i class="fas fa-plus"></i>
                Cadastrar Totem
            </button>

            <button
                type="button"
                class="btn-atualizar-lista"
                id="btnAtualizarLista"
            >
                <i class="fas fa-sync-alt"></i>
                Atualizar dados
            </button>

        </div>

    </div>

    {{-- TABELA --}}
    <div class="dashboard-card totens-card">

        <div class="table-responsive">

            <table class="table custom-table mb-0">

                <thead>
                    <tr>
                        <th width="90">ID</th>
                        <th>DISPOSITIVO / LOCALIZAÇÃO</th>
                        <th width="160">STATUS</th>
                        <th width="190">ÚLTIMO CONTATO</th>
                        <th width="160" class="text-center">AÇÕES</th>
                    </tr>
                </thead>

                <tbody id="tabelaTotens">

                    @forelse ($totens as $totem)

                        @php
                            $status = $totem->status_monitoramento;
                            $estaOnline = $status === 'online';

                            $ultimoContato = $totem->totem_ping
                                ? $totem->totem_ping->diffForHumans()
                                : 'Nunca';

                            $dataContato = $totem->totem_ping
                                ? $totem->totem_ping->format('d/m/Y H:i:s')
                                : 'Nunca';
                        @endphp

                        <tr data-status="{{ $status }}">

                            <td>
                                <span class="totem-id">
                                    #{{ $totem->id }}
                                </span>
                            </td>

                            <td>
                                <div class="dispositivo-info">

                                    <div class="dispositivo-nome">
                                        {{ $totem->totem_nome_identificacao }}
                                    </div>

                                    <div class="dispositivo-localizacao">
                                        <i class="fas fa-map-marker-alt"></i>

                                        {{ $totem->totem_localizacao ?: 'Localização não informada' }}
                                    </div>

                                </div>
                            </td>

                            <td>
                                @if ($estaOnline)
                                    <span class="custom-badge badge-online">
                                        <span class="status-dot"></span>
                                        Online
                                    </span>
                                @else
                                    <span class="custom-badge badge-offline">
                                        <span class="status-dot"></span>
                                        Offline
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="ultimo-contato">

                                    <span
                                        class="{{ $estaOnline ? 'contato-online' : 'contato-offline' }}"
                                        title="{{ $dataContato }}"
                                    >
                                        {{ $ultimoContato }}
                                    </span>

                                    <small class="data-contato">
                                        {{ $dataContato }}
                                    </small>

                                </div>
                            </td>

                            <td class="text-center">

                                <div class="acoes-totem">

                                    {{-- EDITAR --}}
                                    <button
                                        type="button"
                                        class="btn-action btn-editar-totem"
                                        title="Editar Totem"
                                        aria-label="Editar totem {{ $totem->id }}"
                                        data-id="{{ $totem->id }}"
                                        data-url-busca="{{ route('admin.totens.show', $totem->id) }}"
                                        data-url-editar="{{ route('admin.totens.update', $totem->id) }}"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    {{-- REINICIAR --}}
                                    <button
                                        type="button"
                                        class="btn-action btn-reiniciar-totem"
                                        title="Reiniciar Totem"
                                        aria-label="Reiniciar totem {{ $totem->id }}"
                                        data-nome="{{ $totem->totem_nome_identificacao }}"
                                    >
                                        <i class="fas fa-redo-alt"></i>
                                    </button>

                                    {{-- EXCLUIR --}}
                                    <button
                                        type="button"
                                        class="btn-action btn-excluir-totem"
                                        title="Excluir Totem"
                                        aria-label="Excluir totem {{ $totem->id }}"
                                        data-nome="{{ $totem->totem_nome_identificacao }}"
                                        data-url="{{ route('admin.totens.destroy', $totem->id) }}"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5">
                                <div class="sem-totens">
                                    <i class="fas fa-desktop"></i>
                                    <h5>Nenhum totem cadastrado</h5>
                                    <p>
                                        Cadastre um equipamento para
                                        começar o monitoramento.
                                    </p>
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div
            class="sem-resultados-filtro"
            id="semResultadosFiltro"
            hidden
        >
            <i class="fas fa-search"></i>
            <p>Nenhum dispositivo encontrado neste filtro.</p>
        </div>

    </div>

    <div class="totens-rodape">

        <small>
            <i class="fas fa-info-circle"></i>
            Status baseado no último ping recebido.
        </small>

        <small>
            {{ $resumo['todos'] }} dispositivo(s) cadastrado(s)
        </small>

    </div>

</div>


{{-- ==================================================
     MODAL ÚNICO: CADASTRAR E EDITAR
================================================== --}}

<dialog
    id="modalCadastrarTotem"
    class="totens-dialog cadastro-dialog"
>

    <form
        id="formCadastrarTotem"
        action="{{ route('admin.totens.store') }}"
        data-url-criar="{{ route('admin.totens.store') }}"
        method="POST"
    >

        @csrf

        {{--
            Desabilitado ao cadastrar:
            POST /totens

            Habilitado ao editar:
            PUT /totens/{id}
        --}}
        <input
            type="hidden"
            name="_method"
            id="metodoFormTotem"
            value="PUT"
            disabled
        >

        <input
            type="hidden"
            name="totem_form_modo"
            id="campoModoTotem"
            value="{{ old('totem_form_modo', 'criar') }}"
        >

        <input
            type="hidden"
            name="totem_id_edicao"
            id="campoIdTotem"
            value="{{ old('totem_id_edicao') }}"
        >

        <div class="totens-dialog-header">

            <h4 id="tituloModalTotem">
                <i class="fas fa-plus-circle"></i>
                <span id="textoTituloModalTotem">
                    Cadastrar Novo Totem
                </span>
            </h4>

            <button
                type="button"
                class="totens-dialog-close"
                id="btnFecharCadastro"
                aria-label="Fechar"
            >
                &times;
            </button>

        </div>

        <div class="totens-dialog-body">

            <p class="cadastro-descricao" id="descricaoModalTotem">
                Informe os dados de identificação
                e localização do equipamento.
            </p>

            <div
                class="alert alert-danger"
                id="erroCarregamentoTotem"
                role="alert"
                hidden
            ></div>

            {{-- NOME --}}
            <div class="totens-form-group">

                <label for="totem_nome_identificacao">
                    Nome / Identificação
                    <span class="campo-obrigatorio">*</span>
                </label>

                <input
                    type="text"
                    id="totem_nome_identificacao"
                    name="totem_nome_identificacao"
                    class="totens-form-control
                        @error('totem_nome_identificacao', 'formTotem') is-invalid @enderror"
                    placeholder="Ex: Totem Entrada Principal"
                    maxlength="100"
                    value="{{ old('totem_nome_identificacao') }}"
                    required
                >

                @error('totem_nome_identificacao', 'formTotem')
                    <span class="totens-erro-campo">
                        {{ $message }}
                    </span>
                @enderror

            </div>

            {{-- LOCALIZAÇÃO --}}
            <div class="totens-form-group">

                <label for="totem_localizacao">
                    Localização
                </label>

                <input
                    type="text"
                    id="totem_localizacao"
                    name="totem_localizacao"
                    class="totens-form-control
                        @error('totem_localizacao', 'formTotem') is-invalid @enderror"
                    placeholder="Ex: Memorial Heróis do Tocantins"
                    maxlength="100"
                    value="{{ old('totem_localizacao') }}"
                >

                @error('totem_localizacao', 'formTotem')
                    <span class="totens-erro-campo">
                        {{ $message }}
                    </span>
                @enderror

            </div>

            <div class="callout callout-info mb-0" id="avisoCadastroTotem">

                <i class="fas fa-info-circle"></i>

                <span>
                    O novo equipamento será cadastrado como
                    <strong>Offline</strong> até realizar
                    sua primeira comunicação.
                </span>

            </div>

        </div>

        <div class="totens-dialog-footer">

            <button
                type="button"
                class="btn-cancelar-cadastro"
                id="btnCancelarCadastro"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="btn-salvar-cadastro"
                id="btnSalvarCadastro"
            >
                <i class="fas fa-save"></i>
                <span id="textoBotaoSalvarTotem">
                    Salvar Totem
                </span>
            </button>

        </div>

    </form>

</dialog>


<form id="formExcluirTotem" method="POST" hidden>
    @csrf
    @method('DELETE')
</form>

@vite('resources/js/totens.js')

@endsection
