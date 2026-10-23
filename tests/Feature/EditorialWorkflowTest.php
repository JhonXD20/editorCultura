<?php

namespace Tests\Feature;

use App\Models\PageRevision;
use App\Models\Pagina;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_submits_immutable_revision_and_admin_publishes_it(): void
    {
        $page = Pagina::factory()->create();
        $editor = User::factory()->create(['perfil' => 'editor']);
        $admin = User::factory()->create(['perfil' => 'admin']);
        $payload = $this->payload($page, '<main><h1>Versão aprovada</h1></main>');

        $this->actingAs($editor)->postJson(route('editor-visual.submit', $page), $payload)
            ->assertOk()->assertJsonPath('message', 'Conteúdo enviado para revisão.')
            ->assertJsonPath('draft_version', 2);
        $review = ReviewRequest::firstOrFail();
        $this->assertSame('pending', $review->situacao);
        $this->assertSame('pending_review', $page->fresh()->pagina_estado_editorial);

        $this->actingAs($admin)->post(route('reviews.approve', $review))->assertSessionHas('success');
        $page->refresh();
        $this->assertSame('published', $page->pagina_estado_editorial);
        $this->assertSame('<main><h1>Versão aprovada</h1></main>', $page->pagina_html_publicado);
        $this->assertSame($review->revisao_pagina_id, $page->revisao_publicada_id);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_return_requires_comment_and_conflicting_draft_returns_409(): void
    {
        $page = Pagina::factory()->create();
        $editor = User::factory()->create(['perfil' => 'editor']);
        $admin = User::factory()->create(['perfil' => 'admin']);
        $this->actingAs($editor)->postJson(route('editor-visual.submit', $page), $this->payload($page, '<main>Revisar</main>'))
            ->assertOk();
        $review = ReviewRequest::firstOrFail();

        $this->actingAs($admin)->post(route('reviews.reject', $review), [])->assertSessionHasErrors('comment');
        $this->actingAs($admin)->post(route('reviews.reject', $review), ['comment' => 'Ajuste o título.'])
            ->assertSessionHas('success');
        $this->assertSame('changes_requested', $page->fresh()->pagina_estado_editorial);

        $this->actingAs($editor)->putJson(route('editor-visual.save', $page), [
            'project_data' => $this->project(),
            'base_version' => 1,
        ])->assertConflict();
    }

    public function test_restore_creates_a_new_revision_without_changing_the_published_snapshot(): void
    {
        $page = Pagina::factory()->create(['pagina_html_publicado' => '<main>Atual</main>', 'pagina_publicada_em' => now()]);
        $admin = User::factory()->create(['perfil' => 'admin']);
        $revision = PageRevision::create([
            'pagina_id' => $page->id,
            'criado_por' => $admin->id,
            'versao' => 1,
            'estado' => 'published',
            'dados_projeto' => $this->project(),
            'html' => '<main>Antiga</main>',
            'css' => '',
            'hash_verificacao' => hash('sha256', 'old'),
        ]);

        $this->actingAs($admin)->post(route('reviews.restore', [$page, $revision]))
            ->assertRedirect(route('gestao-conteudo.edit', $page));
        $this->assertDatabaseHas('revisoes_pagina', ['pagina_id' => $page->id, 'versao' => 2, 'estado' => 'draft']);
        $this->assertSame('<main>Atual</main>', $page->fresh()->pagina_html_publicado);
    }

    private function payload(Pagina $page, string $html): array
    {
        return ['project_data' => $this->project(), 'html' => $html, 'css' => '', 'base_version' => $page->pagina_versao_rascunho];
    }

    private function project(): array
    {
        return ['pages' => [['id' => 'main', 'component' => ['tagName' => 'main', 'components' => []]]], 'styles' => [], 'assets' => []];
    }
}
