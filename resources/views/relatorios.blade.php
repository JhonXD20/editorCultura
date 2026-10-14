
@extends('layouts.template')

@section('page_title', 'Relatórios de Uso - Editor Cultura')
@section('header_title', 'Relatórios de Uso')
@section('header_subtitle', 'Análise de engajamento e comportamento de público')

@section('css_custom')
    @vite('resources/css/relatorios.css')
@endsection

@section('conteudo')

<div class="dashboard-theme relatorios-page pb-4">

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
            </button>
            <h5><i class="icon fas fa-ban" aria-hidden="true"></i> Não foi possível aplicar os filtros</h5>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ==========================================
         FILTROS DO RELATÓRIO
    ========================================== --}}

    <div class="d-flex justify-content-end mb-4">

        <form
            action="{{ route('relatorios') }}"
            method="GET"
            class="dashboard-card relatorios-filtros"
        >

            {{-- PERÍODO INICIAL --}}
            <div class="relatorios-campo-data">

                <label for="periodo_inicial">
                    PERÍODO INICIAL
                </label>

                <input
                    type="date"
                    id="periodo_inicial"
                    name="periodo_inicial"
                    class="form-control custom-input form-control-sm"
                    value="{{ $inicio->format('Y-m-d') }}"
                    required
                >

            </div>


            {{-- PERÍODO FINAL --}}
            <div class="relatorios-campo-data">

                <label for="periodo_final">
                    PERÍODO FINAL
                </label>

                <input
                    type="date"
                    id="periodo_final"
                    name="periodo_final"
                    class="form-control custom-input form-control-sm"
                    value="{{ $fim->format('Y-m-d') }}"
                    required
                >

            </div>


            {{-- DISPOSITIVO --}}
            <div class="relatorios-campo-totem">

                <label for="filtroTotem">
                    DISPOSITIVO
                </label>

                <select
                    id="filtroTotem"
                    name="totem_id"
                    class="form-control custom-input form-control-sm"
                >

                    <option value="">
                        Todos os Totens
                    </option>

                    @foreach ($totens as $totem)

                        <option
                            value="{{ $totem->id }}"
                            @selected(
                                (string) $totemId === (string) $totem->id
                            )
                        >
                            {{ $totem->totem_nome_identificacao }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- BOTÃO APLICAR --}}
            <div class="relatorios-filtro-acao">

                <button
                    type="submit"
                    class="btn btn-gold btn-sm relatorios-btn-aplicar"
                >
                    <i class="fas fa-filter"></i>
                    Aplicar
                </button>

            </div>

        </form>

    </div>


    {{-- ==========================================
         INDICADORES
    ========================================== --}}

    <div class="row g-3 mb-4">

        {{-- TOTAL DE INTERAÇÕES --}}
        <div class="col-12 col-md-6 col-xl-3">

            <div class="dashboard-card relatorios-kpi">

                <div class="text-muted-custom mb-2">
                    TOTAL DE INTERAÇÕES
                </div>

                <div class="kpi-value font-serif text-gold">
                    {{ number_format($totalInteracoes, 0, ',', '.') }}
                </div>

                <div class="relatorios-kpi-descricao">
                    no período selecionado
                </div>

            </div>

        </div>


        {{-- MÉDIA DIÁRIA --}}
        <div class="col-12 col-md-6 col-xl-3">

            <div class="dashboard-card relatorios-kpi">

                <div class="text-muted-custom mb-2">
                    MÉDIA DIÁRIA
                </div>

                <div class="kpi-value font-serif">
                    {{ number_format($mediaDiaria, 1, ',', '.') }}
                </div>

                <div class="relatorios-kpi-descricao">
                    toques por dia
                </div>

            </div>

        </div>


        {{-- PÁGINA COM MAIOR PERMANÊNCIA --}}
        <div class="col-12 col-md-6 col-xl-3">

            <div class="dashboard-card relatorios-kpi">

                <div class="text-muted-custom mb-2">
                    PÁGINA COM MAIOR PERMANÊNCIA
                </div>

                <div class="kpi-value font-serif">

                    @if ($mediaRetencaoSegundos !== null)

                        {{ $mediaRetencaoSegundos }}s

                    @else

                        —

                    @endif

                </div>

                <div class="relatorios-kpi-descricao">
                    {{ $nomePaginaRetencao ?? 'Sem dados de permanência' }}
                </div>

            </div>

        </div>


        {{-- PICO DE USO --}}
        <div class="col-12 col-md-6 col-xl-3">

            <div class="dashboard-card relatorios-kpi">

                <div class="text-muted-custom mb-2">
                    PICO DE USO
                </div>

                <div class="kpi-value font-serif">
                    {{ $picoHora ?? '—' }}
                </div>

                <div class="relatorios-kpi-descricao">

                    @if ($picoHora)

                        {{ number_format($picoQuantidade, 0, ',', '.') }}
                        toques nessa faixa horária

                    @else

                        Sem interações registradas

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- ==========================================
         GRÁFICOS
    ========================================== --}}

    <div class="row g-3">

        {{-- GRÁFICO DE LINHA --}}
        <div class="col-12 col-lg-7">

            <div class="section-title mt-0">
                TOQUES POR DIA
            </div>

            <div class="dashboard-card relatorios-grafico-card">

                <div class="relatorios-grafico-linha">

                    <canvas
                        id="lineChart"
                        aria-label="Gráfico de toques por dia"
                        role="img"
                    ></canvas>

                </div>

            </div>

        </div>


        {{-- GRÁFICO DE ROSCA --}}
        <div class="col-12 col-lg-5">

            <div class="section-title mt-0">
                DISTRIBUIÇÃO POR TOTEM (%)
            </div>

            <div class="dashboard-card relatorios-grafico-card">

                <div
                    class="relatorios-grafico-rosca"
                    id="containerGraficoRosca"
                >

                    <canvas
                        id="doughnutChart"
                        aria-label="Gráfico de distribuição por totem"
                        role="img"
                    ></canvas>

                </div>


                {{-- MENSAGEM QUANDO NÃO EXISTEM DADOS --}}
                <p
                    id="semDadosRosca"
                    class="relatorios-sem-dados"
                    hidden
                >
                    Nenhuma interação registrada no período.
                </p>


                {{-- LEGENDA GERADA PELO JAVASCRIPT --}}
                <div
                    id="chartLegend"
                    class="relatorios-legenda"
                ></div>

            </div>

        </div>

    </div>


    {{-- ==========================================
         OBSERVAÇÃO
    ========================================== --}}

    <div class="relatorios-observacao">

        <i class="fas fa-info-circle"></i>

        <span>
            Os indicadores utilizam os eventos registrados
            na exibição pública. O tempo de permanência
            corresponde ao período em que a página
            ficou visível.
        </span>

    </div>

</div>


{{-- ==========================================
     DADOS DOS GRÁFICOS
========================================== --}}

<script
    id="relatoriosDados"
    type="application/json"
>
{!! json_encode(
    $graficos,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) !!}
</script>


{{-- JAVASCRIPT DOS RELATÓRIOS --}}
@vite('resources/js/relatorios.js')

@endsection
