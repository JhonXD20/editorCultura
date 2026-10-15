<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_contains_the_modern_form_and_theme_controls(): void
    {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertSee('editor-login-shell', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('editor-cultura-theme', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('secret-123'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_authenticated_layout_displays_the_user_menu_and_secure_logout(): void
    {
        $user = User::factory()->create([
            'name' => 'João Pereira',
            'email' => 'joao@example.test',
        ]);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('editor-user-menu', false)
            ->assertSee('editor-user-avatar', false)
            ->assertSee('João Pereira')
            ->assertSee('joao@example.test')
            ->assertSee('Sessão ativa')
            ->assertSee('Sair do sistema')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'senha-incorreta',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_root_redirects_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('inicio'))
            ->assertRedirect(route('admin'));
    }
}
