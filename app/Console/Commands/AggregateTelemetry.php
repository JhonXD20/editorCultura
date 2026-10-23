<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateTelemetry extends Command
{
    protected $signature = 'telemetry:aggregate';

    protected $description = 'Consolida eventos anteriores a 24 meses e remove apenas os eventos consolidados';

    public function handle(): int
    {
        $cutoff = now()->subMonthsNoOverflow(24)->startOfDay();
        $aggregated = 0;
        while (true) {
            $processed = DB::transaction(function () use ($cutoff): int {
                $events = DB::table('interacoes_totem')->whereRaw('COALESCE(ocorrido_em, created_at) < ?', [$cutoff])
                    ->orderBy('id')->limit(1000)->lockForUpdate()->get();
                foreach ($events->groupBy(fn ($event) => substr($event->ocorrido_em ?? $event->created_at, 0, 10).'|'.($event->totem_id ?? 'null').'|'.($event->pagina_id ?? 'null').'|'.$event->tipo) as $group => $rows) {
                    $sample = $rows->first();
                    $key = hash('sha256', $group);
                    $existing = DB::table('metricas_diarias')->where('chave_agregacao', $key)->lockForUpdate()->first();
                    $values = [
                        'chave_agregacao' => $key,
                        'data_metrica' => substr($sample->ocorrido_em ?? $sample->created_at, 0, 10),
                        'totem_id' => $sample->totem_id,
                        'pagina_id' => $sample->pagina_id,
                        'tipo' => $sample->tipo,
                        'quantidade_eventos' => ($existing->quantidade_eventos ?? 0) + $rows->count(),
                        'duracao_segundos' => ($existing->duracao_segundos ?? 0) + $rows->sum('duracao_segundos'),
                        'updated_at' => now(),
                    ];
                    if ($existing) {
                        DB::table('metricas_diarias')->where('id', $existing->id)->update($values);
                    } else {
                        DB::table('metricas_diarias')->insert($values + ['created_at' => now()]);
                    }
                }
                DB::table('interacoes_totem')->whereIn('id', $events->pluck('id'))->delete();

                return $events->count();
            }, 3);
            $aggregated += $processed;
            if ($processed === 0) {
                break;
            }
        }
        $this->info("{$aggregated} evento(s) consolidado(s).");

        return self::SUCCESS;
    }
}
