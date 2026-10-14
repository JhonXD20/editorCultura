<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InteracoesTotemSeeder extends Seeder
{
    public function run(): void
    {
        // Evita criar dados fictícios em produção.
        if (app()->environment('production')) {
            $this->command?->warn(
                'Seeder de testes bloqueado em produção.'
            );
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFICAR AS TABELAS
        |--------------------------------------------------------------------------
        */

        foreach (['totens', 'paginas', 'interacoes_totem'] as $tabela) {
            if (!Schema::hasTable($tabela)) {
                $this->command?->error(
                    "A tabela {$tabela} não existe."
                );
                return;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR TOTENS E PÁGINAS EXISTENTES
        |--------------------------------------------------------------------------
        */

        $totemIds = DB::table('totens')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $paginaIds = DB::table('paginas')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (empty($totemIds)) {
            $this->command?->warn(
                'Cadastre pelo menos um totem antes de executar.'
            );
            return;
        }

        if (empty($paginaIds)) {
            $this->command?->warn(
                'Cadastre pelo menos uma página antes de executar.'
            );
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CONFIGURAÇÕES DA SIMULAÇÃO
        |--------------------------------------------------------------------------
        |
        | - Últimos 7 dias
        | - Toques em diferentes horários
        | - Tempos variados de permanência
        | - Distribuição entre os totens cadastrados
        |
        */

        $faker = fake('pt_BR');

        $agora = now();

        $registros = [];

        $totalToques = 0;
        $totalPermanencias = 0;

        /*
        |--------------------------------------------------------------------------
        | DISTRIBUIÇÃO DOS EQUIPAMENTOS
        |--------------------------------------------------------------------------
        |
        | Alguns totens recebem mais interações
        | que outros para testar o gráfico de rosca.
        |
        */

        $totensPonderados = [];

        $quantidadeTotens = count($totemIds);

        foreach ($totemIds as $indice => $id) {

            $peso = max(
                1,
                $quantidadeTotens - $indice
            );

            for ($i = 0; $i < $peso; $i++) {
                $totensPonderados[] = $id;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GERAR DADOS DOS ÚLTIMOS 7 DIAS
        |--------------------------------------------------------------------------
        */

        for ($diasAtras = 6; $diasAtras >= 0; $diasAtras--) {

            $dia = $agora->copy()
                ->subDays($diasAtras)
                ->startOfDay();

            // Simula horário de visitação
            $inicioDia = $dia->copy()->setTime(8, 0, 0);
            $fimDia = $dia->copy()->setTime(18, 59, 59);

            // Não gera eventos no futuro.
            if ($fimDia->greaterThan($agora)) {
                $fimDia = $agora->copy();
            }

            if ($inicioDia->greaterThan($fimDia)) {
                continue;
            }

            $segundosDisponiveis = (int)
            $inicioDia->diffInSeconds($fimDia);

            /*
            |--------------------------------------------------------------------------
            | TOQUES DO DIA
            |--------------------------------------------------------------------------
            */

            $quantidadeToques = $faker->numberBetween(
                70,
                140
            );

            // Simula maior movimento nos fins de semana.
            if ($dia->isWeekend()) {
                $quantidadeToques = (int) round(
                    $quantidadeToques * 1.3
                );
            }

            for ($i = 0; $i < $quantidadeToques; $i++) {

                $momento = $inicioDia->copy()
                    ->addSeconds(
                        $faker->numberBetween(
                            0,
                            $segundosDisponiveis
                        )
                    );

                $registros[] = [
                    'totem_id' => $faker->randomElement(
                        $totensPonderados
                    ),

                    'pagina_id' => $faker->randomElement(
                        $paginaIds
                    ),

                    'tipo' => 'toque',

                    'duracao_segundos' => null,

                    'created_at' =>
                    $momento->toDateTimeString(),

                    'updated_at' =>
                    $momento->toDateTimeString(),
                ];

                $totalToques++;
            }

            /*
            |--------------------------------------------------------------------------
            | TEMPO DE PERMANÊNCIA
            |--------------------------------------------------------------------------
            |
            | Gera eventos para calcular a página
            | com maior tempo médio de visualização.
            |
            */

            $quantidadePermanencias = $faker->numberBetween(
                15,
                35
            );

            for ($i = 0; $i < $quantidadePermanencias; $i++) {

                $momento = $inicioDia->copy()
                    ->addSeconds(
                        $faker->numberBetween(
                            0,
                            $segundosDisponiveis
                        )
                    );

                $paginaId = $faker->randomElement(
                    $paginaIds
                );

                // Simula uma página com maior retenção.
                $duracao = $paginaId == $paginaIds[0]
                    ? $faker->numberBetween(80, 160)
                    : $faker->numberBetween(15, 70);

                $registros[] = [
                    'totem_id' => $faker->randomElement(
                        $totensPonderados
                    ),

                    'pagina_id' => $paginaId,

                    'tipo' => 'tempo_pagina',

                    'duracao_segundos' => $duracao,

                    'created_at' =>
                    $momento->toDateTimeString(),

                    'updated_at' =>
                    $momento->toDateTimeString(),
                ];

                $totalPermanencias++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSERIR OS REGISTROS
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($registros) {

            foreach (array_chunk($registros, 250) as $lote) {
                DB::table('interacoes_totem')
                    ->insert($lote);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | RESULTADO
        |--------------------------------------------------------------------------
        */

        $this->command?->info(
            'Dados fictícios cadastrados com sucesso!'
        );

        $this->command?->info(
            "Toques gerados: {$totalToques}"
        );

        $this->command?->info(
            "Permanências geradas: {$totalPermanencias}"
        );

        $this->command?->info(
            'Totens utilizados: ' . count($totemIds)
        );

        $this->command?->info(
            'Páginas utilizadas: ' . count($paginaIds)
        );
    }
}
