@extends('layouts.template')

@section('page_title', 'Visão Geral - TotemSync')
@section('header_title', 'Visão Geral')

@section(
    'header_subtitle',
    $agora->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y · H:i')
)

@section('conteudo')

    <div class="dashboard-theme admin-overview pb-4" id="adminOverview">

        {{-- ======================================
        CABEÇALHO DOS INDICADORES
        ====================================== --}}

        <div class="overview-section-header">

            <h3 class="overview-section-title">
                INDICADORES DO SISTEMA
            </h3>

            <button type="button" class="overview-btn-refresh" id="btnAtualizarDashboard" title="Atualizar indicadores">
                <i class="fas fa-sync-alt"></i>
                Atualizar
            </button>

        </div>

        {{-- ======================================
        INDICADORES
        ====================================== --}}

        <div class="overview-kpi-grid">

            {{-- TOTENS ONLINE --}}
            <div class="dashboard-card overview-kpi">

                <div class="overview-kpi-label status-online">
                    <span class="overview-status-dot"></span>
                    TOTENS ONLINE
                </div>

                <div class="overview-kpi-value">
                    {{ number_format($totalOnline, 0, ',', '.') }}
                </div>

                <div class="overview-kpi-footer">
                    de {{ $totalTotens }} cadastrados
                </div>

            </div>


            {{-- TOTENS OFFLINE --}}
            <div class="dashboard-card overview-kpi">

                <div class="overview-kpi-label status-offline">
                    <span class="overview-status-dot"></span>
                    TOTENS OFFLINE
                </div>

                <div class="overview-kpi-value">
                    {{ number_format($totalOffline, 0, ',', '.') }}
                </div>

                <div class="overview-kpi-footer">
                    sem comunicação recente
                </div>

            </div>


            {{-- INTERAÇÕES HOJE --}}
            <div class="dashboard-card overview-kpi">

                <div class="overview-kpi-label status-interacoes">
                    <i class="fas fa-bolt"></i>
                    INTERAÇÕES HOJE
                </div>

                <div class="overview-kpi-value overview-gold">
                    {{ number_format($interacoesHoje, 0, ',', '.') }}
                </div>

                <div class="overview-kpi-footer">

                    @if ($variacaoInteracoes !== null)

                        <span class="{{ $variacaoInteracoes >= 0 ? 'overview-positivo' : 'overview-negativo' }}">
                            {{ $variacaoInteracoes >= 0 ? '+' : '' }}
                            {{ number_format($variacaoInteracoes, 1, ',', '.') }}%
                        </span>

                        em relação a ontem no mesmo horário

                    @else

                        Sem base de comparação com ontem

                    @endif

                </div>

            </div>


            {{-- ALERTAS DE CONECTIVIDADE --}}
            <div class="dashboard-card overview-kpi">

                <div class="overview-kpi-label status-alerta">
                    <i class="fas fa-exclamation-triangle"></i>
                    ALERTAS DE CONECTIVIDADE
                </div>

                <div class="overview-kpi-value">
                    {{ number_format($totalOffline, 0, ',', '.') }}
                </div>

                <div class="overview-kpi-footer">
                    {{ $totalOffline === 0
        ? 'Nenhum equipamento sem contato'
        : 'Equipamentos que requerem verificação' }}
                </div>

            </div>

        </div>


        {{-- ======================================
        GRÁFICOS
        ====================================== --}}

        <div class="overview-grid">

            {{-- FLUXO DE USO POR HORÁRIO --}}
            <section class="overview-panel">

                <h3 class="overview-section-title">
                    FLUXO DE USO POR HORÁRIO — HOJE
                </h3>

                <div class="dashboard-card overview-chart-card">

                    <div class="overview-chart-area">

                        <canvas id="graficoFluxoHorario" role="img"
                            aria-label="Gráfico de interações por horário de hoje"></canvas>

                    </div>

                </div>

            </section>


            {{-- INTERAÇÕES NA SEMANA --}}
            <section class="overview-panel">

                <h3 class="overview-section-title">
                    INTERAÇÕES POR DIA — SEMANA ATUAL
                </h3>

                <div class="dashboard-card overview-chart-card">

                    <div class="overview-chart-area">

                        <canvas id="graficoInteracoesSemana" role="img"
                            aria-label="Gráfico de interações por dia na semana atual"></canvas>

                    </div>

                </div>

            </section>

        </div>


        {{-- ======================================
        CONTEÚDOS E ALERTAS
        ====================================== --}}

        <div class="overview-grid overview-bottom-grid">

            {{-- CONTEÚDOS MAIS VISUALIZADOS --}}
            <section class="overview-panel">

                <h3 class="overview-section-title">
                    CONTEÚDOS MAIS VISUALIZADOS — SEMANA
                </h3>

                <div class="dashboard-card overview-list-card">

                    @forelse ($conteudosMaisAcessados as $conteudo)

                                    <div class="overview-conteudo-item">

                                        <div class="overview-conteudo-info">

                                            <span class="overview-conteudo-nome" title="{{ $conteudo['nome'] }}">
                                                {{ $conteudo['nome'] }}
                                            </span>

                                            <span class="overview-conteudo-valor">
                                                {{ number_format(
                            $conteudo['total'],
                            0,
                            ',',
                            '.'
                        ) }}
                                            </span>

                                        </div>

                                        <div class="overview-progress">

                                            <div class="overview-progress-bar" style="width: {{ $conteudo['percentual'] }}%;"></div>

                                        </div>

                                    </div>

                    @empty

                        <div class="overview-empty">

                            <i class="fas fa-chart-bar"></i>

                            <p>
                                Ainda não há visualizações
                                registradas nesta semana.
                            </p>

                        </div>

                    @endforelse

                </div>

            </section>


            {{-- ALERTAS --}}
            <section class="overview-panel">

                <h3 class="overview-section-title">
                    ALERTAS DE CONECTIVIDADE
                </h3>

                <div class="dashboard-card overview-alert-card">

                    <div class="overview-alert-list">

                        @forelse ($alertas as $alerta)

                            <div class="overview-alert-item">

                                <div class="overview-alert-icon">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>

                                <div class="overview-alert-content">

                                    <div class="overview-alert-name">
                                        {{ $alerta['nome'] }}
                                    </div>

                                    <div class="overview-alert-description">
                                        {{ $alerta['mensagem'] }}
                                    </div>

                                    <div class="overview-alert-location">
                                        {{ $alerta['localizacao'] }}
                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="overview-empty overview-empty-success">

                                <i class="fas fa-check-circle"></i>

                                <p>
                                    Nenhum alerta de conectividade
                                    no momento.
                                </p>

                            </div>

                        @endforelse

                    </div>

                    <a href="{{ route('admin.totens.index') }}" class="overview-alert-link">
                        Ver monitoramento dos totens
                        <i class="fas fa-arrow-right"></i>
                    </a>

                </div>

            </section>

        </div>


        {{-- ======================================
        RODAPÉ INFORMATIVO
        ====================================== --}}

        <div class="overview-footer">

            <span>
                <i class="fas fa-info-circle"></i>

                Informações obtidas do banco de dados.
                Atualização automática a cada 60 segundos.
            </span>

            <span>
                Última consulta:
                {{ $agora->format('d/m/Y H:i:s') }}
            </span>

        </div>

    </div>


    {{-- ======================================
    DADOS DOS GRÁFICOS
    ====================================== --}}

    <script id="dadosDashboard" type="application/json">
    {!! json_encode(
        $graficos,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) !!}
    </script>


    {{-- CARREGA JAVASCRIPT E CSS DO DASHBOARD --}}
    @vite('resources/js/admin.js')

@endsection
