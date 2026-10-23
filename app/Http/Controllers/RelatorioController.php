<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Models\Totem;
use App\Support\TelemetryMetrics;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RelatorioController extends Controller
{
    /**
     * Exibe os relatórios de uso dos totens.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. VALIDAÇÃO DOS FILTROS
        |--------------------------------------------------------------------------
        */

        $dados = $request->validate([

            'periodo_inicial' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'periodo_final' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'totem_id' => [
                'nullable',
                'integer',
                'exists:totens,id',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. DEFINIÇÃO DO PERÍODO
        |--------------------------------------------------------------------------
        |
        | Padrão: últimos 7 dias.
        |
        */

        $inicio = Carbon::parse(
            $dados['periodo_inicial']
                ?? now()->subDays(6)->toDateString()
        )->startOfDay();

        $fim = Carbon::parse(
            $dados['periodo_final']
                ?? now()->toDateString()
        )->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | 3. VALIDAÇÃO DO INTERVALO
        |--------------------------------------------------------------------------
        */

        if ($inicio->greaterThan($fim)) {

            throw ValidationException::withMessages([
                'periodo_final' => 'O período final deve ser maior ou igual ao período inicial.',

            ]);
        }

        // Quantidade de dias, incluindo a data inicial e a final
        $quantidadeDias = (int) $inicio->copy()
            ->startOfDay()
            ->diffInDays(
                $fim->copy()->startOfDay()
            ) + 1;

        // Limite de 366 dias para evitar consultas excessivas
        if ($quantidadeDias > 366) {

            throw ValidationException::withMessages([
                'periodo_inicial' => 'Selecione um período de até 366 dias.',

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. FILTRO POR TOTEM
        |--------------------------------------------------------------------------
        */

        $totemId = $dados['totem_id'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 5. CONSULTA BASE
        |--------------------------------------------------------------------------
        |
        | Todas as métricas utilizarão o mesmo período
        | e o mesmo filtro de dispositivo.
        |
        */

        $base = TelemetryMetrics::query($inicio, $fim, $totemId === null ? null : (int) $totemId);

        /*
        |--------------------------------------------------------------------------
        | 6. CONSULTA DE TOQUES
        |--------------------------------------------------------------------------
        */

        $toques = (clone $base)
            ->where('tipo', 'toque');

        /*
        |--------------------------------------------------------------------------
        | 7. TOTAL DE INTERAÇÕES
        |--------------------------------------------------------------------------
        */

        $totalInteracoes = (int) (clone $toques)->sum('quantidade');

        /*
        |--------------------------------------------------------------------------
        | 8. MÉDIA DIÁRIA
        |--------------------------------------------------------------------------
        |
        | Considera também os dias sem interações.
        |
        */

        $mediaDiaria = $quantidadeDias > 0
            ? $totalInteracoes / $quantidadeDias
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 9. GRÁFICO: TOQUES POR DIA
        |--------------------------------------------------------------------------
        */

        $contagens = (clone $toques)
            ->selectRaw(
                'dia, SUM(quantidade) AS total'
            )
            ->groupBy('dia')
            ->pluck('total', 'dia')
            ->toArray();

        $labelsDias = [];
        $valoresDias = [];

        $periodo = CarbonPeriod::create(
            $inicio->copy()->startOfDay(),
            $fim->copy()->startOfDay()
        );

        foreach ($periodo as $dia) {

            $chave = $dia->format('Y-m-d');

            $labelsDias[] = $dia->format('d/m');

            $valoresDias[] = (int) (
                $contagens[$chave] ?? 0
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. PICO DE USO
        |--------------------------------------------------------------------------
        |
        | Identifica a faixa horária que registrou
        | a maior quantidade de interações.
        |
        */

        $picoHistoricoIncompleto = (clone $toques)->whereNull('hora')->exists();
        $pico = $picoHistoricoIncompleto ? null : (clone $toques)
            ->selectRaw(
                'hora, SUM(quantidade) AS total'
            )
            ->groupBy('hora')
            ->orderByDesc('total')
            ->orderBy('hora')
            ->first();

        $picoHora = $pico
            ? sprintf('%02d:00', (int) $pico->hora)
            : null;

        $picoQuantidade = $pico
            ? (int) $pico->total
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 11. PÁGINA COM MAIOR PERMANÊNCIA
        |--------------------------------------------------------------------------
        |
        | Calcula o tempo médio registrado por página.
        |
        */

        $retencao = (clone $base)
            ->where('tipo', 'tempo_pagina')
            ->where('amostras_duracao', '>', 0)
            ->selectRaw(
                'pagina_id,
                  ROUND(1.0 * SUM(duracao) / SUM(amostras_duracao)) AS media_segundos'
            )
            ->groupBy('pagina_id')
            ->orderByDesc('media_segundos')
            ->first();

        $nomePaginaRetencao = null;
        $mediaRetencaoSegundos = null;

        if ($retencao) {

            $paginaRetencao = Pagina::find(
                $retencao->pagina_id
            );

            $nomePaginaRetencao =
                $paginaRetencao?->getAttribute('pagina_titulo')
                ?: $paginaRetencao?->getAttribute('pagina_nome')
                ?: $paginaRetencao?->getAttribute('titulo')
                ?: 'Página #'.$retencao->pagina_id;

            $mediaRetencaoSegundos = (int) (
                $retencao->media_segundos
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 12. GRÁFICO: DISTRIBUIÇÃO POR TOTEM
        |--------------------------------------------------------------------------
        */

        $distribuicao = (clone $toques)
            ->leftJoin(
                'totens',
                'metricas.totem_id',
                '=',
                'totens.id'
            )
            ->selectRaw(
                "COALESCE(
                    totens.totem_nome_identificacao,
                    'Não identificado'
                ) AS nome,
                SUM(quantidade) AS total"
            )
            ->groupBy(
                'totens.id',
                'totens.totem_nome_identificacao'
            )
            ->orderByDesc('total')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 13. PREPARAÇÃO DOS GRÁFICOS
        |--------------------------------------------------------------------------
        |
        | Os dados serão enviados em JSON
        | para o relatorios.js.
        |
        */

        $graficos = [

            'linha' => [

                'labels' => $labelsDias,

                'valores' => $valoresDias,

            ],

            'rosca' => [

                'labels' => $distribuicao
                    ->pluck('nome')
                    ->values()
                    ->all(),

                'valores' => $distribuicao
                    ->pluck('total')
                    ->map(
                        fn ($total) => (int) $total
                    )
                    ->values()
                    ->all(),

            ],

        ];

        /*
        |--------------------------------------------------------------------------
        | 14. LISTAGEM DE TOTENS PARA O FILTRO
        |--------------------------------------------------------------------------
        */

        $totens = Totem::orderBy(
            'totem_nome_identificacao',
            'asc'
        )->get();

        /*
        |--------------------------------------------------------------------------
        | 15. RETORNO PARA A BLADE
        |--------------------------------------------------------------------------
        */

        return view('relatorios', compact(

            'inicio',
            'fim',
            'totemId',
            'totens',

            'totalInteracoes',
            'mediaDiaria',

            'picoHora',
            'picoQuantidade',
            'picoHistoricoIncompleto',

            'nomePaginaRetencao',
            'mediaRetencaoSegundos',

            'graficos'

        ));
    }
}
