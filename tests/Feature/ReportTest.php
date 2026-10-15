<?php

namespace Tests\Feature;

use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_reports_require_authentication(): void
    {
        $this->get(route('relatorios'))->assertRedirect(route('login'));
    }

    public function test_report_calculates_totals_daily_average_peak_and_retention(): void
    {
        Carbon::setTestNow('2026-10-14 18:00:00');
        $page = Pagina::factory()->create(['pagina_titulo' => 'Memória local']);
        $totem = Totem::factory()->create(['totem_nome_identificacao' => 'Totem A']);

        InteracaoTotem::factory()->count(3)->create([
            'totem_id' => $totem->id,
            'pagina_id' => $page->id,
            'created_at' => now()->setTime(10, 15),
        ]);
        InteracaoTotem::factory()->create([
            'totem_id' => $totem->id,
            'pagina_id' => $page->id,
            'created_at' => now()->subDay()->setTime(14, 0),
        ]);
        InteracaoTotem::factory()->duration(120)->create([
            'totem_id' => $totem->id,
            'pagina_id' => $page->id,
            'created_at' => now()->setTime(11, 0),
        ]);

        $this->withoutVite()
            ->actingAs(User::factory()->create())
            ->get(route('relatorios', [
                'periodo_inicial' => '2026-10-13',
                'periodo_final' => '2026-10-14',
            ]))
            ->assertOk()
            ->assertViewHas('totalInteracoes', 4)
            ->assertViewHas('mediaDiaria', 2.0)
            ->assertViewHas('picoHora', '10:00')
            ->assertViewHas('picoQuantidade', 3)
            ->assertViewHas('nomePaginaRetencao', 'Memória local')
            ->assertViewHas('mediaRetencaoSegundos', 120)
            ->assertViewHas('graficos', function (array $graphs): bool {
                return $graphs['linha']['labels'] === ['13/10', '14/10']
                    && $graphs['linha']['valores'] === [1, 3]
                    && $graphs['rosca']['valores'] === [4];
            });
    }

    public function test_report_can_filter_a_single_totem(): void
    {
        Carbon::setTestNow('2026-10-14 18:00:00');
        $page = Pagina::factory()->create();
        $first = Totem::factory()->create();
        $second = Totem::factory()->create();

        InteracaoTotem::factory()->count(2)->create([
            'totem_id' => $first->id,
            'pagina_id' => $page->id,
            'created_at' => now()->subHour(),
        ]);
        InteracaoTotem::factory()->count(4)->create([
            'totem_id' => $second->id,
            'pagina_id' => $page->id,
            'created_at' => now()->subHour(),
        ]);

        $this->withoutVite()
            ->actingAs(User::factory()->create())
            ->get(route('relatorios', [
                'periodo_inicial' => now()->toDateString(),
                'periodo_final' => now()->toDateString(),
                'totem_id' => $first->id,
            ]))
            ->assertOk()
            ->assertViewHas('totalInteracoes', 2)
            ->assertViewHas('totemId', $first->id);
    }

    public function test_report_rejects_inverted_excessive_and_unknown_filters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('relatorios', [
            'periodo_inicial' => '2026-10-14',
            'periodo_final' => '2026-10-13',
        ]))->assertSessionHasErrors('periodo_final');

        $this->actingAs($user)->get(route('relatorios', [
            'periodo_inicial' => '2025-01-01',
            'periodo_final' => '2026-10-14',
        ]))->assertSessionHasErrors('periodo_inicial');

        $this->actingAs($user)->get(route('relatorios', [
            'totem_id' => 999999,
        ]))->assertSessionHasErrors('totem_id');
    }
}
