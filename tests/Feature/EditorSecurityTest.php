<?php

namespace Tests\Feature;

use App\Models\Conteudo;
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

    public function test_upload_rejects_executable_files(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('conteudos.upload-imagem'), [
                'imagem' => UploadedFile::fake()->create(
                    'codigo.php',
                    10,
                    'application/x-php'
                ),
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('imagem');
    }

    public function test_bulk_save_cannot_update_content_from_another_page(): void
    {
        $paginaA = $this->pagina('Pagina A');
        $paginaB = $this->pagina('Pagina B');
        $conteudoB = Conteudo::create([
            'pagina_id' => $paginaB->id,
            'conteudo_tipo_componente' => 'texto',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => ['texto' => 'Original'],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('conteudos.salvar-em-massa'), [
                'pagina_id' => $paginaA->id,
                'blocos' => [[
                    'id' => $conteudoB->id,
                    'tipo' => 'texto',
                    'ordem' => 1,
                    'dados_conteudo' => ['texto' => 'Alterado'],
                ]],
            ]);

        $response->assertUnprocessable();
        $this->assertSame('Original', $conteudoB->fresh()->conteudo_dados_conteudo['texto']);
    }

    public function test_bulk_save_sanitizes_rich_text(): void
    {
        $pagina = $this->pagina('Pagina segura');
        $conteudo = Conteudo::create([
            'pagina_id' => $pagina->id,
            'conteudo_tipo_componente' => 'texto',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => ['texto' => 'Original'],
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('conteudos.salvar-em-massa'), [
                'pagina_id' => $pagina->id,
                'blocos' => [[
                    'id' => $conteudo->id,
                    'tipo' => 'texto',
                    'ordem' => 1,
                    'dados_conteudo' => [
                        'texto' => '<strong>Permitido</strong><script>alert(1)</script><span onclick="alert(2)">Texto</span>',
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $html = $conteudo->fresh()->conteudo_dados_conteudo['texto'];

        $this->assertStringContainsString('<strong>Permitido</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_empty_bulk_save_removes_all_page_content(): void
    {
        $pagina = $this->pagina('Pagina vazia');
        Conteudo::create([
            'pagina_id' => $pagina->id,
            'conteudo_tipo_componente' => 'texto',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => ['texto' => 'Remover'],
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('conteudos.salvar-em-massa'), [
                'pagina_id' => $pagina->id,
                'blocos' => [],
            ])
            ->assertOk();

        $this->assertDatabaseCount('conteudos', 0);
    }

    private function pagina(string $titulo): Pagina
    {
        return Pagina::create([
            'pagina_titulo' => $titulo,
            'pagina_tipo_layout' => 'interna',
            'pagina_ativo' => true,
        ]);
    }
}
