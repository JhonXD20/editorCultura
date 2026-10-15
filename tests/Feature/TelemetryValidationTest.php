<?php

namespace Tests\Feature;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_touch_is_recorded_without_a_totem(): void
    {
        $page = Pagina::factory()->create();

        $this->postJson(route('totem.interacoes.store'), [
            'pagina_id' => $page->id,
            'tipo' => 'toque',
            'totem_id' => 123,
        ])->assertNoContent();

        $interaction = InteracaoTotem::firstOrFail();
        $this->assertNull($interaction->totem_id);
        $this->assertNull($interaction->duracao_segundos);
    }

    public function test_duration_requires_a_valid_number_of_seconds(): void
    {
        $page = Pagina::factory()->create();

        $this->postJson(route('totem.interacoes.store'), [
            'pagina_id' => $page->id,
            'tipo' => 'tempo_pagina',
        ])->assertUnprocessable()->assertJsonValidationErrors('duracao_segundos');

        $this->postJson(route('totem.interacoes.store'), [
            'pagina_id' => $page->id,
            'tipo' => 'tempo_pagina',
            'duracao_segundos' => 86401,
        ])->assertUnprocessable()->assertJsonValidationErrors('duracao_segundos');
    }

    public function test_interaction_rejects_inactive_pages_and_unknown_types(): void
    {
        $page = Pagina::factory()->inactive()->create();

        $this->postJson(route('totem.interacoes.store'), [
            'pagina_id' => $page->id,
            'tipo' => 'toque',
        ])->assertUnprocessable()->assertJsonValidationErrors('pagina_id');

        $activePage = Pagina::factory()->create();
        $this->postJson(route('totem.interacoes.store'), [
            'pagina_id' => $activePage->id,
            'tipo' => 'execucao_arbitraria',
        ])->assertUnprocessable()->assertJsonValidationErrors('tipo');
    }

    public function test_deleted_totem_session_is_discarded_safely(): void
    {
        $page = Pagina::factory()->create();
        $totem = Totem::factory()->create();
        $totemId = $totem->id;
        $totem->delete();

        $this->withSession(['totem_ativo_id' => $totemId])
            ->postJson(route('totem.interacoes.store'), [
                'pagina_id' => $page->id,
                'tipo' => 'toque',
            ])->assertNoContent()
            ->assertSessionMissing('totem_ativo_id');

        $this->assertNull(InteracaoTotem::firstOrFail()->totem_id);
    }
}
