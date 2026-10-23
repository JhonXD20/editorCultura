<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_visual_editor_requires_authentication(): void
    {
        $pagina = $this->pagina();

        $this->putJson(route('editor-visual.save', $pagina), [
            'project_data' => $this->safeProject(),
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_save_a_safe_draft(): void
    {
        $pagina = $this->pagina();

        $this->actingAs(User::factory()->create())
            ->putJson(route('editor-visual.save', $pagina), [
                'project_data' => $this->safeProject(),
                'base_version' => $pagina->pagina_versao_rascunho,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'draft_version' => 2]);

        $pagina->refresh();

        $this->assertSame('grapesjs-0.23', $pagina->pagina_versao_editor);
        $this->assertSame('main', $pagina->pagina_dados_editor['pages'][0]['component']['tagName']);
        $this->assertNull($pagina->pagina_publicada_em);
    }

    public function test_authenticated_user_can_open_the_visual_editor(): void
    {
        $this->withoutVite();
        $pagina = $this->pagina();

        $this->actingAs(User::factory()->create())
            ->get(route('gestao-conteudo.edit', $pagina))
            ->assertOk()
            ->assertSee('Editor visual de páginas')
            ->assertSee('visualEditorConfig', false)
            ->assertSee('"publishUrl":', false);
    }

    public function test_draft_rejects_executable_components(): void
    {
        $pagina = $this->pagina();
        $project = $this->safeProject();
        $project['pages'][0]['component']['components'][] = [
            'tagName' => 'script',
            'content' => 'alert(1)',
        ];

        $this->actingAs(User::factory()->create())
            ->putJson(route('editor-visual.save', $pagina), [
                'project_data' => $project,
                'base_version' => $pagina->pagina_versao_rascunho,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_data');
    }

    public function test_publish_stores_only_sanitized_output(): void
    {
        $pagina = $this->pagina();

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor-visual.publish', $pagina), [
                'project_data' => $this->safeProject(),
                'html' => '<main><h1 onclick="alert(1)">Memória</h1><button data-cultura-navigation="1" data-page-id="2"><i class="fas fa-home" aria-hidden="true"></i><span>Início</span></button><script>alert(2)</script><img src="/imagem.jpg" onerror="alert(3)"></main>',
                'css' => '.titulo { color: #174b35; font-size: 40px; } @media (max-width: 480px) { .titulo { font-size: 24px; width: 90%; } }',
                'base_version' => $pagina->pagina_versao_rascunho,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'draft_version' => 2, 'publication_version' => 1])
            ->assertJsonStructure(['published_at']);

        $pagina->refresh();

        $this->assertNotNull($pagina->pagina_publicada_em);
        $this->assertStringContainsString('Memória', $pagina->pagina_html_publicado);
        $this->assertStringContainsString('/imagem.jpg', $pagina->pagina_html_publicado);
        $this->assertStringContainsString('fas fa-home', $pagina->pagina_html_publicado);
        $this->assertStringContainsString('data-cultura-navigation="1"', $pagina->pagina_html_publicado);
        $this->assertStringNotContainsString('<script', $pagina->pagina_html_publicado);
        $this->assertStringNotContainsString('onclick', $pagina->pagina_html_publicado);
        $this->assertStringNotContainsString('onerror', $pagina->pagina_html_publicado);
        $this->assertStringContainsString('@media (max-width: 480px)', $pagina->pagina_css_publicado);
    }

    public function test_publish_rejects_css_with_external_resources(): void
    {
        $pagina = $this->pagina();

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor-visual.publish', $pagina), [
                'project_data' => $this->safeProject(),
                'html' => '<main>Conteúdo</main>',
                'css' => '.hero { background-image: url("https://example.com/tracker.png"); }',
                'base_version' => $pagina->pagina_versao_rascunho,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('publication');

        $this->assertNull($pagina->fresh()->pagina_publicada_em);
    }

    public function test_public_page_renders_the_published_version(): void
    {
        $this->withoutVite();

        $pagina = $this->pagina();
        $pagina->update([
            'pagina_html_publicado' => '<main><h1>Versão publicada</h1></main>',
            'pagina_css_publicado' => 'h1 { color: #174b35; }',
            'pagina_publicada_em' => now(),
        ]);

        $this->actingAs(User::factory()->create())->get(route('gestao-conteudo.preview', $pagina))
            ->assertOk()
            ->assertSee('Versão publicada')
            ->assertSee('grapes-published-page', false)
            ->assertSee('data-responsive-version="legacy"', false);
    }

    public function test_public_page_identifies_the_current_responsive_format(): void
    {
        $this->withoutVite();

        $pagina = $this->pagina();
        $pagina->update([
            'pagina_html_publicado' => '<main data-cultura-responsive-version="2"><h1>Responsive</h1></main>',
            'pagina_css_publicado' => '@media (max-width: 480px) { h1 { width: 90%; } }',
            'pagina_publicada_em' => now(),
        ]);

        $this->actingAs(User::factory()->create())->get(route('gestao-conteudo.preview', $pagina))
            ->assertOk()
            ->assertSee('Responsive')
            ->assertSee('data-responsive-version="2"', false);
    }

    private function pagina(): Pagina
    {
        return Pagina::create([
            'pagina_titulo' => 'Página de teste',
            'pagina_tipo_layout' => 'home',
            'pagina_ativo' => true,
        ]);
    }

    private function safeProject(): array
    {
        return [
            'pages' => [[
                'id' => 'pagina-principal',
                'component' => [
                    'tagName' => 'main',
                    'components' => [[
                        'tagName' => 'h1',
                        'content' => 'Memória cultural',
                    ]],
                ],
            ]],
            'styles' => [],
            'assets' => [],
        ];
    }
}
