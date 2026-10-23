<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EditorSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_visual_upload_rejects_executable_files(): void
    {
        $this->actingAs(User::factory()->create(['perfil' => 'editor']))
            ->postJson(route('editor-visual.upload'), [
                'files' => [UploadedFile::fake()->create('codigo.php', 10, 'application/x-php')],
            ])->assertUnprocessable()->assertJsonValidationErrors('files.0');
    }

    public function test_editor_cannot_publish_or_delete(): void
    {
        $home = Pagina::factory()->home()->create();
        $page = Pagina::factory()->create();
        $editor = User::factory()->create(['perfil' => 'editor']);
        $project = ['pages' => [['component' => ['tagName' => 'main']]]];

        $this->actingAs($editor)->postJson(route('editor-visual.publish', $page), [
            'project_data' => $project,
            'html' => '<main>Teste</main>',
            'css' => '',
            'base_version' => $page->pagina_versao_rascunho,
        ])->assertForbidden();
        $this->actingAs($editor)->delete(route('gestao-conteudo.destroy', $page))->assertForbidden();
        $this->assertDatabaseHas('paginas', ['id' => $home->id]);
        $this->assertDatabaseHas('paginas', ['id' => $page->id]);
    }
}
