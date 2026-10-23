
import Chart from 'chart.js/auto';
import { onAdminPage } from './admin-lifecycle';

onAdminPage(() => {

    const elementoDados = document.getElementById(
        'relatoriosDados'
    );

    const canvasLinha = document.getElementById(
        'lineChart'
    );

    const canvasRosca = document.getElementById(
        'doughnutChart'
    );

    if (
        !elementoDados ||
        !canvasLinha ||
        !canvasRosca
    ) {
        return;
    }

    /* =====================================
       1. DADOS DO LARAVEL
    ===================================== */

    let dados;

    try {
        dados = JSON.parse(
            elementoDados.textContent
        );
    } catch (erro) {
        console.error(
            'Erro ao interpretar dados dos relatórios:',
            erro
        );

        return;
    }

    const linha = dados.linha || {
        labels: [],
        valores: [],
    };

    const rosca = dados.rosca || {
        labels: [],
        valores: [],
    };

    /* =====================================
       2. CONFIGURAÇÕES GLOBAIS
    ===================================== */

    Chart.defaults.color = '#8b949e';

    Chart.defaults.font.family =
        'Arial, sans-serif';

    const corDourada = '#cba258';

    const corGrade =
        'rgba(139, 148, 158, 0.12)';

    const coresRosca = [
        '#d4af37',
        '#aa8529',
        '#8a6b22',
        '#6b521b',
        '#4b3a14',
        '#2e240c',
        '#d9b97a',
        '#997d44',
        '#b69c62',
    ];

    function obterCor(indice) {
        return coresRosca[
            indice % coresRosca.length
        ];
    }

    function formatarNumero(valor) {
        return Number(valor).toLocaleString(
            'pt-BR'
        );
    }

    /* =====================================
       3. GRÁFICO DE LINHA
    ===================================== */

    new Chart(canvasLinha, {

        type: 'line',

        data: {
            labels: linha.labels,

            datasets: [{
                label: 'Toques',

                data: linha.valores,

                borderColor: corDourada,

                backgroundColor: corDourada,

                borderWidth: 2,

                pointBackgroundColor: corDourada,

                pointBorderColor: '#161b22',

                pointBorderWidth: 2,

                pointRadius: 4,

                pointHoverRadius: 6,

                tension: 0.35,

                fill: false,
            }],
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false,
            },

            plugins: {
                legend: {
                    display: false,
                },

                tooltip: {
                    callbacks: {
                        label(contexto) {
                            return (
                                'Toques: ' +
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
                        autoSkip: true,
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

    /* =====================================
       4. GRÁFICO DE ROSCA
    ===================================== */

    const labelsRosca = rosca.labels || [];

    const valoresRosca = (rosca.valores || [])
        .map(Number);

    const totalRosca = valoresRosca.reduce(
        (total, valor) => total + valor,
        0
    );

    const elementoSemDados = document.getElementById(
        'semDadosRosca'
    );

    const containerRosca = document.querySelector(
        '.relatorios-grafico-rosca'
    );

    const legenda = document.getElementById(
        'chartLegend'
    );

    if (totalRosca <= 0) {

        if (containerRosca) {
            containerRosca.hidden = true;
        }

        if (elementoSemDados) {
            elementoSemDados.hidden = false;
        }

        return;
    }

    const coresAtuais = labelsRosca.map(
        (_, indice) => obterCor(indice)
    );

    new Chart(canvasRosca, {

        type: 'doughnut',

        data: {
            labels: labelsRosca,

            datasets: [{
                data: valoresRosca,

                backgroundColor: coresAtuais,

                borderColor: '#ffffff',

                borderWidth: 2,

                hoverOffset: 5,
            }],
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            cutout: '70%',

            plugins: {
                legend: {
                    display: false,
                },

                tooltip: {
                    callbacks: {
                        label(contexto) {

                            const quantidade = Number(
                                contexto.raw
                            );

                            const porcentagem =
                                totalRosca > 0
                                    ? quantidade / totalRosca * 100
                                    : 0;

                            return (
                                contexto.label +
                                ': ' +
                                formatarNumero(quantidade) +
                                ' toques (' +
                                porcentagem.toFixed(1) +
                                '%)'
                            );
                        },
                    },
                },
            },
        },
    });

    /* =====================================
       5. LEGENDA PERSONALIZADA
    ===================================== */

    if (legenda) {

        legenda.replaceChildren();

        labelsRosca.forEach((nome, indice) => {

            const item = document.createElement(
                'div'
            );

            item.className =
                'relatorios-legenda-item';

            const ponto = document.createElement(
                'span'
            );

            ponto.className =
                'relatorios-legenda-cor';

            ponto.style.backgroundColor =
                coresAtuais[indice];

            const texto = document.createElement(
                'span'
            );

            const quantidade =
                valoresRosca[indice] || 0;

            const porcentagem =
                quantidade / totalRosca * 100;

            texto.textContent =
                `${nome} (${porcentagem.toFixed(1)}%)`;

            item.appendChild(ponto);
            item.appendChild(texto);

            legenda.appendChild(item);
        });
    }

    return () => {
        [canvasLinha, canvasRosca].forEach((canvas) => Chart.getChart(canvas)?.destroy());
    };
});
