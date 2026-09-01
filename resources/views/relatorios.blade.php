@extends('layouts.template')

@section('page_title', 'Relatórios de Uso - Editor Cultura')
@section('header_title', 'Relatórios de Uso')
@section('header_subtitle', 'Análise de engajamento e comportamento de público')

@section('conteudo')
<div class="dashboard-theme pb-4">
    
    <!-- Barra de Filtros -->
    <div class="d-flex justify-content-end mb-4">
        <div class="dashboard-card p-2 d-flex align-items-center flex-wrap gap-3" style="border-radius: 12px;">
            
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted-custom" style="font-size: 0.75rem;">DE</span>
                <input type="date" class="form-control custom-input form-control-sm" value="2025-07-21">
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted-custom" style="font-size: 0.75rem;">ATÉ</span>
                <input type="date" class="form-control custom-input form-control-sm" value="2025-07-27">
            </div>
            
            <div>
                <select class="form-select custom-input form-select-sm" style="min-width: 180px;">
                    <option selected>Todos os Totens</option>
                    <option value="1">Totem Entrada Principal</option>
                    <option value="2">Totem Galeria Permanente</option>
                </select>
            </div>
            
            <button class="btn btn-gold btn-sm fw-bold px-4" style="border-radius: 6px;">Aplicar</button>
            
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1 -->
        <div class="col-12 col-md-3">
            <div class="dashboard-card h-100">
                <div class="text-muted-custom mb-2">TOTAL DE INTERAÇÕES</div>
                <div class="kpi-value font-serif text-gold mb-1" style="font-size: 2.8rem;">16.617</div>
                <div class="text-muted" style="font-size: 0.85rem;">no período selecionado</div>
            </div>
        </div>
        
        <!-- Card 2 -->
        <div class="col-12 col-md-3">
            <div class="dashboard-card h-100">
                <div class="text-muted-custom mb-2">MÉDIA DIÁRIA</div>
                <div class="kpi-value font-serif text-white mb-1" style="font-size: 2.8rem;">2.374</div>
                <div class="text-muted" style="font-size: 0.85rem;">toques por dia</div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="col-12 col-md-3">
            <div class="dashboard-card h-100">
                <div class="text-muted-custom mb-2">SLIDE COM MAIOR RETENÇÃO</div>
                <div class="kpi-value font-serif text-white mb-1" style="font-size: 2.8rem;">78%</div>
                <div class="text-muted" style="font-size: 0.85rem;">Fundação (1923)</div>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="col-12 col-md-3">
            <div class="dashboard-card h-100">
                <div class="text-muted-custom mb-2">PICO DE USO</div>
                <div class="kpi-value font-serif text-white mb-1" style="font-size: 2.8rem;">Sábado</div>
                <div class="text-muted" style="font-size: 0.85rem;">3.010 interações — 16h</div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3">
        <!-- Gráfico de Linha -->
        <div class="col-12 col-md-7">
            <div class="section-title mt-0">TOQUES POR DIA</div>
            <div class="dashboard-card">
                <div class="chart-container" style="height: 320px;">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Rosca (Doughnut) -->
        <div class="col-12 col-md-5">
            <div class="section-title mt-0">DISTRIBUIÇÃO POR TOTEM (%)</div>
            <div class="dashboard-card d-flex flex-column align-items-center justify-content-center" style="min-height: 350px;">
                <div class="chart-container position-relative" style="height: 220px; width: 220px; margin: 0 auto;">
                    <canvas id="doughnutChart"></canvas>
                </div>
                
                <!-- Legenda Customizada -->
                <div id="chartLegend" class="d-flex flex-wrap justify-content-center mt-4 gap-3 text-muted" style="font-size: 0.75rem; font-family: 'Inter', monospace;">
                    <!-- A legenda será gerada via JS para espelhar a imagem -->
                </div>
            </div>
        </div>
    </div>

</div>
@stop

@section('css_custom')
<style>
    /* Estilos Específicos para Relatórios */
    .btn-gold {
        background-color: var(--color-gold);
        color: #161b22;
        border: none;
        transition: opacity 0.2s;
    }
    
    .btn-gold:hover {
        opacity: 0.9;
        color: #161b22;
    }

    /* Inputs de Filtro Customizados */
    .custom-input {
        background-color: transparent !important;
        border: 1px solid var(--border-color) !important;
        color: var(--color-text-main) !important;
    }

    .custom-input:focus {
        box-shadow: none !important;
        border-color: var(--color-gold) !important;
    }

    /* Ajuste para ícone de calendário no input date (browsers Webkit) */
    ::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 0.6;
        cursor: pointer;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
</style>
@stop

@section('js_custom')
<!-- Certifique-se de que o Chart.js está sendo chamado no template base ou aqui -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Configurações globais
    Chart.defaults.color = '#8b949e';
    Chart.defaults.font.family = 'Inter';
    const gridColor = 'rgba(139, 148, 158, 0.1)';

    /* =========================================
       GRÁFICO DE LINHA (Toques por Dia)
       ========================================= */
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['21/07', '22/07', '23/07', '24/07', '25/07', '26/07', '27/07'],
            datasets: [{
                data: [1800, 2100, 1750, 2600, 2400, 3100, 2900],
                borderColor: '#cba258',
                backgroundColor: '#cba258', // Cor do ponto
                borderWidth: 2,
                pointBackgroundColor: '#cba258',
                pointBorderColor: '#161b22', // Borda escura no ponto para destacar
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: false,
                tension: 0.4 // Linha curvada
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { 
                    grid: { display: false, drawBorder: false }
                },
                y: {
                    grid: { color: gridColor, drawBorder: false },
                    min: 0,
                    max: 3200,
                    ticks: { stepSize: 800 }
                }
            }
        }
    });

    /* =========================================
       GRÁFICO DE ROSCA (Distribuição)
       ========================================= */
    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
    
    // Tons de dourado extraídos da imagem para as fatias
    const doughnutColors = [
        '#d4af37', // T-A1 (Claro)
        '#aa8529', // T-A2
        '#8a6b22', // T-B3
        '#6b521b', // T-C1
        '#4b3a14', // T-C3
        '#2e240c'  // Outros (Mais escuro)
    ];
    
    const doughnutLabels = [
        'T-A1 Entrada', 
        'T-A2 Galeria', 
        'T-B3 Café', 
        'T-C1 Jardim', 
        'T-C3 Linha Tempo', 
        'Outros'
    ];
    
    const doughnutData = [35, 20, 15, 10, 10, 10]; // Porcentagens ilustrativas

    new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: doughnutLabels,
            datasets: [{
                data: doughnutData,
                backgroundColor: doughnutColors,
                borderWidth: 1,
                borderColor: '#161b22', // Borda igual ao fundo para separar fatias
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%', // Espessura fina da rosca
            plugins: {
                legend: { display: false }, // Esconde a legenda padrão
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });

    /* =========================================
    GERAR LEGENDA CUSTOMIZADA
       ========================================= */
    const legendContainer = document.getElementById('chartLegend');
    
    // Gerar na mesma ordem/estilo da imagem
    const legendItems = [
        { label: 'Outros', color: doughnutColors[5] },
        { label: 'T-A1 Entrada', color: doughnutColors[0] },
        { label: 'T-A2 Galeria', color: doughnutColors[1] },
        { label: 'T-B3 Café', color: doughnutColors[2] },
        { label: 'T-C1 Jardim', color: doughnutColors[3] },
        { label: 'T-C3 Linha Tempo', color: doughnutColors[4] }
    ];

    legendItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'legend-item';
        div.innerHTML = `<span class="legend-dot" style="background-color: ${item.color};"></span> ${item.label}`;
        legendContainer.appendChild(div);
    });
</script>
@stop