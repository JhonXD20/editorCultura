@extends('layouts.template')

@section('title', 'Editor Cultura - Visão Geral')

@section('content_header')
<div class="d-flex justify-content-between align-items-center custom-header">
    <div>
        <h1 class="font-serif text-main mb-0">Visão Geral</h1>
        <p class="text-muted-custom mb-0 text-lowercase" style="text-transform: none !important;">Domingo, 27 de julho
            de 2025 · 16:42</p>
    </div>
    <div class="d-flex align-items-center text-green text-muted-custom">
        <span class="mr-2 me-2" style="font-size: 10px;">●</span> SISTEMA OPERACIONAL
    </div>
</div>
@stop

@section('conteudo')
<div class="dashboard-theme pb-4">

    <!-- Indicadores -->
    <div class="section-title">Indicadores em tempo real</div>
    <div class="row">
        <div class="col-12 col-md-3 mb-3">
            <div class="dashboard-card card-online">
                <div class="text-green text-muted-custom fw-bold font-weight-bold mb-2"><span
                        style="font-size: 10px;">●</span> TOTENS ONLINE</div>
                <div class="kpi-value font-serif text-main">14</div>
                <div class="text-muted" style="font-size: 0.85rem;">de 17 instalados</div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <div class="dashboard-card card-offline">
                <div class="text-red text-muted-custom fw-bold font-weight-bold mb-2"><span
                        style="font-size: 10px;">●</span> TOTENS OFFLINE</div>
                <div class="kpi-value font-serif text-main">3</div>
                <div class="text-muted" style="font-size: 0.85rem;">requerem atenção</div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <div class="dashboard-card card-gold">
                <div class="text-gold text-muted-custom fw-bold font-weight-bold mb-2">✦ INTERAÇÕES HOJE</div>
                <div class="kpi-value font-serif text-main">2.847</div>
                <div class="text-muted" style="font-size: 0.85rem;">+12% em relação a ontem</div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <div class="dashboard-card card-offline">
                <div class="text-red text-muted-custom fw-bold font-weight-bold mb-2">⚠ ALERTAS CRÍTICOS</div>
                <div class="kpi-value font-serif text-main">2</div>
                <div class="text-muted" style="font-size: 0.85rem;">Totem A3 e Totem B1</div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
        <div class="row mt-2">
            <div class="col-12 col-md-6 mb-4">
                <div class="section-title">Fluxo de uso por horário - Hoje</div>
                <div class="dashboard-card">
                    <!-- Nova div envolvendo o canvas -->
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 mb-4">
                <div class="section-title">Interações por dia - Semana atual</div>
                <div class="dashboard-card">
                    <!-- Nova div envolvendo o canvas -->
                    <div class="chart-container">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
<!-- Listas e Alertas -->
        <div class="row mt-2">
            
            <!-- Conteúdos Mais Acessados -->
            <div class="col-12 col-md-6 mb-3">
                <div class="section-title mt-0">Conteúdos mais acessados - Semana</div>
                <div class="dashboard-card">
                    <!-- Contêiner com rolagem interna -->
                    <div class="scrollable-content">
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center text-main mb-1" style="font-size: 0.95rem;">
                                <span class="text-truncate-custom pe-2">Fundação do Memorial (1923)</span>
                                <span class="text-muted flex-shrink-0">1.240</span>
                            </div>
                            <div class="custom-progress"><div class="custom-progress-bar" style="width: 100%;"></div></div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center text-main mb-1" style="font-size: 0.95rem;">
                                <span class="text-truncate-custom pe-2">Galeria de Pioneiros</span>
                                <span class="text-muted flex-shrink-0">980</span>
                            </div>
                            <div class="custom-progress"><div class="custom-progress-bar" style="width: 75%;"></div></div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center text-main mb-1" style="font-size: 0.95rem;">
                                <span class="text-truncate-custom pe-2">A Cidade no Século XX</span>
                                <span class="text-muted flex-shrink-0">754</span>
                            </div>
                            <div class="custom-progress"><div class="custom-progress-bar" style="width: 55%;"></div></div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center text-main mb-1" style="font-size: 0.95rem;">
                                <span class="text-truncate-custom pe-2">Acervo Fotográfico — Anos 50</span>
                                <span class="text-muted flex-shrink-0">631</span>
                            </div>
                            <div class="custom-progress"><div class="custom-progress-bar" style="width: 45%;"></div></div>
                        </div>

                        <div class="mb-1">
                            <div class="d-flex justify-content-between align-items-center text-main mb-1" style="font-size: 0.95rem;">
                                <span class="text-truncate-custom pe-2">Eventos Culturais 2024</span>
                                <span class="text-muted flex-shrink-0">488</span>
                            </div>
                            <div class="custom-progress"><div class="custom-progress-bar" style="width: 35%;"></div></div>
                        </div>
                        
                    </div>
                </div>
            </div>

            <!-- Alertas do Sistema -->
            <div class="col-12 col-md-6 mb-3">
                <div class="section-title mt-0">Alertas do Sistema</div>
                <div class="dashboard-card d-flex flex-column">
                    <!-- Contêiner com rolagem interna -->
                    <div class="scrollable-content mb-3">
                        
                        <div class="alert-item alert-border-red">
                            <div class="alert-badge red flex-shrink-0">A3</div>
                            <div class="flex-grow-1 text-main text-truncate-custom pe-2" style="font-size: 0.95rem;">Tela sem resposta há 22 min</div>
                            <div class="text-muted flex-shrink-0" style="font-size: 0.8rem;">14:37</div>
                        </div>

                        <div class="alert-item alert-border-red">
                            <div class="alert-badge red flex-shrink-0">B1</div>
                            <div class="flex-grow-1 text-main text-truncate-custom pe-2" style="font-size: 0.95rem;">Temperatura acima do limite (72°C)</div>
                            <div class="text-muted flex-shrink-0" style="font-size: 0.8rem;">13:55</div>
                        </div>

                        <div class="alert-item alert-border-gold mb-0">
                            <div class="alert-badge gold flex-shrink-0">C2</div>
                            <div class="flex-grow-1 text-main text-truncate-custom pe-2" style="font-size: 0.95rem;">Conteúdo desatualizado — 3 dias</div>
                            <div class="text-muted flex-shrink-0" style="font-size: 0.8rem;">09:12</div>
                        </div>
                        
                    </div>

                    <div class="mt-auto">
                        <a href="#" class="text-gold text-decoration-none" style="font-size: 0.9rem;">Ver todos os alertas &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        
@stop


@section('css_custom')

<style>
    /* Apenas coisas exclusivas desta tela do Dashboard */
    .chart-container {
        position: relative;
        height: 180px; 
        width: 100%;
    }

    .custom-progress {
        height: 4px;
        background-color: var(--progress-bg);
        border-radius: 2px;
        margin-top: 8px;
        transition: background-color 0.3s;
    }

    .custom-progress-bar {
        background-color: var(--color-gold);
        height: 100%;
        border-radius: 2px;
    }
</style>
@stop

@section('js')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Cores neutras para o Chart.js funcionarem bem no claro e no escuro
    Chart.defaults.color = '#8b949e';
    Chart.defaults.font.family = 'Inter';
    const gridColor = 'rgba(139, 148, 158, 0.15)'; // Linhas suaves para ambos os temas

    // Gráfico de Linha (Fluxo de Uso)
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['08h', '09h', '10h', '11h', '12h', '13h', '14h', '15h', '16h', '17h', '18h', '19h'],
            datasets: [{
                data: [50, 120, 210, 280, 200, 160, 320, 390, 420, 340, 200, 100],
                borderColor: '#cba258',
                backgroundColor: 'rgba(203, 162, 88, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false, drawBorder: false } },
                y: {
                    grid: { color: gridColor, drawBorder: false },
                    min: 0,
                    max: 600,
                    ticks: { stepSize: 150 }
                }
            }
        }
    });

    // Gráfico de Barras (Interações por Dia)
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'],
            datasets: [{
                data: [420, 380, 510, 450, 680, 820, 750],
                backgroundColor: '#cba258',
                borderRadius: 4,
                barThickness: 'flex',
                maxBarThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false, drawBorder: false } },
                y: {
                    grid: { color: gridColor, drawBorder: false },
                    min: 0,
                    max: 1000,
                    ticks: { stepSize: 250 }
                }
            }
        }
    });
</script>
@stop