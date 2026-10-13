<?php

namespace Database\Seeders;

use App\Models\Totem;
use Illuminate\Database\Seeder;

class TotemSeeder extends Seeder
{
    public function run(): void
    {
        // Impede a execução acidental em produção.
        if (app()->environment('production')) {
            $this->command?->warn(
                'Seeder de teste não executado em produção.'
            );

            return;
        }

        /*
         * Cenário de teste:
         *
         * 9 dispositivos
         * 5 online
         * 4 offline
         */

        $dispositivos = [
            [
                'nome' => 'TESTE - Totem Entrada Principal',
                'local' => 'Hall de Entrada',
                'status' => 'online',
            ],
            [
                'nome' => 'TESTE - Totem Galeria Permanente',
                'local' => 'Galeria Permanente',
                'status' => 'online',
            ],
            [
                'nome' => 'TESTE - Totem Exposição Histórica',
                'local' => 'Sala de Exposições',
                'status' => 'online',
            ],
            [
                'nome' => 'TESTE - Totem Acervo Digital',
                'local' => 'Área de Visitação',
                'status' => 'online',
            ],
            [
                'nome' => 'TESTE - Totem Recepção',
                'local' => 'Recepção Principal',
                'status' => 'online',
            ],
            [
                'nome' => 'TESTE - Totem Auditório',
                'local' => 'Auditório Principal',
                'status' => 'offline',
            ],
            [
                'nome' => 'TESTE - Totem Multimídia',
                'local' => 'Sala de Multimídia',
                'status' => 'offline',
            ],
            [
                'nome' => 'TESTE - Totem Corredor Histórico',
                'local' => 'Corredor Histórico',
                'status' => 'offline',
            ],
            [
                'nome' => 'TESTE - Totem Entrada Secundária',
                'local' => 'Entrada Secundária',
                'status' => 'offline',
            ],
        ];

        foreach ($dispositivos as $dispositivo) {

            // Gera um ping fictício conforme o status.
            $fakeTotem = $dispositivo['status'] === 'online'
                ? Totem::factory()->online()->make()
                : Totem::factory()->offline()->make();

            // Atualiza registros de teste existentes,
            // evitando criar duplicatas a cada execução.
            Totem::updateOrCreate(
                [
                    'totem_nome_identificacao' =>
                    $dispositivo['nome'],
                ],
                [
                    'totem_localizacao' =>
                    $dispositivo['local'],

                    'totem_ping' =>
                    $fakeTotem->totem_ping,
                ]
            );
        }

        $this->command?->info(
            '9 totens de teste atualizados: 5 online e 4 offline.'
        );
    }
}
