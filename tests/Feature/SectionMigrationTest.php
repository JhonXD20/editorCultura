<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SectionMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_existing_groups_content_and_order_survive_upgrade_and_rollback(): void
    {
        $migration = require database_path('migrations/2026_10_19_150000_create_secoes_table.php');
        $migration->down();
        foreach ([['home', 'História', 0], ['interna', 'História', 5], ['interna', 'História', 2], ['interna', null, 0]] as [$layout, $group, $order]) {
            DB::table('paginas')->insert([
                'pagina_titulo' => 'Página '.$order, 'pagina_tipo_layout' => $layout, 'pagina_ativo' => true,
                'grupo_slide' => $group, 'ordem_slide' => $order,
                'pagina_html_publicado' => '<main>Conteúdo preservado</main>',
                'pagina_css_publicado' => 'main{color:red}', 'pagina_dados_editor' => '{"pages":[]}',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $before = DB::table('paginas')->orderBy('id')->get()->toArray();
        $migration->up();
        $this->assertDatabaseCount('secoes', 1);
        $id = DB::table('secoes')->value('id');
        $this->assertNull(DB::table('paginas')->where('id', 1)->value('secao_id'));
        $this->assertSame($id, DB::table('paginas')->where('id', 2)->value('secao_id'));
        $this->assertSame($id, DB::table('paginas')->where('id', 3)->value('secao_id'));
        $this->assertNull(DB::table('paginas')->where('id', 4)->value('secao_id'));
        $migration->down();
        $this->assertFalse(Schema::hasTable('secoes'));
        $this->assertEquals($before, DB::table('paginas')->orderBy('id')->get()->toArray());
        $migration->up();
        $this->assertDatabaseCount('paginas', 4);
    }
}
