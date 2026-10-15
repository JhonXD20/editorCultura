<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TotemManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_totem_routes_require_authentication(): void
    {
        $totem = Totem::factory()->create();

        $this->get(route('admin.totens.index'))->assertRedirect(route('login'));
        $this->getJson(route('admin.totens.status'))->assertUnauthorized();
        $this->getJson(route('admin.totens.acesso', $totem))->assertUnauthorized();
        $this->get(route('admin.totens.show', $totem))->assertRedirect(route('login'));
        $this->post(route('admin.totens.store'))->assertRedirect(route('login'));
    }

    public function test_index_reports_online_and_offline_totems(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');
        Totem::factory()->count(2)->online()->create();
        Totem::factory()->offline()->create();

        $this->withoutVite()
            ->actingAs(User::factory()->create())
            ->get(route('admin.totens.index'))
            ->assertOk()
            ->assertViewHas('resumo', [
                'todos' => 3,
                'online' => 2,
                'offline' => 1,
            ]);
    }

    public function test_realtime_status_endpoint_returns_current_totem_snapshot(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');

        $online = Totem::factory()->create([
            'totem_nome_identificacao' => 'Totem Biblioteca',
            'totem_localizacao' => 'Entrada principal',
            'totem_ping' => now()->subMinute(),
        ]);
        $offline = Totem::factory()->create([
            'totem_nome_identificacao' => 'Totem Auditório',
            'totem_localizacao' => null,
            'totem_ping' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.totens.status'));

        $response->assertOk()
            ->assertJsonPath('resumo.todos', 2)
            ->assertJsonPath('resumo.online', 1)
            ->assertJsonPath('resumo.offline', 1)
            ->assertJsonPath('totens.0.id', $online->id)
            ->assertJsonPath('totens.0.status', 'online')
            ->assertJsonPath('totens.0.nome', 'Totem Biblioteca')
            ->assertJsonPath('totens.0.localizacao', 'Entrada principal')
            ->assertJsonPath('totens.1.id', $offline->id)
            ->assertJsonPath('totens.1.status', 'offline')
            ->assertJsonPath('totens.1.localizacao', 'Localização não informada')
            ->assertJsonStructure([
                'gerado_em',
                'resumo' => ['todos', 'online', 'offline'],
                'totens' => [
                    '*' => [
                        'id',
                        'nome',
                        'localizacao',
                        'status',
                        'ultimo_contato' => ['relativo', 'data', 'iso'],
                        'urls' => ['buscar', 'editar', 'excluir'],
                    ],
                ],
            ]);

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function test_authenticated_user_can_create_show_update_and_delete_a_totem(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.totens.store'), [
            'totem_nome_identificacao' => 'Totem Biblioteca',
            'totem_localizacao' => 'Entrada',
        ])->assertRedirect(route('admin.totens.index'))
            ->assertSessionHas('success');

        $totem = Totem::where('totem_nome_identificacao', 'Totem Biblioteca')->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('admin.totens.show', $totem))
            ->assertOk()
            ->assertExactJson([
                'id' => $totem->id,
                'totem_nome_identificacao' => 'Totem Biblioteca',
                'totem_localizacao' => 'Entrada',
                'pagina_inicial_id' => null,
                'totem_orientacao' => 'landscape',
                'totem_inatividade_segundos' => 120,
            ]);

        $this->actingAs($user)->put(route('admin.totens.update', $totem), [
            'totem_nome_identificacao' => 'Totem Biblioteca Central',
            'totem_localizacao' => 'Hall',
        ])->assertRedirect(route('admin.totens.index'));

        $this->assertDatabaseHas('totens', [
            'id' => $totem->id,
            'totem_nome_identificacao' => 'Totem Biblioteca Central',
            'totem_localizacao' => 'Hall',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.totens.destroy', $totem))
            ->assertRedirect(route('admin.totens.index'));

        $this->assertDatabaseMissing('totens', ['id' => $totem->id]);
    }

    public function test_totem_validation_rejects_missing_name_and_long_values(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('admin.totens.index'))
            ->post(route('admin.totens.store'), [
                'totem_nome_identificacao' => '',
                'totem_localizacao' => str_repeat('x', 101),
            ])
            ->assertRedirect(route('admin.totens.index'))
            ->assertSessionHasErrors([
                'totem_nome_identificacao',
                'totem_localizacao',
            ], null, 'formTotem');

        $this->assertDatabaseCount('totens', 0);
    }

    public function test_status_accessor_uses_the_two_minute_threshold(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');

        $this->assertSame('online', Totem::factory()->make([
            'totem_ping' => now()->subSeconds(120),
        ])->status_monitoramento);
        $this->assertSame('offline', Totem::factory()->make([
            'totem_ping' => now()->subSeconds(121),
        ])->status_monitoramento);
        $this->assertSame('offline', Totem::factory()->make([
            'totem_ping' => null,
        ])->status_monitoramento);
    }

    public function test_tablet_settings_are_saved_and_preserved_when_omitted(): void
    {
        $page = Pagina::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('admin.totens.store'), [
            'totem_nome_identificacao' => 'Tablet Android',
            'pagina_inicial_id' => $page->id,
            'totem_orientacao' => 'landscape',
            'totem_inatividade_segundos' => 300,
        ])->assertRedirect(route('admin.totens.index'));

        $totem = Totem::firstOrFail();
        $this->assertSame($page->id, $totem->pagina_inicial_id);
        $this->assertSame(300, $totem->totem_inatividade_segundos);

        $this->put(route('admin.totens.update', $totem), [
            'totem_nome_identificacao' => 'Tablet atualizado',
        ])->assertRedirect(route('admin.totens.index'));

        $this->assertSame($page->id, $totem->fresh()->pagina_inicial_id);
        $this->assertSame(300, $totem->fresh()->totem_inatividade_segundos);

        $this->put(route('admin.totens.update', $totem), [
            'totem_nome_identificacao' => 'Tablet atualizado',
            'pagina_inicial_id' => null,
            'totem_inatividade_segundos' => 0,
        ])->assertRedirect(route('admin.totens.index'));

        $this->assertNull($totem->fresh()->pagina_inicial_id);
        $this->assertSame(0, $totem->fresh()->totem_inatividade_segundos);
    }

    public function test_invalid_settings_and_inactive_home_pages_are_rejected(): void
    {
        $page = Pagina::factory()->inactive()->create();
        $this->actingAs(User::factory()->create())->post(route('admin.totens.store'), [
            'totem_nome_identificacao' => 'Tablet Android',
            'pagina_inicial_id' => $page->id,
            'totem_orientacao' => 'invalid',
            'totem_inatividade_segundos' => -1,
        ])->assertSessionHasErrors([
            'pagina_inicial_id', 'totem_orientacao', 'totem_inatividade_segundos',
        ], null, 'formTotem');

        $this->assertDatabaseCount('totens', 0);
    }

    public function test_admin_can_generate_an_expiring_tablet_start_link(): void
    {
        Carbon::setTestNow('2026-10-14 12:00:00');
        $page = Pagina::factory()->create();
        $totem = Totem::factory()->create(['pagina_inicial_id' => $page->id]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.totens.acesso', $totem))
            ->assertOk()
            ->assertJsonPath('pagina', $page->pagina_titulo);

        $url = $response->json('url');
        $this->get($url)->assertRedirect(route('totem.exibir', $page))
            ->assertSessionHas('totem_ativo_id', $totem->id);

        Carbon::setTestNow(now()->addMinutes(31));
        $this->get($url)->assertForbidden();
    }

    public function test_start_link_requires_an_active_page(): void
    {
        $totem = Totem::factory()->create();
        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.totens.acesso', $totem))
            ->assertUnprocessable();
    }
}
