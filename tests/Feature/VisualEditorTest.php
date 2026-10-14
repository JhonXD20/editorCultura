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
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $pagina->refresh();

        $this->assertSame('grapesjs-0.23', $pagina->editor_version);
        $this->assertSame('main', $pagina->editor_project_data['pages'][0]['component']['tagName']);
        $this->assertNull($pagina->published_at);
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
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $pagina->refresh();

        $this->assertNotNull($pagina->published_at);
        $this->assertStringContainsString('Memória', $pagina->published_html);
        $this->assertStringContainsString('/imagem.jpg', $pagina->published_html);
        $this->assertStringContainsString('fas fa-home', $pagina->published_html);
        $this->assertStringContainsString('data-cultura-navigation="1"', $pagina->published_html);
        $this->assertStringNotContainsString('<script', $pagina->published_html);
        $this->assertStringNotContainsString('onclick', $pagina->published_html);
        $this->assertStringNotContainsString('onerror', $pagina->published_html);
        $this->assertStringContainsString('@media (max-width: 480px)', $pagina->published_css);
    }

    public function test_publish_rejects_css_with_external_resources(): void
    {
        $pagina = $this->pagina();

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor-visual.publish', $pagina), [
                'project_data' => $this->safeProject(),
                'html' => '<main>Conteúdo</main>',
                'css' => '.hero { background-image: url("https://example.com/tracker.png"); }',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('publication');

        $this->assertNull($pagina->fresh()->published_at);
    }

    public function test_public_page_renders_the_published_version(): void
    {
        $this->withoutVite();

        $pagina = $this->pagina();
        $pagina->update([
            'published_html' => '<main><h1>Versão publicada</h1></main>',
            'published_css' => 'h1 { color: #174b35; }',
            'published_at' => now(),
        ]);

        $this->get(route('totem.exibir', $pagina))
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
            'published_html' => '<main data-cultura-responsive-version="2"><h1>Responsive</h1></main>',
            'published_css' => '@media (max-width: 480px) { h1 { width: 90%; } }',
            'published_at' => now(),
        ]);

        $this->get(route('totem.exibir', $pagina))
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
