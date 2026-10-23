<?php

namespace Tests\Feature;

use App\Models\DeviceCredential;
use App\Models\MediaAsset;
use App\Models\Pagina;
use App\Models\PairingCode;
use App\Models\ReviewRequest;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModernizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_does_not_change_publication_version(): void
    {
        $page = Pagina::factory()->home()->published()->create();
        $totem = Totem::factory()->create(['pagina_inicial_id' => $page->id]);
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', 'secret')]);
        $version = $this->withToken('secret')->getJson(route('device.manifest'))->json('version');
        $this->travel(5)->minutes();
        $this->withToken('secret')->postJson(route('device.heartbeat'))->assertOk();
        $this->withToken('secret')->getJson(route('device.manifest'))->assertJsonPath('version', $version);
        $page->update(['pagina_html_publicado' => '<main>Outra versão</main>', 'pagina_versao_publicacao' => 2]);
        $this->assertNotSame($version, $this->withToken('secret')->getJson(route('device.manifest'))->json('version'));
    }

    public function test_approval_keeps_newer_working_draft(): void
    {
        $page = Pagina::factory()->create();
        $editor = User::factory()->create(['perfil' => 'editor']);
        $this->actingAs($editor)->postJson(route('editor-visual.submit', $page), $this->payload(1))->assertOk();
        $newer = ['pages' => [['component' => ['tagName' => 'main', 'content' => 'Rascunho mais recente']]]];
        $this->actingAs($editor)->putJson(route('editor-visual.save', $page), ['project_data' => $newer, 'base_version' => 2])->assertOk();
        $this->actingAs(User::factory()->create())->post(route('reviews.approve', ReviewRequest::firstOrFail()))->assertRedirect();
        $this->assertEquals($newer, $page->fresh()->pagina_dados_editor);
        $this->assertSame('<main>Versão enviada</main>', $page->fresh()->pagina_html_publicado);
        $this->assertSame('draft', $page->fresh()->pagina_estado_editorial);
    }

    public function test_manual_save_keeps_media_referenced_by_previous_revisions(): void
    {
        $page = Pagina::factory()->create();
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'disco' => 'public', 'caminho' => 'media/images/test.png',
            'nome_original' => 'test.png', 'tipo_mime' => 'image/png', 'tamanho' => 20, 'hash_verificacao' => hash('sha256', 'test'), 'tipo' => 'image',
        ]);
        $payload = $this->payload(1);
        $payload['html'] = '<main><img src="/storage/media/images/test.png"></main>';
        $payload['manual'] = true;
        $this->actingAs(User::factory()->create())->putJson(route('editor-visual.save', $page), $payload)->assertOk();
        $this->putJson(route('editor-visual.save', $page), $this->payload(2))->assertOk();
        $this->delete(route('midias.destroy', $asset))->assertSessionHas('error');
        $this->deleteJson(route('midias.destroy', $asset))->assertUnprocessable();
        $this->assertDatabaseHas('midias', ['id' => $asset->id]);
    }

    public function test_session_version_revokes_sessions_independently_of_storage_driver(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->withSession(['auth_session_version' => 1]);
        $admin->forceFill(['versao_sessao' => 2])->save();
        $this->getJson(route('gestao-conteudo.index'))->assertUnauthorized();
    }

    public function test_pairing_account_has_no_admin_access_and_ends_session_after_pairing(): void
    {
        $home = Pagina::factory()->home()->published()->create();
        $totem = Totem::factory()->create(['pagina_inicial_id' => $home->id]);
        $user = User::factory()->create(['perfil' => 'pairing']);
        $this->actingAs($user)->get(route('usuarios.index'))->assertForbidden();
        $this->post(route('pairing.store'), ['totem_id' => $totem->id])
            ->assertRedirect(route('totem.exibir', $home))->assertCookie('totemsync_device');
        $this->assertGuest();
    }

    public function test_draft_only_page_is_not_available_on_player(): void
    {
        $page = Pagina::factory()->create();
        $totem = Totem::factory()->create();
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', 'secret')]);
        $this->withToken('secret')->get(route('totem.exibir', $page))->assertNotFound();
    }

    public function test_home_cannot_be_demoted_without_replacement(): void
    {
        $home = Pagina::factory()->home()->published()->create();
        $this->actingAs(User::factory()->create())->putJson(route('gestao-conteudo.update', $home), [
            'pagina_titulo' => 'Inicial', 'pagina_tipo_layout' => 'interna', 'pagina_ativo' => true,
        ])->assertUnprocessable();
        $this->assertSame('home', $home->fresh()->pagina_tipo_layout);
    }

    public function test_telemetry_duration_is_validated_before_any_event_is_written(): void
    {
        $page = Pagina::factory()->published()->create();
        $totem = Totem::factory()->create();
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', 'secret')]);
        $this->withToken('secret')->postJson(route('device.telemetry'), ['events' => [
            ['uuid' => (string) Str::uuid(), 'pagina_id' => $page->id, 'tipo' => 'toque'],
            ['uuid' => (string) Str::uuid(), 'pagina_id' => $page->id, 'tipo' => 'tempo_pagina'],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('interacoes_totem', 0);
    }

    public function test_expired_pairing_code_cannot_issue_a_device_credential(): void
    {
        $totem = Totem::factory()->create();
        PairingCode::create([
            'totem_id' => $totem->id, 'hash_codigo' => hash('sha256', '123456'), 'expira_em' => now()->subSecond(),
        ]);
        $this->post(route('device.pair'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('credenciais_dispositivo', 0);
    }

    public function test_media_library_rejects_disguised_svg_upload(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('imagem.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->actingAs(User::factory()->create(['perfil' => 'editor']))
            ->postJson(route('midias.store'), ['file' => $file])->assertUnprocessable();
        $this->assertDatabaseCount('midias', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_media_library_uses_uuid_and_checksums_for_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['perfil' => 'editor']))->post(route('midias.store'), [
            'file' => UploadedFile::fake()->image('original.png', 40, 20),
        ])->assertRedirect();
        $asset = MediaAsset::firstOrFail();
        $this->assertTrue(Str::isUuid($asset->uuid));
        $this->assertStringNotContainsString('original', $asset->caminho);
        $this->assertSame(hash('sha256', Storage::disk('public')->get($asset->caminho)), $asset->hash_verificacao);
    }

    public function test_aggregation_is_idempotent_and_keeps_recent_telemetry(): void
    {
        $old = now()->subMonthsNoOverflow(25);
        foreach ([10, 20] as $duration) {
            DB::table('interacoes_totem')->insert([
                'totem_id' => null, 'pagina_id' => null, 'tipo' => 'tempo_pagina', 'duracao_segundos' => $duration,
                'created_at' => $old, 'updated_at' => $old,
            ]);
        }
        DB::table('interacoes_totem')->insert([
            'totem_id' => null, 'pagina_id' => null, 'tipo' => 'toque', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->artisan('telemetry:aggregate')->assertSuccessful();
        $this->artisan('telemetry:aggregate')->assertSuccessful();
        $this->assertDatabaseCount('metricas_diarias', 1);
        $this->assertDatabaseHas('metricas_diarias', ['quantidade_eventos' => 2, 'duracao_segundos' => 30]);
        $this->assertDatabaseCount('interacoes_totem', 1);
    }

    public function test_last_active_administrator_cannot_be_demoted(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->put(route('usuarios.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'perfil' => 'editor', 'ativo' => true,
        ])->assertSessionHasErrors('perfil');
        $this->assertSame('admin', $admin->fresh()->perfil);
    }

    private function payload(int $version): array
    {
        return [
            'base_version' => $version,
            'project_data' => ['pages' => [['component' => ['tagName' => 'main', 'content' => 'Versão enviada']]]],
            'html' => '<main>Versão enviada</main>', 'css' => '',
        ];
    }
}
