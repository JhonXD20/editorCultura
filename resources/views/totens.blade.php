
@extends('layouts.template')

@section('page_title', 'Monitoramento - Editor Cultura')

@section('header_title', 'Monitoramento de Totens')

@section(
    'header_subtitle',
    $resumo['todos'] . ' dispositivos cadastrados · situação calculada pelo último contato'
)

@section('conteudo')

<div class="dashboard-theme pb-4">

    {{-- =====================================
         MENSAGENS DO SISTEMA
    ===================================== --}}

    @if (session('success'))

        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}

            <button
                type="button"
                class="btn-close close"
                data-dismiss="alert"
                data-bs-dismiss="alert"
                aria-label="Fechar"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

    @endif

    {{-- =====================================
         CABEÇALHO DOS FILTROS
    ===================================== --}}

    <div class="d-flex flex-wrap align-items-center
                justify-content-between gap-3 mb-4">

        <div class="d-flex flex-wrap gap-2 filters-container">

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

        <button
            type="button"
            class="btn-atualizar-lista"
            id="btnAtualizarLista"
            title="Consultar novamente os dados"
        >
            <i class="fas fa-sync-alt"></i>
            Atualizar dados
        </button>

    </div>

    {{-- =====================================
         TABELA DE TOTENS
    ===================================== --}}

    <div class="dashboard-card p-0 overflow-hidden">

        <div class="table-responsive">

            <table class="table custom-table mb-0">

                <thead>
                    <tr>

                        <th width="90">
                            ID
                        </th>

                        <th>
                            DISPOSITIVO / LOCALIZAÇÃO
                        </th>

                        <th width="160">
                            STATUS
                        </th>

                        <th width="190">
                            ÚLTIMO CONTATO
                        </th>

                        <th width="130" class="text-center">
                            AÇÕES
                        </th>

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
                                ? $totem->totem_ping->format(
                                    'd/m/Y H:i:s'
                                )
                                : 'Nunca';
                        @endphp

                        <tr data-status="{{ $status }}">

                            {{-- ID --}}
                            <td>

                                <span class="totem-id">
                                    #{{ $totem->id }}
                                </span>

                            </td>

                            {{-- IDENTIFICAÇÃO / LOCALIZAÇÃO --}}
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

                            {{-- STATUS --}}
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

                            {{-- ÚLTIMO CONTATO --}}
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

                            {{-- AÇÕES --}}
                            <td class="text-center">

                                <button
                                    type="button"
                                    class="btn-action btn-detalhes"
                                    title="Visualizar detalhes"
                                    aria-label="Detalhes do totem {{ $totem->id }}"

                                    data-id="{{ $totem->id }}"
                                    data-nome="{{ $totem->totem_nome_identificacao }}"
                                    data-localizacao="{{ $totem->totem_localizacao ?: 'Não informada' }}"
                                    data-status="{{ $status }}"
                                    data-ultimo-contato="{{ $dataContato }}"
                                    data-cadastro="{{ $totem->created_at?->format('d/m/Y H:i') ?? 'Não informado' }}"
                                >
                                    <i class="fas fa-list"></i>
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="text-center py-5">

                                <div class="sem-totens">

                                    <i class="fas fa-desktop"></i>

                                    <h5>
                                        Nenhum totem cadastrado
                                    </h5>

                                    <p>
                                        Os equipamentos cadastrados
                                        aparecerão nesta página.
                                    </p>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Mensagem quando o filtro não encontra dados --}}
        <div
            class="sem-resultados-filtro"
            id="semResultadosFiltro"
            hidden
        >
            <i class="fas fa-search"></i>

            <p>
                Nenhum dispositivo encontrado neste filtro.
            </p>
        </div>

    </div>

    {{-- =====================================
         RODAPÉ DA TABELA
    ===================================== --}}

    <div class="d-flex justify-content-between
                align-items-center flex-wrap mt-3">

        <small class="texto-informativo">

            <i class="fas fa-info-circle"></i>

            Status baseado no último ping recebido.

        </small>

        <small class="texto-informativo">

            {{ $resumo['todos'] }}
            dispositivo(s) cadastrado(s)

        </small>

    </div>

</div>

{{-- =====================================
     MODAL NATIVO DE DETALHES
===================================== --}}

<dialog id="modalDetalhesTotem" class="totem-dialog">

    <div class="totem-dialog-header">

        <h4 id="tituloDetalhesTotem">
            Detalhes do Totem
        </h4>

        <button
            type="button"
            class="totem-dialog-close"
            id="btnFecharDetalhes"
            aria-label="Fechar detalhes"
        >
            &times;
        </button>

    </div>

    <div class="totem-dialog-body">

        <div class="detalhe-campo">
            <strong>ID:</strong>
            <span id="detalheId"></span>
        </div>

        <div class="detalhe-campo">
            <strong>Identificação:</strong>
            <span id="detalheNome"></span>
        </div>

        <div class="detalhe-campo">
            <strong>Localização:</strong>
            <span id="detalheLocalizacao"></span>
        </div>

        <div class="detalhe-campo">
            <strong>Status:</strong>
            <span id="detalheStatus"></span>
        </div>

        <div class="detalhe-campo">
            <strong>Último contato:</strong>
            <span id="detalheContato"></span>
        </div>

        <div class="detalhe-campo">
            <strong>Data do cadastro:</strong>
            <span id="detalheCadastro"></span>
        </div>

    </div>

    <div class="totem-dialog-footer">

        <button
            type="button"
            class="btn-fechar-modal"
            id="btnFecharRodape"
        >
            Fechar
        </button>

    </div>

</dialog>

{{-- =====================================
     FUNCIONALIDADES DA PÁGINA
===================================== --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =====================================
       FILTROS
    ===================================== */

    const botoesFiltro = document.querySelectorAll(
        '.filter-btn[data-filtro]'
    );

    const linhas = document.querySelectorAll(
        '#tabelaTotens tr[data-status]'
    );

    const semResultados = document.getElementById(
        'semResultadosFiltro'
    );

    botoesFiltro.forEach(botao => {

        botao.addEventListener('click', function () {

            const filtro = this.dataset.filtro;

            botoesFiltro.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });

            this.classList.add('active');
            this.setAttribute('aria-pressed', 'true');

            let visiveis = 0;

            linhas.forEach(linha => {

                const status = linha.dataset.status;

                const mostrar =
                    filtro === 'todos' ||
                    filtro === status;

                linha.hidden = !mostrar;

                if (mostrar) {
                    visiveis++;
                }
            });

            if (semResultados) {
                semResultados.hidden =
                    visiveis > 0 || linhas.length === 0;
            }

        });

    });

    /* =====================================
       ATUALIZAR DADOS
    ===================================== */

    const btnAtualizar = document.getElementById(
        'btnAtualizarLista'
    );

    if (btnAtualizar) {

        btnAtualizar.addEventListener('click', function () {

            // Nova consulta ao controller Laravel
            window.location.reload();

        });
    }

    /* =====================================
       MODAL DE DETALHES
    ===================================== */

    const modal = document.getElementById(
        'modalDetalhesTotem'
    );

    const botoesDetalhes = document.querySelectorAll(
        '.btn-detalhes'
    );

    function preencherTexto(id, valor) {

        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent = valor || 'Não informado';
        }
    }

    botoesDetalhes.forEach(botao => {

        botao.addEventListener('click', function () {

            const dados = this.dataset;

            preencherTexto('detalheId', dados.id);

            preencherTexto(
                'detalheNome',
                dados.nome
            );

            preencherTexto(
                'detalheLocalizacao',
                dados.localizacao
            );

            preencherTexto(
                'detalheStatus',
                dados.status === 'online'
                    ? 'Online'
                    : 'Offline'
            );

            preencherTexto(
                'detalheContato',
                dados.ultimoContato
            );

            preencherTexto(
                'detalheCadastro',
                dados.cadastro
            );

            if (modal && typeof modal.showModal === 'function') {
                modal.showModal();
            }

        });

    });

    /* =====================================
       FECHAR MODAL
    ===================================== */

    function fecharModal() {
        if (modal?.open) {
            modal.close();
        }
    }

    document.getElementById('btnFecharDetalhes')
        ?.addEventListener('click', fecharModal);

    document.getElementById('btnFecharRodape')
        ?.addEventListener('click', fecharModal);

    modal?.addEventListener('click', function (evento) {

        if (evento.target === modal) {
            fecharModal();
        }

    });

});
</script>

@stop


@section('css_custom')

<style>

/* =====================================
   FILTROS
===================================== */

.filters-container {
    gap: 10px;
}

.filter-btn {
    background-color: transparent;

    border: 1px solid var(--border-color, #dee2e6);

    color: var(--color-text-muted, #64748b);

    border-radius: 20px;

    padding: 6px 16px;

    font-size: 0.85rem;
    font-weight: 600;

    cursor: pointer;

    transition: all 0.2s ease;
}

.filter-btn:hover,
.filter-btn.active {
    border-color: var(--color-gold, #cb9c54);
    color: var(--color-gold, #cb9c54);
}

/* Botão de atualização */

.btn-atualizar-lista {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    background: transparent;

    border: 1px solid var(--border-color, #dee2e6);
    border-radius: 6px;

    padding: 8px 14px;

    color: var(--color-text-muted, #64748b);

    font-size: 0.85rem;
    font-weight: 600;

    cursor: pointer;
}

.btn-atualizar-lista:hover {
    border-color: var(--color-gold, #cb9c54);
    color: var(--color-gold, #cb9c54);
}

/* =====================================
   TABELA
===================================== */

.custom-table {
    width: 100%;
    color: var(--color-text-main, #1f2937);
    margin-bottom: 0;
}

.custom-table th,
.custom-table td {
    border-top: none;

    border-bottom: 1px solid var(
        --border-color,
        #dee2e6
    );

    padding: 1rem 1.25rem;

    background-color: transparent !important;

    vertical-align: middle;
}

.custom-table thead th {
    font-size: 0.75rem;

    text-transform: uppercase;

    letter-spacing: 1px;

    color: var(--color-text-muted, #64748b);

    font-weight: 600;
}

.custom-table tbody tr:hover {
    background-color: rgba(0,0,0,.02);
}

.custom-table tbody tr[hidden] {
    display: none !important;
}

/* =====================================
   IDENTIFICAÇÃO
===================================== */

.totem-id {
    color: var(--color-gold, #cb9c54);
    font-weight: 600;
}

.dispositivo-nome {
    color: var(--color-text-main, #1f2937);

    font-weight: 500;
    font-size: 0.95rem;
}

.dispositivo-localizacao {
    color: var(--color-text-muted, #64748b);

    font-size: 0.85rem;
    margin-top: 3px;
}

.dispositivo-localizacao i {
    margin-right: 4px;
    font-size: 0.75rem;
}

/* =====================================
   STATUS
===================================== */

.custom-badge {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 5px 11px;

    border-radius: 14px;

    font-size: 0.8rem;
    font-weight: 600;

    white-space: nowrap;
}

.status-dot {
    display: inline-block;

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background-color: currentColor;
}

.badge-online {
    background-color: rgba(46,160,67,.15);
    color: #16a34a;
}

.badge-offline {
    background-color: rgba(139,148,158,.15);
    color: #64748b;
}

/* =====================================
   ÚLTIMO CONTATO
===================================== */

.ultimo-contato {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.contato-online {
    color: #16a34a;
    font-weight: 500;
}

.contato-offline {
    color: var(--color-text-muted, #64748b);
}

.data-contato {
    font-size: 0.75rem;
    color: var(--color-text-muted, #64748b);
}

/* =====================================
   AÇÕES
===================================== */

.btn-action {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    width: 34px;
    height: 34px;

    border: 1px solid var(--border-color, #dee2e6);

    border-radius: 5px;

    background: transparent;

    color: var(--color-text-muted, #64748b);

    cursor: pointer;

    transition: all .2s ease;
}

.btn-action:hover {
    border-color: var(--color-gold, #cb9c54);
    color: var(--color-gold, #cb9c54);
}

/* =====================================
   ESTADOS VAZIOS
===================================== */

.sem-totens,
.sem-resultados-filtro {
    text-align: center;

    color: var(--color-text-muted, #64748b);

    padding: 28px;
}

.sem-totens i,
.sem-resultados-filtro i {
    font-size: 28px;
    margin-bottom: 12px;
}

.sem-totens h5 {
    font-size: 1rem;
}

.sem-totens p,
.sem-resultados-filtro p {
    margin-bottom: 0;
    font-size: .9rem;
}

.sem-resultados-filtro[hidden] {
    display: none !important;
}

.texto-informativo {
    color: var(--color-text-muted, #64748b);
}

/* =====================================
   MODAL NATIVO
===================================== */

.totem-dialog {
    width: min(94vw, 520px);

    border: none;
    border-radius: 12px;

    padding: 0;

    background: #ffffff;

    color: #1f2937;

    box-shadow: 0 15px 45px rgba(0,0,0,.22);
}

.totem-dialog::backdrop {
    background: rgba(0,0,0,.45);
}

.totem-dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 18px 22px;

    border-bottom: 1px solid #e5e7eb;
}

.totem-dialog-header h4 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 600;
}

.totem-dialog-close {
    background: transparent;
    border: none;

    font-size: 26px;
    color: #64748b;

    cursor: pointer;
}

.totem-dialog-body {
    padding: 22px;
}

.detalhe-campo {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;

    padding: 11px 0;

    border-bottom: 1px solid #f1f5f9;

    font-size: .9rem;
}

.detalhe-campo strong {
    flex-shrink: 0;
}

.detalhe-campo span {
    text-align: right;
    overflow-wrap: anywhere;
}

.totem-dialog-footer {
    display: flex;
    justify-content: flex-end;

    padding: 15px 22px;

    border-top: 1px solid #e5e7eb;
}

.btn-fechar-modal {
    background: #334155;
    border: none;
    border-radius: 6px;

    padding: 8px 20px;

    color: #fff;
    cursor: pointer;
}

.btn-fechar-modal:hover {
    background: #1e293b;
}

</style>

@stop
