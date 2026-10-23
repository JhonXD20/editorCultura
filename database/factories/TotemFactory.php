<?php

namespace Database\Factories;

use App\Models\Totem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Totem>
 */
class TotemFactory extends Factory
{
    protected $model = Totem::class;

    public function definition(): array
    {
        return [
            'totem_orientacao' => 'landscape',
            'totem_inatividade_segundos' => 120,
            'totem_nome_identificacao' => 'TESTE - Totem '.fake()->unique()->bothify('??-###'),

            'totem_localizacao' => fake()->randomElement([
                'Hall de Entrada',
                'Galeria Permanente',
                'Memorial Heróis do Tocantins',
                'Sala de Exposições',
                'Recepção Principal',
                'Corredor Histórico',
                'Área de Visitação',
                'Sala de Multimídia',
                'Entrada do Memorial',
            ]),

            'totem_ping' => now()->subSeconds(
                fake()->numberBetween(5, 60)
            ),
        ];
    }

    /**
     * Simula um equipamento online.
     * Última comunicação entre 5 e 60 segundos.
     */
    public function online(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'totem_ping' => now()->subSeconds(
                    fake()->numberBetween(5, 60)
                ),
            ];
        });
    }

    /**
     * Simula um equipamento offline.
     * Última comunicação entre 3 e 180 minutos.
     */
    public function offline(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'totem_ping' => now()->subMinutes(
                    fake()->numberBetween(3, 180)
                ),
            ];
        });
    }
}
