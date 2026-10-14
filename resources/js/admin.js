
/* ==========================================
   DASHBOARD - EDITOR CULTURA
========================================== */

import Chart from 'chart.js/auto';

import '../css/admin.css';


document.addEventListener('DOMContentLoaded', () => {

    const dashboard = document.getElementById(
        'adminOverview'
    );

    if (!dashboard) return;

    /* =====================================
       1. DADOS DO CONTROLLER
    ===================================== */

    const elementoDados = document.getElementById(
        'dadosDashboard'
    );

    if (!elementoDados) {
        console.error(
            'Dados do dashboard não encontrados.'
        );
        return;
    }

    let dados;

    try {
        dados = JSON.parse(
            elementoDados.textContent
        );

    } catch (erro) {
        console.error(
            'Erro ao carregar dados dos gráficos:',
            erro
        );

        return;
    }

    /* =====================================
       2. CORES E CONFIGURAÇÕES
    ===================================== */

    const corDourada = '#cba258';

    const corGrade = 'rgba(139, 148, 158, 0.12)';

    const corTexto = '#64748b';

    function formatarNumero(valor) {
        return Number(valor).toLocaleString('pt-BR');
    }

    Chart.defaults.font.family =
        'Arial, sans-serif';

    Chart.defaults.color = corTexto;

    /* =====================================
       3. FLUXO DE USO POR HORÁRIO
    ===================================== */

    const canvasHorario = document.getElementById(
        'graficoFluxoHorario'
    );

    const horarios = dados.horarios || {
        labels: [],
        valores: [],
    };

    if (canvasHorario) {

        new Chart(canvasHorario, {

            type: 'line',

            data: {

                labels: horarios.labels,

                datasets: [{

                    label: 'Interações',

                    data: horarios.valores,

                    borderColor: corDourada,

                    backgroundColor:
                        'rgba(203, 162, 88, 0.12)',

                    borderWidth: 2,

                    pointRadius: 2,

                    pointHoverRadius: 5,

                    pointBackgroundColor: corDourada,

                    tension: 0.35,

                    fill: true,

                }],
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index',
                },

                plugins: {

                    legend: {
                        display: false,
                    },

                    tooltip: {

                        callbacks: {

                            label(contexto) {

                                return (
                                    'Interações: ' +
                                    formatarNumero(
                                        contexto.parsed.y
                                    )
                                );
                            },
                        },
                    },
                },

                scales: {

                    x: {

                        grid: {
                            display: false,
                        },

                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 12,
                            maxRotation: 0,
                        },
                    },

                    y: {

                        beginAtZero: true,

                        grid: {
                            color: corGrade,
                        },

                        ticks: {

                            precision: 0,

                            callback(valor) {
                                return formatarNumero(valor);
                            },
                        },
                    },
                },
            },
        });
    }

    /* =====================================
       4. INTERAÇÕES POR DIA
    ===================================== */

    const canvasSemana = document.getElementById(
        'graficoInteracoesSemana'
    );

    const semana = dados.semana || {
        labels: [],
        valores: [],
    };

    if (canvasSemana) {

        new Chart(canvasSemana, {

            type: 'bar',

            data: {

                labels: semana.labels,

                datasets: [{

                    label: 'Interações',

                    data: semana.valores,

                    backgroundColor: corDourada,

                    hoverBackgroundColor: '#b58b45',

                    borderRadius: 4,

                    maxBarThickness: 52,

                    barPercentage: 0.65,

                    categoryPercentage: 0.8,

                }],
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        display: false,
                    },

                    tooltip: {

                        callbacks: {

                            label(contexto) {

                                return (
                                    'Interações: ' +
                                    formatarNumero(
                                        contexto.parsed.y
                                    )
                                );
                            },
                        },
                    },
                },

                scales: {

                    x: {

                        grid: {
                            display: false,
                        },

                        ticks: {
                            maxRotation: 0,
                        },
                    },

                    y: {

                        beginAtZero: true,

                        grid: {
                            color: corGrade,
                        },

                        ticks: {

                            precision: 0,

                            callback(valor) {
                                return formatarNumero(valor);
                            },
                        },
                    },
                },
            },
        });
    }

    /* =====================================
       5. ATUALIZAÇÃO MANUAL
    ===================================== */

    const btnAtualizar = document.getElementById(
        'btnAtualizarDashboard'
    );

    btnAtualizar?.addEventListener('click', () => {

        window.location.reload();

    });

    /* =====================================
       6. ATUALIZAÇÃO AUTOMÁTICA
    ===================================== */

    const intervaloAtualizacao = 60000;

    setInterval(() => {

        // Evita recarregar quando
        // o usuário estiver em outra aba.
        if (document.visibilityState === 'visible') {

            window.location.reload();

        }

    }, intervaloAtualizacao);

});
