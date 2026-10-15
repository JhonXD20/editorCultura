<?php

namespace Tests\Feature;

use App\Models\Conteudo;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_add_a_sanitized_text_component(): void
    {
        $page = Pagina::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('gestao-conteudo.edit', $page))
            ->post(route('conteudos.store'), [
                'pagina_id' => $page->id,
                'tipo_componente' => 'texto',
                'texto' => '<strong>Memória</strong><script>alert(1)</script>',
            ])
            ->assertRedirect(route('gestao-conteudo.edit', $page))
            ->assertSessionHas('success');

        $content = Conteudo::firstOrFail();
        $this->assertSame('texto', $content->conteudo_tipo_componente);
        $this->assertStringContainsString('<strong>Memória</strong>', $content->conteudo_dados_conteudo['texto']);
        $this->assertStringNotContainsString('<script', $content->conteudo_dados_conteudo['texto']);
    }

    public function test_navigation_component_can_create_its_destination_page(): void
    {
        $page = Pagina::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('conteudos.store'), [
                'pagina_id' => $page->id,
                'tipo_componente' => 'botao_navegacao',
                'titulo' => 'Conheça o acervo',
                'pagina_destino_id' => 'nova',
                'nova_pagina_titulo' => 'Acervo histórico',
                'grupo_slide' => 'Acervo',
            ])->assertSessionHas('success');

        $destination = Pagina::where('pagina_titulo', 'Acervo histórico')->firstOrFail();
        $button = Conteudo::firstOrFail();

        $this->assertSame('Acervo', $destination->grupo_slide);
        $this->assertSame($destination->id, $button->conteudo_dados_conteudo['pagina_destino_id']);
    }

    public function test_bulk_save_rejects_duplicate_identifiers_and_unsafe_background_css(): void
    {
        $page = Pagina::factory()->create();
        $content = Conteudo::factory()->create(['pagina_id' => $page->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('conteudos.salvar-em-massa'), [
            'pagina_id' => $page->id,
            'blocos' => [
                [
                    'id' => $content->id,
                    'tipo' => 'texto',
                    'ordem' => 1,
                    'dados_conteudo' => ['texto' => 'Primeiro'],
                ],
                [
                    'id' => $content->id,
                    'tipo' => 'texto',
                    'ordem' => 2,
                    'dados_conteudo' => ['texto' => 'Duplicado'],
                ],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('blocos');

        $this->actingAs($user)->postJson(route('conteudos.salvar-em-massa'), [
            'pagina_id' => $page->id,
            'blocos' => [[
                'id' => 'novo_fundo',
                'tipo' => 'fundo',
                'ordem' => 0,
                'dados_conteudo' => [
                    'tipo_fundo' => 'css',
                    'valor_fundo' => 'url(https://tracker.example/pixel)',
                ],
            ]],
        ])->assertOk();

        $background = Conteudo::where('conteudo_tipo_componente', 'fundo')->firstOrFail();
        $this->assertSame('#ffffff', $background->conteudo_dados_conteudo['valor_fundo']);
    }

    public function test_publish_signal_updates_all_totems(): void
    {
        Carbon::setTestNow('2026-10-14 15:00:00');
        $page = Pagina::factory()->create();
        $totem = Totem::factory()->create(['updated_at' => now()->subDay()]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('conteudos.publicar'), ['pagina_id' => $page->id])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($totem->fresh()->updated_at->equalTo(now()));
    }
}
