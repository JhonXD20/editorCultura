
@extends('layouts.template')

@section('page_title', 'Monitoramento - TotemSync')
@section('header_title', 'Monitoramento de Totens')

@section('header_subtitle')
    <span data-totem-total>{{ $resumo['todos'] }}</span> dispositivos cadastrados · situação calculada pelo último contato
@endsection

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
    data-status-url="{{ route('admin.totens.status') }}"
    data-csrf-token="{{ csrf_token() }}"
    data-refresh-interval="10000"
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
                Todos (<span data-totem-count="todos">{{ $resumo['todos'] }}</span>)
            </button>

            <button
                type="button"
                class="filter-btn"
                data-filtro="online"
                aria-pressed="false"
            >
                Online (<span data-totem-count="online">{{ $resumo['online'] }}</span>)
            </button>

            <button
                type="button"
                class="filter-btn"
                data-filtro="offline"
                aria-pressed="false"
            >
                Offline (<span data-totem-count="offline">{{ $resumo['offline'] }}</span>)
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
                <span>Atualizar agora</span>
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
                        <th width="200" class="text-center">AÇÕES</th>
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

                        <tr
                            data-status="{{ $status }}"
                            data-totem-id="{{ $totem->id }}"
                            data-ultimo-contato="{{ $totem->totem_ping?->toIso8601String() }}"
                        >

                            <td>
                                <span class="totem-id">
                                    #{{ $totem->id }}
                                </span>
                            </td>

                            <td>
                                <div class="dispositivo-info">

                                    <div class="dispositivo-nome" data-totem-nome>
                                        {{ $totem->totem_nome_identificacao }}
                                    </div>

                                    <div class="dispositivo-localizacao">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span data-totem-localizacao>{{ $totem->totem_localizacao ?: 'Localização não informada' }}</span>
                                    </div>

                                </div>
                            </td>

                            <td>
                                @if ($estaOnline)
                                    <span class="custom-badge badge-online" data-totem-status-badge>
                                        <span class="status-dot"></span>
                                        <span data-totem-status-text>Online</span>
                                    </span>
                                @else
                                    <span class="custom-badge badge-offline" data-totem-status-badge>
                                        <span class="status-dot"></span>
                                        <span data-totem-status-text>Offline</span>
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="ultimo-contato">

                                    <span
                                        class="{{ $estaOnline ? 'contato-online' : 'contato-offline' }}"
                                        title="{{ $dataContato }}"
                                        data-totem-contato-relativo
                                    >
                                        {{ $ultimoContato }}
                                    </span>

                                    <small class="data-contato" data-totem-contato-data>
                                        {{ $dataContato }}
                                    </small>

                                </div>
                            </td>

                            <td class="text-center">

                                <div class="acoes-totem">

                                    <button type="button"
                                        class="btn-action btn-acesso-totem"
                                        title="Preparar tablet"
                                        aria-label="Preparar tablet {{ $totem->id }}"
                                        data-url="{{ route('admin.totens.acesso', $totem) }}"
                                        data-nome="{{ $totem->totem_nome_identificacao }}">
                                        <i class="fas fa-tablet-alt"></i>
                                    </button>

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
                                        title="Recarregar conteúdo"
                                        data-url="{{ route('admin.totens.reload', $totem) }}"
                                        aria-label="Reiniciar totem {{ $totem->id }}"
                                        data-nome="{{ $totem->totem_nome_identificacao }}"
                                    >
                                        <i class="fas fa-redo-alt"></i>
                                    </button>

                                    {{-- EXCLUIR --}}
                                    <button type="button" class="btn-action btn-revogar-totem" title="Revogar acesso" data-url="{{ route('admin.totens.revoke', $totem) }}" data-nome="{{ $totem->totem_nome_identificacao }}"><i class="fas fa-ban" aria-hidden="true"></i></button>
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

                        <tr data-empty-row>
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

        <small class="totens-sync-status" id="totensSyncStatus" aria-live="polite">
            <span class="totens-sync-dot" aria-hidden="true"></span>
            <span data-sync-label>Atualização automática ativa</span>
            <span aria-hidden="true">·</span>
            <span><strong data-totem-total>{{ $resumo['todos'] }}</strong> dispositivo(s)</span>
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

            <fieldset class="totens-player-settings">
                <legend>Exibição no tablet</legend>
                <p class="cadastro-descricao">Preparado para Android na horizontal. As alterações entram em vigor ao abrir novamente a exibição.</p>

                <div class="totens-form-group">
                    <label for="pagina_inicial_id">Página inicial</label>
                    <select id="pagina_inicial_id" name="pagina_inicial_id"
                        class="totens-form-control @error('pagina_inicial_id', 'formTotem') is-invalid @enderror">
                        <option value="">Automática (página inicial do sistema)</option>
                        @foreach ($paginas as $opcaoPagina)
                            <option value="{{ $opcaoPagina->id }}" @selected((string) old('pagina_inicial_id') === (string) $opcaoPagina->id)>
                                {{ $opcaoPagina->pagina_titulo }}
                            </option>
                        @endforeach
                    </select>
                    @error('pagina_inicial_id', 'formTotem')
                        <span class="totens-erro-campo">{{ $message }}</span>
                    @enderror
                </div>

                <div class="totens-form-group">
                    <label for="totem_orientacao">Orientação do conteúdo</label>
                    <select id="totem_orientacao" name="totem_orientacao"
                        class="totens-form-control @error('totem_orientacao', 'formTotem') is-invalid @enderror">
                        <option value="landscape" @selected(old('totem_orientacao', 'landscape') === 'landscape')>Horizontal (padrão Android)</option>
                        <option value="portrait" @selected(old('totem_orientacao') === 'portrait')>Vertical</option>
                        <option value="auto" @selected(old('totem_orientacao') === 'auto')>Automática</option>
                    </select>
                    <small class="totens-field-help">A orientação física deve ser configurada no Android.</small>
                    @error('totem_orientacao', 'formTotem')
                        <span class="totens-erro-campo">{{ $message }}</span>
                    @enderror
                </div>

                <div class="totens-form-group">
                    <label for="totem_inatividade_segundos">Retornar ao início após</label>
                    <select id="totem_inatividade_segundos" name="totem_inatividade_segundos"
                        class="totens-form-control @error('totem_inatividade_segundos', 'formTotem') is-invalid @enderror">
                        @foreach ([0 => 'Desativado', 30 => '30 segundos', 60 => '1 minuto', 120 => '2 minutos', 180 => '3 minutos', 300 => '5 minutos', 600 => '10 minutos'] as $segundos => $rotulo)
                            <option value="{{ $segundos }}" @selected((string) old('totem_inatividade_segundos', 120) === (string) $segundos)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    <small class="totens-field-help">O tempo reinicia a cada interação do visitante.</small>
                    @error('totem_inatividade_segundos', 'formTotem')
                        <span class="totens-erro-campo">{{ $message }}</span>
                    @enderror
                </div>
                <div class="totens-form-group">
                    <input type="hidden" name="questionario_ativo" value="0">
                    <label for="questionario_ativo"><input type="checkbox" id="questionario_ativo" name="questionario_ativo" value="1" @checked(old('questionario_ativo', false))> Disponibilizar avaliação opcional aos visitantes</label>
                    <small class="totens-field-help">Ative após revisar as perguntas e definir o procedimento de aplicação.</small>
                </div>
            </fieldset>

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

<dialog id="modalAcessoTotem" class="totens-dialog" aria-labelledby="tituloAcessoTotem">
    <div class="totens-dialog-header">
        <h4 id="tituloAcessoTotem"><i class="fas fa-tablet-alt" aria-hidden="true"></i> Preparar tablet</h4>
        <button type="button" class="totens-dialog-close" data-fechar-modal="modalAcessoTotem" aria-label="Fechar">&times;</button>
    </div>
    <div class="totens-dialog-body">
        <p id="nomeAcessoTotem" class="text-main"></p>
        <p class="cadastro-descricao">Abra este endereço no tablet Android para associá-lo ao equipamento. O código é de uso único e expira em 10 minutos.</p>
        <p id="erroAcessoTotem" class="alert alert-danger" role="alert" hidden></p>
        <label for="urlAcessoTotem">Endereço de inicialização</label>
        <input type="url" id="urlAcessoTotem" class="totens-form-control" readonly>
        <canvas id="pairingQrCode" role="img" aria-label="QR Code para pareamento"></canvas>
        <p class="totens-field-help mt-2">O tablet precisa acessar o mesmo servidor. Configure a tela na horizontal no Android.</p>
        <p id="statusCopiaAcesso" class="totens-field-help" role="status"></p>
    </div>
    <div class="totens-dialog-footer">
        <button type="button" class="btn-salvar-cadastro" id="btnCopiarAcessoTotem" disabled>
            <i class="fas fa-copy" aria-hidden="true"></i> Copiar endereço
        </button>
    </div>
</dialog>

<form id="formExcluirTotem" method="POST" hidden>
    @csrf
    @method('DELETE')
</form>

@vite('resources/js/totens.js')

@endsection
