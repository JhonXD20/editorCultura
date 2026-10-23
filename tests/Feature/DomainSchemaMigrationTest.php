<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DeviceCredential;
use App\Models\MediaAsset;
use App\Models\PageRevision;
use App\Models\Pagina;
use App\Models\PairingCode;
use App\Models\ReviewRequest;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DomainSchemaMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_populated_schema_survives_rollback_and_upgrade_with_relationships_intact(): void
    {
        $user = User::factory()->create(['data_nascimento' => '1990-01-02']);
        $page = Pagina::factory()->published()->create();
        $totem = Totem::factory()->create(['pagina_inicial_id' => $page->id, 'totem_recarga_solicitada_em' => now()]);
        $revision = PageRevision::create([
            'pagina_id' => $page->id, 'criado_por' => $user->id, 'versao' => 1, 'estado' => 'published',
            'dados_projeto' => ['pages' => []], 'html' => '<main>Conteúdo preservado</main>', 'css' => 'main{color:red}',
            'hash_verificacao' => hash('sha256', 'revision'), 'observacao' => 'Histórico preservado',
        ]);
        $page->update(['revisao_publicada_id' => $revision->id]);
        $review = ReviewRequest::create([
            'pagina_id' => $page->id, 'revisao_pagina_id' => $revision->id, 'enviado_por' => $user->id,
            'revisado_por' => $user->id, 'situacao' => 'approved', 'comentario_revisao' => 'Aprovada',
            'enviado_em' => now(), 'revisado_em' => now(),
        ]);
        $asset = MediaAsset::create([
            'uuid' => (string) Str::uuid(), 'enviado_por' => $user->id, 'disco' => 'public',
            'caminho' => 'media/images/preservada.png', 'nome_original' => 'Preservada.png', 'tipo_mime' => 'image/png',
            'tamanho' => 123, 'hash_verificacao' => hash('sha256', 'media'), 'tipo' => 'image',
            'metadados' => ['width' => 10], 'ultima_referencia_em' => now(),
        ]);
        $page->mediaAssets()->attach($asset);
        AuditLog::create([
            'usuario_id' => $user->id, 'acao' => 'content.published', 'auditavel_tipo' => Pagina::class,
            'auditavel_id' => $page->id, 'endereco_ip' => '127.0.0.1', 'agente_usuario' => 'Teste', 'metadados' => ['teste' => true],
        ]);
        DeviceCredential::create([
            'totem_id' => $totem->id, 'nome' => 'Dispositivo', 'hash_token' => hash('sha256', 'token'),
            'ultimo_contato_em' => now(), 'revogado_em' => now(),
        ]);
        PairingCode::create([
            'totem_id' => $totem->id, 'criado_por' => $user->id, 'hash_codigo' => hash('sha256', '123456'),
            'expira_em' => now()->addHour(), 'usado_em' => now(),
        ]);
        DB::table('metricas_diarias')->insert([
            'data_metrica' => '2026-01-01', 'totem_id' => $totem->id, 'pagina_id' => $page->id,
            'tipo' => 'tempo_pagina', 'quantidade_eventos' => 2, 'duracao_segundos' => 30,
            'chave_agregacao' => hash('sha256', 'metric'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('interacoes_totem')->insert([
            'evento_uuid' => (string) Str::uuid(), 'totem_id' => $totem->id, 'pagina_id' => $page->id,
            'tipo' => 'toque', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $tables = ['users', 'paginas', 'totens', 'interacoes_totem', 'revisoes_pagina', 'solicitacoes_revisao',
            'midias', 'referencias_midia', 'registros_auditoria', 'credenciais_dispositivo', 'codigos_pareamento', 'metricas_diarias'];
        $before = $this->snapshot($tables);
        $internal = $this->snapshot(['sessions', 'password_reset_tokens', 'notifications', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs']);
        $migration = $this->migration();
        $migration->down();
        $this->assertDatabaseHas('page_revisions', ['id' => $revision->id, 'version' => 1, 'note' => 'Histórico preservado']);
        $this->assertDatabaseHas('media_references', ['media_asset_id' => $asset->id, 'pagina_id' => $page->id]);
        $this->assertSame('1990-01-02', substr(DB::table('users')->where('id', $user->id)->value('birth_date'), 0, 10));
        $this->assertFalse(Schema::hasTable('revisoes_pagina'));
        $migration->up();
        $this->assertEquals($before, $this->snapshot($tables));
        $this->assertEquals($internal, $this->snapshot(array_keys($internal)));
        $this->assertTrue($page->fresh()->publishedRevision->is($revision));
        $this->assertTrue($review->fresh()->revision->is($revision));
        $this->assertTrue($asset->paginas()->first()->is($page));
        $this->assertTrue($page->mediaAssets()->first()->is($asset));

        // Confere ações referenciais após renomear tanto a tabela quanto sua chave.
        DB::table('users')->where('id', $user->id)->delete();
        $this->assertNull($revision->fresh()->criado_por);
        $this->assertNull($asset->fresh()->enviado_por);
        $this->assertNull(AuditLog::firstOrFail()->usuario_id);
        $asset->delete();
        $this->assertDatabaseCount('referencias_midia', 0);
        $revision->delete();
        $this->assertNull($page->fresh()->revisao_publicada_id);
        $this->assertDatabaseCount('solicitacoes_revisao', 0);
    }

    public function test_conflicting_table_is_reported_before_any_rename(): void
    {
        Schema::create('page_revisions', fn (Blueprint $table) => $table->id());
        try {
            $this->migration()->down();
            $this->fail('O conflito deveria impedir a migração.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('coexistem', $exception->getMessage());
            $this->assertTrue(Schema::hasColumn('users', 'perfil'));
            $this->assertFalse(Schema::hasColumn('users', 'role'));
            $this->assertTrue(Schema::hasColumn('paginas', 'pagina_publicada_em'));
        } finally {
            Schema::dropIfExists('page_revisions');
        }
    }

    public function test_interrupted_rename_can_be_resumed_without_resetting_data(): void
    {
        $user = User::factory()->create();
        $migration = $this->migration();
        $migration->down();
        Schema::table('users', fn (Blueprint $table) => $table->renameColumn('role', 'perfil'));
        Schema::rename('media_assets', 'midias');
        $migration->up();
        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertTrue(Schema::hasColumn('midias', 'caminho'));
        $this->assertFalse(Schema::hasTable('media_assets'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_19_120000_padronizar_nomenclatura_do_dominio.php');
    }

    private function snapshot(array $tables): array
    {
        $rows = [];
        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $rows[$table] = DB::table($table)->orderBy($columns[0])->get()->map(fn ($row) => (array) $row)->all();
        }

        return $rows;
    }
}
