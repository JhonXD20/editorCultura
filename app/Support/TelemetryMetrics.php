<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TelemetryMetrics
{
    public static function query(Carbon $start, Carbon $end, ?int $totemId = null): Builder
    {
        $time = 'COALESCE(ocorrido_em, created_at)';
        $hour = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', {$time}) AS INTEGER)" : "HOUR({$time})";
        $raw = DB::table('interacoes_totem')
            ->selectRaw("totem_id, pagina_id, tipo, DATE({$time}) AS dia, {$hour} AS hora, 1 AS quantidade,
                COALESCE(duracao_segundos, 0) AS duracao,
                CASE WHEN duracao_segundos IS NULL THEN 0 ELSE 1 END AS amostras_duracao")
            ->whereRaw("{$time} BETWEEN ? AND ?", [$start, $end]);
        $daily = DB::table('metricas_diarias')
            ->selectRaw('totem_id, pagina_id, tipo, data_metrica AS dia, NULL AS hora, quantidade_eventos AS quantidade,
                duracao_segundos AS duracao,
                CASE WHEN tipo = ? THEN quantidade_eventos ELSE 0 END AS amostras_duracao', ['tempo_pagina'])
            ->whereBetween('data_metrica', [$start->toDateString(), $end->toDateString()]);
        if ($totemId !== null) {
            $raw->where('totem_id', $totemId);
            $daily->where('totem_id', $totemId);
        }

        return DB::query()->fromSub($raw->unionAll($daily), 'metricas');
    }
}
