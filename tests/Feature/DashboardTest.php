<?php

namespace Tests\Feature;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('admin'))->assertRedirect(route('login'));
    }

    public function test_dashboard_calculates_monitoring_and_interaction_indicators(): void
    {
        Carbon::setTestNow('2026-10-14 15:30:00');

        $page = Pagina::factory()->create(['pagina_titulo' => 'História']);
        $online = Totem::factory()->create(['totem_ping' => now()->subSeconds(30)]);
        Totem::factory()->create(['totem_ping' => now()->subMinutes(10)]);

        InteracaoTotem::factory()->count(3)->create([
            'totem_id' => $online->id,
            'pagina_id' => $page->id,
            'created_at' => now()->setTime(10, 0),
        ]);
        InteracaoTotem::factory()->duration(80)->count(2)->create([
            'totem_id' => $online->id,
            'pagina_id' => $page->id,
            'created_at' => now()->setTime(11, 0),
        ]);
        InteracaoTotem::factory()->count(2)->create([
            'totem_id' => $online->id,
            'pagina_id' => $page->id,
            'created_at' => now()->subDay()->setTime(10, 0),
        ]);

        $this->withoutVite()
            ->actingAs(User::factory()->create())
            ->get(route('admin'))
            ->assertOk()
            ->assertViewHas('totalTotens', 2)
            ->assertViewHas('totalOnline', 1)
            ->assertViewHas('totalOffline', 1)
            ->assertViewHas('interacoesHoje', 3)
            ->assertViewHas('interacoesOntem', 2)
            ->assertViewHas('variacaoInteracoes', 50.0)
            ->assertViewHas('graficos', function (array $graphs): bool {
                return count($graphs['horarios']['labels']) === 24
                    && $graphs['horarios']['valores'][10] === 3
                    && count($graphs['semana']['labels']) === 7;
            })
            ->assertViewHas('conteudosMaisAcessados', fn ($items): bool => $items->first()['nome'] === 'História')
            ->assertViewHas('alertas', fn ($items): bool => $items->count() === 1);
    }
}
