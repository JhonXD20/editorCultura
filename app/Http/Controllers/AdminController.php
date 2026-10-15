<?php

namespace App\Http\Controllers;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Exibe a visão geral do sistema.
     */
    public function index(): View
    {
        $agora = Carbon::now();

        // Equipamentos cadastrados
        $totens = Totem::orderBy('id')->get();

        // Indicadores de monitoramento
        $monitoramento = $this->calcularMonitoramento($totens);

        $totalTotens = $monitoramento['total'];
        $totalOnline = $monitoramento['online'];
        $totalOffline = $monitoramento['offline'];

        // Indicadores de interações
        $interacoes = $this->calcularInteracoes($agora);

        $interacoesHoje = $interacoes['hoje'];
        $interacoesOntem = $interacoes['ontem'];
        $variacaoInteracoes = $interacoes['variacao'];

        // Gráficos
        $graficos = [
            'horarios' => $this->graficoPorHorario($agora),
            'semana' => $this->graficoSemanal($agora),
        ];

        // Conteúdos mais visualizados
        $conteudosMaisAcessados =
            $this->buscarConteudosMaisAcessados($agora);

        // Alertas de conectividade
        $alertas = $this->buscarAlertas($totens);

        return view('admin', compact(
            'agora',
            'totalTotens',
            'totalOnline',
            'totalOffline',
            'interacoesHoje',
            'interacoesOntem',
            'variacaoInteracoes',
            'graficos',
            'conteudosMaisAcessados',
            'alertas'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | 1. MONITORAMENTO DOS TOTENS
    |--------------------------------------------------------------------------
    */

    private function calcularMonitoramento(
        Collection $totens
    ): array {
        $total = $totens->count();

        $online = $totens->filter(
            fn (Totem $totem): bool => $totem->status_monitoramento === 'online'
        )->count();

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $total - $online,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 2. INTERAÇÕES DE HOJE E ONTEM
    |--------------------------------------------------------------------------
    */

    private function calcularInteracoes(
        Carbon $agora
    ): array {
        $inicioHoje = $agora->copy()->startOfDay();

        $inicioOntem = $agora->copy()
            ->subDay()
            ->startOfDay();

        $fimOntem = $agora->copy()->subDay();

        $hoje = InteracaoTotem::query()
            ->where('tipo', 'toque')
            ->whereBetween(
                'created_at',
                [$inicioHoje, $agora]
            )
            ->count();

        $ontem = InteracaoTotem::query()
            ->where('tipo', 'toque')
            ->whereBetween(
                'created_at',
                [$inicioOntem, $fimOntem]
            )
            ->count();

        $variacao = $ontem > 0
            ? (($hoje - $ontem) / $ontem) * 100
            : null;

        return [
            'hoje' => $hoje,
            'ontem' => $ontem,
            'variacao' => $variacao,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 3. GRÁFICO DE INTERAÇÕES POR HORÁRIO
    |--------------------------------------------------------------------------
    */

    private function graficoPorHorario(
        Carbon $agora
    ): array {
        $inicio = $agora->copy()->startOfDay();
        $expressaoHora = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', created_at) AS INTEGER)"
            : 'HOUR(created_at)';

        $registros = DB::table('interacoes_totem')
            ->selectRaw(
                "{$expressaoHora} AS hora,
                 COUNT(*) AS total"
            )
            ->where('tipo', 'toque')
            ->whereBetween(
                'created_at',
                [$inicio, $agora]
            )
            ->groupByRaw($expressaoHora)
            ->pluck('total', 'hora');

        $labels = [];
        $valores = [];

        for ($hora = 0; $hora < 24; $hora++) {
            $labels[] = sprintf('%02dh', $hora);

            $valores[] = (int) (
                $registros[$hora] ?? 0
            );
        }

        return [
            'labels' => $labels,
            'valores' => $valores,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 4. GRÁFICO SEMANAL
    |--------------------------------------------------------------------------
    */

    private function graficoSemanal(
        Carbon $agora
    ): array {
        $inicioSemana = $agora->copy()
            ->startOfWeek(Carbon::MONDAY);

        $registros = DB::table('interacoes_totem')
            ->selectRaw(
                'DATE(created_at) AS dia,
                 COUNT(*) AS total'
            )
            ->where('tipo', 'toque')
            ->whereBetween(
                'created_at',
                [$inicioSemana, $agora]
            )
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'dia');

        $nomesDias = [
            'Segunda',
            'Terça',
            'Quarta',
            'Quinta',
            'Sexta',
            'Sábado',
            'Domingo',
        ];

        $labels = [];
        $valores = [];

        for ($indice = 0; $indice < 7; $indice++) {
            $dia = $inicioSemana->copy()
                ->addDays($indice);

            $chave = $dia->format('Y-m-d');

            $labels[] = $nomesDias[$indice];

            $valores[] = (int) (
                $registros[$chave] ?? 0
            );
        }

        return [
            'labels' => $labels,
            'valores' => $valores,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 5. CONTEÚDOS MAIS ACESSADOS
    |--------------------------------------------------------------------------
    */

    private function buscarConteudosMaisAcessados(
        Carbon $agora
    ): Collection {
        $inicioSemana = $agora->copy()
            ->startOfWeek(Carbon::MONDAY);

        $registros = InteracaoTotem::query()
            ->where('tipo', 'tempo_pagina')
            ->whereNotNull('pagina_id')
            ->whereBetween(
                'created_at',
                [$inicioSemana, $agora]
            )
            ->select('pagina_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('pagina_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($registros->isEmpty()) {
            return collect();
        }

        $paginas = Pagina::whereIn(
            'id',
            $registros->pluck('pagina_id')
        )
            ->get()
            ->keyBy('id');

        $maiorQuantidade = (int) (
            $registros->first()?->total ?? 0
        );

        return $registros->map(
            function ($registro) use (
                $paginas,
                $maiorQuantidade
            ): array {
                $pagina = $paginas->get(
                    $registro->pagina_id
                );

                $nome =
                    $pagina?->getAttribute('pagina_titulo')
                    ?: $pagina?->getAttribute('pagina_nome')
                    ?: $pagina?->getAttribute('titulo')
                    ?: 'Página #'.$registro->pagina_id;

                $total = (int) $registro->total;

                $percentual = $maiorQuantidade > 0
                    ? ($total / $maiorQuantidade) * 100
                    : 0;

                return [
                    'id' => $registro->pagina_id,
                    'nome' => $nome,
                    'total' => $total,
                    'percentual' => $percentual,
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 6. ALERTAS DOS TOTENS
    |--------------------------------------------------------------------------
    */

    private function buscarAlertas(
        Collection $totens
    ): Collection {
        $offline = $totens->filter(
            fn (Totem $totem): bool => $totem->status_monitoramento === 'offline'
        );

        return $offline
            ->sortBy(
                fn (Totem $totem): int => $totem->totem_ping?->timestamp ?? 0
            )
            ->take(5)
            ->map(
                function (Totem $totem): array {
                    $mensagem = $totem->totem_ping
                        ? 'Último contato '.
                        $totem->totem_ping->diffForHumans()
                        : 'Nenhuma comunicação registrada';

                    return [
                        'id' => $totem->id,

                        'nome' => $totem->totem_nome_identificacao,

                        'localizacao' => $totem->totem_localizacao
                            ?: 'Localização não informada',

                        'mensagem' => $mensagem,
                    ];
                }
            )
            ->values();
    }
}
