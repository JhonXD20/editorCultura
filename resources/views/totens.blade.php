@extends('layouts.template') <!-- Use 'layouts.template' se você salvou dentro da pasta layouts -->

<!-- Substituindo os yields do cabeçalho do template mestre -->
@section('page_title', 'Relatórios - Editor Cultura')
@section('header_title', 'Monitoramento de Totens')
@section('header_subtitle', '9 dispositivos registrados · última verificação há 30 segundos')

<!-- O conteúdo real da página entra no miolo -->
@section('conteudo')
    <div class="dashboard-theme pb-4">
        
        <!-- Filtros -->
        <div class="d-flex flex-wrap gap-2 mb-4 filters-container">
            <button class="filter-btn active">Todos (9)</button>
            <button class="filter-btn">Online (5)</button>
            <button class="filter-btn">Sincronizando (1)</button>
            <button class="filter-btn">Offline (2)</button>
            <button class="filter-btn">Erro Crítico (1)</button>
        </div>

        <!-- Tabela de Dados -->
        <div class="dashboard-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table custom-table mb-0">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th>DISPOSITIVO / LOCALIZAÇÃO</th>
                            <th width="150">STATUS</th>
                            <th width="100">VERSÃO</th>
                            <th width="130">TEMPERATURA</th>
                            <th width="120">UPTIME</th>
                            <th width="150">ÚLTIMO CONTATO</th>
                            <th width="160" class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Linha 1 -->
                        <tr>
                            <td class="text-gold fw-bold align-middle">T-A1</td>
                            <td>
                                <div class="text-main fw-bold">Totem Entrada Principal</div>
                                <div class="text-muted" style="font-size: 0.85rem;">Hall de Entrada — Portão Leste</div>
                            </td>
                            <td class="align-middle">
                                <span class="custom-badge badge-online"><span class="dot">●</span> Online</span>
                            </td>
                            <td class="align-middle text-muted">v4.2.1</td>
                            <td class="align-middle text-main fw-bold">42°C</td>
                            <td class="align-middle text-muted">14d 6h</td>
                            <td class="align-middle text-muted">agora</td>
                            <td class="align-middle text-center">
                                <button class="btn-action" title="Reiniciar"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-action" title="Opções"><i class="fas fa-bars"></i></button>
                                <button class="btn-action" title="Desligar"><i class="fas fa-power-off"></i></button>
                            </td>
                        </tr>

                        <!-- Linha 2 -->
                        <tr>
                            <td class="text-gold fw-bold align-middle">T-A2</td>
                            <td>
                                <div class="text-main fw-bold">Totem Galeria Permanente</div>
                                <div class="text-muted" style="font-size: 0.85rem;">Ala Norte — Sala 02</div>
                            </td>
                            <td class="align-middle">
                                <span class="custom-badge badge-online"><span class="dot">●</span> Online</span>
                            </td>
                            <td class="align-middle text-muted">v4.2.1</td>
                            <td class="align-middle text-main fw-bold">44°C</td>
                            <td class="align-middle text-muted">14d 5h</td>
                            <td class="align-middle text-muted">2 min atrás</td>
                            <td class="align-middle text-center">
                                <button class="btn-action"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-action"><i class="fas fa-bars"></i></button>
                                <button class="btn-action"><i class="fas fa-power-off"></i></button>
                            </td>
                        </tr>

                        <!-- Linha 3 -->
                        <tr>
                            <td class="text-gold fw-bold align-middle">T-A3</td>
                            <td>
                                <div class="text-main fw-bold">Totem Exposição Temporária</div>
                                <div class="text-muted" style="font-size: 0.85rem;">Ala Sul — Corredor B</div>
                            </td>
                            <td class="align-middle">
                                <span class="custom-badge badge-error"><span class="dot">●</span> Erro Crítico</span>
                            </td>
                            <td class="align-middle text-muted">v4.1.8</td>
                            <td class="align-middle text-red fw-bold">72°C</td>
                            <td class="align-middle text-muted">—</td>
                            <td class="align-middle text-muted">22 min atrás</td>
                            <td class="align-middle text-center">
                                <button class="btn-action"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-action"><i class="fas fa-bars"></i></button>
                                <button class="btn-action"><i class="fas fa-power-off"></i></button>
                            </td>
                        </tr>

                        <!-- Linha 4 -->
                        <tr>
                            <td class="text-gold fw-bold align-middle">T-B1</td>
                            <td>
                                <div class="text-main fw-bold">Totem Auditório</div>
                                <div class="text-muted" style="font-size: 0.85rem;">Auditório Principal — Entrada</div>
                            </td>
                            <td class="align-middle">
                                <span class="custom-badge badge-offline"><span class="dot">●</span> Offline</span>
                            </td>
                            <td class="align-middle text-muted">v4.0.3</td>
                            <td class="align-middle text-muted">—</td>
                            <td class="align-middle text-muted">—</td>
                            <td class="align-middle text-muted">1h atrás</td>
                            <td class="align-middle text-center">
                                <button class="btn-action"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-action"><i class="fas fa-bars"></i></button>
                                <button class="btn-action"><i class="fas fa-power-off"></i></button>
                            </td>
                        </tr>

                        <!-- Linha 5 (Última visível como exemplo, com borda inferior removida) -->
                        <tr>
                            <td class="text-gold fw-bold align-middle border-0">T-B2</td>
                            <td class="border-0">
                                <div class="text-main fw-bold">Totem Acervo Digital</div>
                                <div class="text-muted" style="font-size: 0.85rem;">Biblioteca Histórica — Piso 2</div>
                            </td>
                            <td class="align-middle border-0">
                                <span class="custom-badge badge-sync"><span class="dot">●</span> Sincronizando</span>
                            </td>
                            <td class="align-middle text-muted border-0">v4.2.0</td>
                            <td class="align-middle text-main fw-bold border-0">48°C</td>
                            <td class="align-middle text-muted border-0">7d 2h</td>
                            <td class="align-middle text-muted border-0">5 min atrás</td>
                            <td class="align-middle text-center border-0">
                                <button class="btn-action"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-action"><i class="fas fa-bars"></i></button>
                                <button class="btn-action"><i class="fas fa-power-off"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

<!-- Injetando apenas o CSS que pertence a esta tabela -->
@section('css_custom')
<style>
    /* Botões de Filtro */
    .filters-container { gap: 10px; }
    
    .filter-btn {
        background-color: transparent;
        border: 1px solid var(--border-color);
        color: var(--color-text-muted);
        border-radius: 20px;
        padding: 6px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .filter-btn:hover, .filter-btn.active { 
        border-color: var(--color-gold); 
        color: var(--color-gold); 
    }

    /* Estilização da Tabela */
    .custom-table { color: var(--color-text-main); margin-bottom: 0; }
    
    .custom-table th, .custom-table td {
        border-top: none;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem 1.25rem;
        background-color: transparent !important;
    }
    
    .custom-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--color-text-muted);
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }

    /* Badges de Status */
    .custom-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .custom-badge .dot { font-size: 10px; margin-right: 6px; }
    
    .badge-online { background-color: rgba(46, 160, 67, 0.15); color: var(--color-green); }
    .badge-error { background-color: rgba(248, 81, 73, 0.15); color: var(--color-red); }
    .badge-offline { background-color: rgba(139, 148, 158, 0.15); color: var(--color-text-muted); }
    .badge-sync { background-color: rgba(203, 162, 88, 0.15); color: var(--color-gold); }

    /* Botões de Ação na Tabela */
    .btn-action {
        background-color: transparent;
        border: 1px solid var(--border-color);
        color: var(--color-text-muted);
        border-radius: 4px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        margin: 0 2px;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .btn-action:hover {
        border-color: var(--color-gold);
        color: var(--color-gold);
        background-color: rgba(203, 162, 88, 0.05);
    }
</style>
@stop