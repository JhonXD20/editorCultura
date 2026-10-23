<?php

namespace Tests\Feature;

use App\Models\DeviceCredential;
use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function device(): Totem
    {
        $totem = Totem::factory()->create();
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', 'audit-token')]);
        $this->withToken('audit-token');

        return $totem;
    }

    public function test_offline_occurrence_is_preserved_and_used_by_report_and_dashboard(): void
    {
        Carbon::setTestNow('2026-10-20 14:00:00');
        $this->device();
        $page = Pagina::factory()->published()->create();
        $uuid = (string) Str::uuid();
        $this->postJson(route('device.telemetry'), ['events' => [[
            'uuid' => $uuid, 'pagina_id' => $page->id, 'tipo' => 'toque',
            'occurred_at' => now()->subDay()->setTime(10, 0)->toIso8601String(),
        ]]])->assertOk()->assertJsonPath('accepted', 1)->assertJsonPath('acknowledged.0', $uuid);
        $event = InteracaoTotem::firstOrFail();
        $this->assertSame('2026-10-19 10:00:00', $event->getRawOriginal('ocorrido_em'));
        $this->assertTrue((bool) $event->horario_validado);
        $this->assertSame('2026-10-20 14:00:00', $event->created_at->format('Y-m-d H:i:s'));
        $this->withoutVite()->actingAs(User::factory()->create())->get(route('relatorios', [
            'periodo_inicial' => '2026-10-19', 'periodo_final' => '2026-10-19',
        ]))->assertOk()->assertViewHas('totalInteracoes', 1)->assertViewHas('picoHora', '10:00');
        $this->get(route('admin'))->assertOk()->assertViewHas('interacoesHoje', 0);
    }

    public function test_disabled_published_page_events_are_durable_and_unknown_pages_are_explicitly_rejected(): void
    {
        $this->device();
        $page = Pagina::factory()->published()->create(['pagina_ativo' => false]);
        $valid = (string) Str::uuid();
        $invalid = (string) Str::uuid();
        $payload = ['events' => [
            ['uuid' => $valid, 'pagina_id' => $page->id, 'tipo' => 'toque'],
            ['uuid' => $invalid, 'pagina_id' => 999999, 'tipo' => 'toque'],
        ]];
        $this->postJson(route('device.telemetry'), $payload)->assertOk()->assertExactJson([
            'accepted' => 1, 'acknowledged' => [$valid],
            'rejected' => [['uuid' => $invalid, 'reason' => 'page_unavailable', 'retryable' => false]],
        ]);
        $this->postJson(route('device.telemetry'), $payload)->assertOk()->assertJsonPath('accepted', 0)->assertJsonPath('acknowledged', [$valid]);
        $this->assertDatabaseCount('interacoes_totem', 1);
    }

    public function test_future_clock_falls_back_to_receipt_and_malformed_batch_is_not_partially_written(): void
    {
        $this->device();
        $page = Pagina::factory()->published()->create();
        $event = ['uuid' => (string) Str::uuid(), 'pagina_id' => $page->id, 'tipo' => 'toque'];
        $this->postJson(route('device.telemetry'), ['events' => [$event + ['occurred_at' => 'invalid']]])->assertUnprocessable();
        $this->assertDatabaseCount('interacoes_totem', 0);
        $this->postJson(route('device.telemetry'), ['events' => [$event + ['occurred_at' => now()->addYear()->toIso8601String()]]])->assertOk();
        $row = InteracaoTotem::firstOrFail();
        $this->assertFalse((bool) $row->horario_validado);
        $this->assertSame($row->getRawOriginal('ocorrido_em'), $row->created_at->format('Y-m-d H:i:s'));
    }

    public function test_uuid_owned_by_another_device_is_not_acknowledged_or_overwritten(): void
    {
        $this->device();
        $page = Pagina::factory()->published()->create();
        $uuid = (string) Str::uuid();
        InteracaoTotem::factory()->create(['evento_uuid' => $uuid, 'pagina_id' => $page->id, 'totem_id' => Totem::factory()->create()->id]);
        $this->postJson(route('device.telemetry'), ['events' => [[
            'uuid' => $uuid, 'pagina_id' => $page->id, 'tipo' => 'toque',
        ]]])->assertOk()->assertExactJson([
            'accepted' => 0, 'acknowledged' => [],
            'rejected' => [['uuid' => $uuid, 'reason' => 'uuid_conflict', 'retryable' => false]],
        ]);
        $this->assertDatabaseCount('interacoes_totem', 1);
    }

    public function test_mixed_raw_and_daily_duration_uses_weighted_average(): void
    {
        $page = Pagina::factory()->published()->create();
        $totem = Totem::factory()->create();
        DB::table('metricas_diarias')->insert([
            'data_metrica' => now()->toDateString(), 'totem_id' => $totem->id, 'pagina_id' => $page->id,
            'tipo' => 'tempo_pagina', 'quantidade_eventos' => 2, 'duracao_segundos' => 40,
            'chave_agregacao' => hash('sha256', 'mixed'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        InteracaoTotem::factory()->duration(60)->create(['pagina_id' => $page->id, 'totem_id' => $totem->id]);
        $this->withoutVite()->actingAs(User::factory()->create())->get(route('relatorios', ['totem_id' => $totem->id]))
            ->assertOk()->assertViewHas('mediaRetencaoSegundos', 33);
    }

    public function test_reports_preserve_totals_distributions_and_weighted_duration_after_aggregation(): void
    {
        Carbon::setTestNow('2026-10-20 14:00:00');
        $page = Pagina::factory()->published()->create();
        $totem = Totem::factory()->create();
        $old = now()->subMonthsNoOverflow(25);
        InteracaoTotem::factory()->count(3)->create(['totem_id' => $totem->id, 'pagina_id' => $page->id, 'tipo' => 'toque', 'created_at' => $old]);
        foreach ([10, 20, 90] as $duration) {
            InteracaoTotem::factory()->duration($duration)->create(['totem_id' => $totem->id, 'pagina_id' => $page->id, 'created_at' => $old]);
        }
        $url = route('relatorios', ['periodo_inicial' => $old->toDateString(), 'periodo_final' => $old->toDateString(), 'totem_id' => $totem->id]);
        $this->withoutVite()->actingAs(User::factory()->create());
        $before = $this->get($url)->assertOk()->assertViewHas('totalInteracoes', 3)->assertViewHas('mediaRetencaoSegundos', 40);
        $this->artisan('telemetry:aggregate')->assertSuccessful();
        $this->artisan('telemetry:aggregate')->assertSuccessful();
        $this->assertDatabaseCount('interacoes_totem', 0);
        $after = $this->get($url)->assertOk()->assertViewHas('totalInteracoes', 3)->assertViewHas('mediaRetencaoSegundos', 40)
            ->assertViewHas('picoHistoricoIncompleto', true)->assertViewHas('picoHora', null);
        $this->assertSame($before->viewData('graficos'), $after->viewData('graficos'));
        $this->assertSame(6, (int) DB::table('metricas_diarias')->sum('quantidade_eventos'));
    }
}
