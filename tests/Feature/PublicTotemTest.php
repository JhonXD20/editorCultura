<?php

namespace Tests\Feature;

use App\Models\DeviceCredential;
use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTotemTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaired_device_is_rejected(): void
    {
        $page = Pagina::factory()->create();
        $this->get(route('totem.exibir', $page))->assertUnauthorized()->assertSee('Dispositivo não pareado');
    }

    public function test_paired_device_receives_manifest_and_player_configuration(): void
    {
        $this->withoutVite();
        $page = Pagina::factory()->home()->published()->create(['pagina_versao_publicacao' => 2]);
        $totem = Totem::factory()->create(['pagina_inicial_id' => $page->id, 'totem_inatividade_segundos' => 180]);
        $token = 'device-secret-token';
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', $token)]);

        $this->withToken($token)->get(route('totem.exibir', $page))
            ->assertOk()->assertSee('data-manifest-url="'.route('device.manifest').'"', false)
            ->assertSee('data-inatividade-segundos="180"', false);
        $this->withToken($token)->getJson(route('device.manifest'))
            ->assertOk()->assertJsonPath('totem.id', $totem->id)->assertJsonPath('pages.0.id', $page->id)
            ->assertJsonPath('pages.0.version', 2)
            ->assertJsonStructure(['version', 'totem' => ['reload_requested_at'], 'pages' => [['checksum']]]);
    }

    public function test_batch_telemetry_is_idempotent_and_heartbeat_updates_totem(): void
    {
        $page = Pagina::factory()->published()->create();
        $totem = Totem::factory()->create();
        $token = 'device-secret-token';
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', $token)]);
        $uuid = (string) Str::uuid();
        $payload = ['events' => [['uuid' => $uuid, 'pagina_id' => $page->id, 'tipo' => 'toque']]];

        $this->withToken($token)->postJson(route('device.telemetry'), $payload)
            ->assertOk()->assertJsonPath('accepted', 1);
        $this->withToken($token)->postJson(route('device.telemetry'), $payload)
            ->assertOk()->assertJsonPath('accepted', 0);
        $this->assertDatabaseCount(InteracaoTotem::class, 1);
        $this->withToken($token)->postJson(route('device.heartbeat'))->assertOk();
        $this->assertNotNull($totem->fresh()->totem_ping);
    }

    public function test_revoked_device_is_rejected(): void
    {
        $page = Pagina::factory()->create();
        $totem = Totem::factory()->create();
        $token = 'revoked-token';
        DeviceCredential::create([
            'totem_id' => $totem->id,
            'hash_token' => hash('sha256', $token),
            'revogado_em' => now(),
        ]);

        $this->withToken($token)->get(route('totem.exibir', $page))->assertUnauthorized();
    }
}
