<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_manager_requires_authentication(): void
    {
        $this->get(route('gestao-conteudo.index'))
            ->assertRedirect(route('login'));
    }

    public function test_page_manager_lists_all_pages(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $first = $this->page('Página inicial', 'home');
        $second = $this->page('Agenda cultural');

        $this->actingAs($user)
            ->get(route('gestao-conteudo.index'))
            ->assertOk()
            ->assertSee('Páginas criadas')
            ->assertSee($first->pagina_titulo)
            ->assertSee($second->pagina_titulo)
            ->assertSee(route('gestao-conteudo.edit', $second), false)
            ->assertSee(route('gestao-conteudo.destroy', $second), false);
    }

    public function test_authenticated_user_can_delete_a_page(): void
    {
        $user = User::factory()->create();
        $this->page('Página inicial', 'home');
        $page = $this->page('Página temporária');

        $this->actingAs($user)
            ->delete(route('gestao-conteudo.destroy', $page))
            ->assertRedirect(route('gestao-conteudo.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('paginas', ['id' => $page->id]);
    }

    public function test_authenticated_user_can_create_and_update_a_page(): void
    {
        $user = User::factory()->create();
        $this->page('Página inicial', 'home');

        $response = $this->actingAs($user)
            ->post(route('gestao-conteudo.store'), [
                'pagina_titulo' => 'Nova exposição',
                'pagina_tipo_layout' => 'interna',
            ]);

        $page = Pagina::where('pagina_titulo', 'Nova exposição')->firstOrFail();
        $response->assertRedirect(route('gestao-conteudo.edit', $page));

        $this->actingAs($user)
            ->put(route('gestao-conteudo.update', $page), [
                'pagina_titulo' => 'Exposição atualizada',
                'pagina_tipo_layout' => 'interna',
                'pagina_ativo' => false,
            ])
            ->assertRedirect(route('gestao-conteudo.index'));

        $this->assertDatabaseHas('paginas', [
            'id' => $page->id,
            'pagina_titulo' => 'Exposição atualizada',
            'pagina_tipo_layout' => 'interna',
            'pagina_ativo' => false,
        ]);
    }

    public function test_page_validation_rejects_invalid_data(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('gestao-conteudo.store'), [
                'pagina_titulo' => '',
                'pagina_tipo_layout' => 'desconhecido',
            ])
            ->assertSessionHasErrors(['pagina_titulo', 'pagina_tipo_layout']);

        $this->assertDatabaseCount('paginas', 0);
    }

    public function test_last_page_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $page = $this->page('Única página', 'home');

        $this->actingAs($user)
            ->delete(route('gestao-conteudo.destroy', $page))
            ->assertRedirect(route('gestao-conteudo.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('paginas', ['id' => $page->id]);
    }

    public function test_editor_contains_the_page_delete_action(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $page = $this->page('Página editável');
        $this->page('Outra página');

        $this->actingAs($user)
            ->get(route('gestao-conteudo.edit', $page))
            ->assertOk()
            ->assertSee('deleteCurrentPageForm', false)
            ->assertSee(route('gestao-conteudo.destroy', $page), false);
    }

    private function page(string $title, string $layout = 'interna'): Pagina
    {
        return Pagina::create([
            'pagina_titulo' => $title,
            'pagina_tipo_layout' => $layout,
            'pagina_ativo' => true,
        ]);
    }
}
