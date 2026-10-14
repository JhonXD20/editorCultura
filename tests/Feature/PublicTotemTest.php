<?php

namespace Tests\Feature;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicTotemTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_exposes_the_telemetry_configuration(): void
    {
        $this->withoutVite();

        $pagina = Pagina::create([
            'pagina_titulo' => 'Inicio',
            'pagina_tipo_layout' => 'home',
            'pagina_ativo' => true,
        ]);

        $response = $this->get(route('totem.exibir', $pagina));

        $response->assertOk();
        $response->assertSee('data-pagina-id="'.$pagina->id.'"', false);
        $response->assertSee('data-interacao-url="'.route('totem.interacoes.store').'"', false);
        $response->assertSee('data-ping-url="'.route('totem.ping').'"', false);
        $response->assertSee('data-inicial-url="'.route('totem.exibir', $pagina).'"', false);
    }

    public function test_inactive_page_is_not_publicly_accessible(): void
    {
        $pagina = Pagina::create([
            'pagina_titulo' => 'Rascunho',
            'pagina_tipo_layout' => 'interna',
            'pagina_ativo' => false,
        ]);

        $this->get(route('totem.exibir', $pagina))->assertNotFound();
    }

    public function test_signed_initialization_associates_the_totem_with_the_session(): void
    {
        $pagina = Pagina::create([
            'pagina_titulo' => 'Inicio',
            'pagina_tipo_layout' => 'home',
            'pagina_ativo' => true,
        ]);
        $totem = Totem::create([
            'totem_nome_identificacao' => 'Totem 1',
        ]);

        $url = URL::signedRoute('totem.iniciar', [
            'totem' => $totem,
            'pagina' => $pagina,
        ]);

        $this->get($url)
            ->assertRedirect(route('totem.exibir', $pagina))
            ->assertSessionHas('totem_ativo_id', $totem->id);
    }

    public function test_interaction_and_ping_use_the_totem_from_the_session(): void
    {
        $pagina = Pagina::create([
            'pagina_titulo' => 'Inicio',
            'pagina_tipo_layout' => 'home',
            'pagina_ativo' => true,
        ]);
        $totem = Totem::create([
            'totem_nome_identificacao' => 'Totem 1',
        ]);

        $this->withSession(['totem_ativo_id' => $totem->id])
            ->postJson(route('totem.interacoes.store'), [
                'pagina_id' => $pagina->id,
                'tipo' => 'toque',
            ])
            ->assertNoContent();

        $this->assertDatabaseHas(InteracaoTotem::class, [
            'totem_id' => $totem->id,
            'pagina_id' => $pagina->id,
            'tipo' => 'toque',
        ]);

        $this->withSession(['totem_ativo_id' => $totem->id])
            ->postJson(route('totem.ping'))
            ->assertNoContent();

        $this->assertNotNull($totem->fresh()->totem_ping);
    }
}
