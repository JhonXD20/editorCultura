<?php

namespace Database\Factories;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InteracaoTotem> */
class InteracaoTotemFactory extends Factory
{
    protected $model = InteracaoTotem::class;

    public function definition(): array
    {
        return [
            'totem_id' => Totem::factory(),
            'pagina_id' => Pagina::factory(),
            'tipo' => 'toque',
            'duracao_segundos' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function duration(int $seconds = 60): static
    {
        return $this->state(fn (): array => [
            'tipo' => 'tempo_pagina',
            'duracao_segundos' => $seconds,
        ]);
    }
}
