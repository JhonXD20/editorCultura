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

    public function test_public_page_exposes_navigation_within_the_same_group(): void
    {
        $this->withoutVite();

        $home = Pagina::factory()->home()->create();
        $first = Pagina::factory()->create([
            'grupo_slide' => 'História',
            'ordem_slide' => 1,
        ]);
        $current = Pagina::factory()->create([
            'grupo_slide' => 'História',
            'ordem_slide' => 2,
        ]);
        $next = Pagina::factory()->create([
            'grupo_slide' => 'História',
            'ordem_slide' => 3,
        ]);
        Pagina::factory()->inactive()->create([
            'grupo_slide' => 'História',
            'ordem_slide' => 4,
        ]);

        $this->get(route('totem.exibir', $current))
            ->assertOk()
            ->assertViewHas('paginaAnteriorId', $first->id)
            ->assertViewHas('proximaPaginaId', $next->id)
            ->assertViewHas('paginaInicialId', $home->id);
    }

    public function test_unsigned_initialization_is_rejected(): void
    {
        $page = Pagina::factory()->create();
        $totem = Totem::factory()->create();

        $this->get(route('totem.iniciar', [
            'totem' => $totem,
            'pagina' => $page,
        ]))->assertForbidden();
    }

    public function test_associated_tablet_uses_its_home_orientation_and_idle_timeout(): void
    {
        $globalHome = Pagina::factory()->home()->create();
        $customHome = Pagina::factory()->create();
        $current = Pagina::factory()->published()->create();
        $totem = Totem::factory()->create([
            'pagina_inicial_id' => $customHome->id,
            'totem_orientacao' => 'landscape',
            'totem_inatividade_segundos' => 180,
        ]);

        $this->withoutVite()->withSession(['totem_ativo_id' => $totem->id])
            ->get(route('totem.exibir', $current))
            ->assertOk()
            ->assertViewHas('paginaInicialId', $customHome->id)
            ->assertSee('data-orientacao="landscape"', false)
            ->assertSee('data-inatividade-segundos="180"', false)
            ->assertSee('data-inicial-url="'.route('totem.exibir', $customHome).'"', false)
            ->assertSee('Navegação do TotemSync')
            ->assertDontSee('touchZoneLeft', false);

        $customHome->update(['pagina_ativo' => false]);
        $this->get(route('totem.exibir', $current))->assertOk()
            ->assertViewHas('paginaInicialId', $globalHome->id);
    }

    public function test_deleting_the_configured_home_preserves_the_tablet(): void
    {
        $home = Pagina::factory()->home()->create();
        $customHome = Pagina::factory()->create();
        $totem = Totem::factory()->create(['pagina_inicial_id' => $customHome->id]);
        $customHome->delete();

        $this->assertNull($totem->fresh()->pagina_inicial_id);
        $this->assertSame($home->id, $totem->fresh()->paginaInicialDisponivel()->id);
    }

    public function test_preview_does_not_redirect_for_inactivity_and_clears_deleted_device(): void
    {
        $page = Pagina::factory()->create();
        $this->withoutVite()->withSession(['totem_ativo_id' => 99999])
            ->get(route('totem.exibir', $page))->assertOk()
            ->assertSessionMissing('totem_ativo_id')
            ->assertSee('data-inatividade-segundos="0"', false)
            ->assertViewHas('paginaInicialId', $page->id);
    }
}
