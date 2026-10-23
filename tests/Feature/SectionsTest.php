<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\Secao;
use App\Models\Totem;
use App\Models\User;
use App\Support\PlayerManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_rename_sections_and_manager_groups_pages(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())->from(route('gestao-conteudo.index'))
            ->post(route('secoes.store'), ['nome' => 'História'])->assertRedirect(route('gestao-conteudo.index'));
        $section = Secao::firstOrFail();
        $this->put(route('secoes.update', $section), ['nome' => 'Memória', 'versao' => 1])->assertRedirect();
        $page = Pagina::factory()->create(['secao_id' => $section->id]);
        $this->get(route('gestao-conteudo.index'))->assertOk()->assertSee('Memória')
            ->assertSee('data-section-heading="'.$section->id.'"', false)->assertSee($page->pagina_titulo);
        $this->post(route('secoes.store'), ['nome' => 'Memória'])->assertSessionHasErrors('nome');
    }

    public function test_new_section_pages_and_duplicates_are_appended_without_publication(): void
    {
        Pagina::factory()->home()->create();
        $section = Secao::create(['nome' => 'História']);
        $existing = Pagina::factory()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 7]);
        $this->actingAs(User::factory()->create())->post(route('gestao-conteudo.store'), [
            'pagina_titulo' => 'Nova página', 'pagina_tipo_layout' => 'interna', 'secao_id' => $section->id,
        ])->assertRedirect();
        $new = Pagina::where('pagina_titulo', 'Nova página')->firstOrFail();
        $this->assertSame(8, $new->ordem_slide);
        $this->assertNull($new->pagina_publicada_em);
        $this->post(route('gestao-conteudo.duplicate', $existing))->assertRedirect();
        $copy = Pagina::where('pagina_titulo', $existing->pagina_titulo.' (cópia)')->firstOrFail();
        $this->assertSame(9, $copy->ordem_slide);
        $this->assertSame($section->id, $copy->secao_id);
        $this->assertNull($copy->pagina_publicada_em);
    }

    public function test_reordering_preserves_content_and_timestamps_and_rejects_stale_saves(): void
    {
        $section = Secao::create(['nome' => 'História']);
        $first = Pagina::factory()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 1]);
        $second = Pagina::factory()->create(['secao_id' => $section->id, 'ordem_slide' => 2, 'pagina_dados_editor' => ['pages' => []]]);
        $before = $first->fresh()->getAttributes();
        $this->travel(1)->hours();
        $this->actingAs(User::factory()->create())->put(route('secoes.reorder', $section), [
            'paginas' => [$second->id, $first->id], 'versao' => 1,
        ])->assertRedirect();
        $after = $first->fresh()->getAttributes();
        unset($before['ordem_slide'], $after['ordem_slide']);
        $this->assertEquals($before, $after);
        $this->assertSame(2, $first->fresh()->ordem_slide);
        $this->assertNull($second->fresh()->pagina_publicada_em);
        $this->assertSame(['pages' => []], $second->fresh()->pagina_dados_editor);
        $this->putJson(route('secoes.reorder', $section), ['paginas' => [$first->id, $second->id], 'versao' => 1])->assertConflict();
        $this->assertSame(2, $first->fresh()->ordem_slide);
    }

    public function test_reordering_rejects_missing_duplicate_and_foreign_pages(): void
    {
        $section = Secao::create(['nome' => 'História']);
        $first = Pagina::factory()->create(['secao_id' => $section->id]);
        $second = Pagina::factory()->create(['secao_id' => $section->id]);
        $foreign = Pagina::factory()->create();
        $this->actingAs(User::factory()->create());
        foreach ([[$first->id], [$first->id, $first->id], [$first->id, $foreign->id]] as $ids) {
            $this->putJson(route('secoes.reorder', $section), ['paginas' => $ids, 'versao' => 1])->assertUnprocessable();
        }
        $this->assertSame(1, $section->fresh()->versao);
        $this->assertSame($section->id, $second->fresh()->secao_id);
    }

    public function test_moving_pages_invalidates_order_forms_and_detaching_does_not_rejoin_legacy_groups(): void
    {
        $first = Secao::create(['nome' => 'História']);
        $second = Secao::create(['nome' => 'Acervo']);
        $page = Pagina::factory()->published()->create(['secao_id' => $first->id, 'grupo_slide' => 'Legado']);
        $this->actingAs(User::factory()->create());
        $data = ['pagina_titulo' => $page->pagina_titulo, 'pagina_tipo_layout' => 'interna', 'pagina_ativo' => true];
        $this->put(route('gestao-conteudo.update', $page), $data + ['secao_id' => $second->id])->assertRedirect();
        $this->assertSame($second->id, $page->fresh()->secao_id);
        $this->assertSame(2, $first->fresh()->versao);
        $this->assertSame(2, $second->fresh()->versao);
        $this->put(route('gestao-conteudo.update', $page), $data + ['secao_id' => null])->assertRedirect();
        $this->assertNull($page->fresh()->secao_id);
        $this->assertNull($page->fresh()->grupo_slide);
        $this->assertNotNull($page->fresh()->pagina_publicada_em);
    }

    public function test_editors_cannot_change_live_section_structure_but_can_add_drafts(): void
    {
        Pagina::factory()->home()->create();
        $section = Secao::create(['nome' => 'História']);
        $page = Pagina::factory()->published()->create(['secao_id' => $section->id]);
        $this->actingAs(User::factory()->create(['perfil' => 'editor']));
        $this->postJson(route('secoes.store'), ['nome' => 'Acervo'])->assertForbidden();
        $this->putJson(route('secoes.update', $section), ['nome' => 'Acervo', 'versao' => 1])->assertForbidden();
        $this->putJson(route('secoes.reorder', $section), ['paginas' => [$page->id], 'versao' => 1])->assertForbidden();
        $this->putJson(route('gestao-conteudo.update', $page), [
            'pagina_titulo' => $page->pagina_titulo, 'pagina_tipo_layout' => 'interna', 'pagina_ativo' => true, 'secao_id' => null,
        ])->assertForbidden();
        $this->post(route('gestao-conteudo.store'), [
            'pagina_titulo' => 'Rascunho', 'pagina_tipo_layout' => 'interna', 'secao_id' => $section->id,
        ])->assertRedirect();
        $this->assertNull(Pagina::where('pagina_titulo', 'Rascunho')->firstOrFail()->pagina_publicada_em);
    }

    public function test_home_stays_outside_sections_and_promoting_a_member_detaches_it(): void
    {
        $home = Pagina::factory()->home()->published()->create();
        $section = Secao::create(['nome' => 'História']);
        $page = Pagina::factory()->published()->create(['secao_id' => $section->id]);
        $this->actingAs(User::factory()->create())->putJson(route('gestao-conteudo.update', $home), [
            'pagina_titulo' => $home->pagina_titulo, 'pagina_tipo_layout' => 'home', 'pagina_ativo' => true, 'secao_id' => $section->id,
        ])->assertUnprocessable();
        $this->post(route('gestao-conteudo.set-home', $page))->assertRedirect();
        $this->assertNull($page->fresh()->secao_id);
        $this->assertSame('home', $page->fresh()->pagina_tipo_layout);
    }

    public function test_section_sequence_skips_unpublished_and_inactive_pages_and_loops_at_the_end(): void
    {
        $this->withoutVite();
        $section = Secao::create(['nome' => 'História']);
        $first = Pagina::factory()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 1]);
        Pagina::factory()->create(['secao_id' => $section->id, 'ordem_slide' => 2]);
        Pagina::factory()->inactive()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 3]);
        $last = Pagina::factory()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 4]);
        Pagina::factory()->published()->create(['ordem_slide' => 5]);
        $this->actingAs(User::factory()->create())->get(route('gestao-conteudo.preview', $first))
            ->assertOk()->assertViewHas('paginaAnteriorId', null)->assertViewHas('proximaPaginaId', $last->id)
            ->assertViewHas('entradasSecoes', fn ($entries) => $entries[$section->id] === $first->id)
            ->assertSee('data-section-navigation="1"', false);
        $this->get(route('gestao-conteudo.preview', $last))->assertOk()
            ->assertViewHas('paginaAnteriorId', $first->id)->assertViewHas('proximaPaginaId', $first->id);
    }

    public function test_standalone_pages_keep_existing_navigation_and_single_page_sections_do_not_self_link(): void
    {
        $this->withoutVite();
        $section = Secao::create(['nome' => 'História']);
        $member = Pagina::factory()->published()->create(['secao_id' => $section->id]);
        $first = Pagina::factory()->published()->create(['grupo_slide' => 'Legado', 'ordem_slide' => 1]);
        $last = Pagina::factory()->published()->create(['grupo_slide' => 'Legado', 'ordem_slide' => 2]);
        $this->actingAs(User::factory()->create())->get(route('gestao-conteudo.preview', $first))
            ->assertOk()->assertViewHas('proximaPaginaId', $last->id)->assertSee('data-section-navigation="0"', false);
        $this->get(route('gestao-conteudo.preview', $member))->assertOk()
            ->assertViewHas('proximaPaginaId', null)->assertViewHas('paginaAnteriorId', null);
    }

    public function test_section_order_and_names_change_manifest_but_never_publish_a_draft(): void
    {
        $section = Secao::create(['nome' => 'História']);
        $first = Pagina::factory()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 1]);
        $second = Pagina::factory()->published()->create(['secao_id' => $section->id, 'ordem_slide' => 2]);
        $draft = Pagina::factory()->create(['secao_id' => $section->id]);
        $totem = Totem::factory()->create();
        $before = PlayerManifest::make($totem);
        $this->actingAs(User::factory()->create())->put(route('secoes.reorder', $section), [
            'versao' => 1, 'paginas' => [$second->id, $draft->id, $first->id],
        ])->assertRedirect();
        $after = PlayerManifest::make($totem);
        $this->assertNotSame($before['version'], $after['version']);
        $this->assertNotContains($draft->id, array_column($after['pages'], 'id'));
        $this->put(route('secoes.update', $section), ['nome' => 'Acervo', 'versao' => 2])->assertRedirect();
        $this->assertNotSame($after['version'], PlayerManifest::make($totem)['version']);
    }

    public function test_section_card_attributes_survive_publication_sanitization(): void
    {
        $page = Pagina::factory()->create();
        $section = Secao::create(['nome' => 'História']);
        $this->actingAs(User::factory()->create())->postJson(route('editor-visual.publish', $page), [
            'base_version' => 1, 'project_data' => ['pages' => [['component' => ['tagName' => 'main']]]],
            'html' => '<article data-cultura-navigation="1" data-section-id="'.$section->id.'"><h2>História</h2></article>', 'css' => '',
        ])->assertOk();
        $this->assertStringContainsString('data-section-id="'.$section->id.'"', $page->fresh()->pagina_html_publicado);
    }
}
