<?php

namespace Tests\Feature;

use App\Mail\AccessLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AccessLinkSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function redeem(User $user, string $token): TestResponse
    {
        return $this->postJson(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'uma-nova-senha-segura', 'password_confirmation' => 'uma-nova-senha-segura',
        ]);
    }

    public function test_only_first_redemption_succeeds_and_revokes_all_old_access(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember-token']);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $token = Password::createToken($user);
        $this->redeem($user, $token)->assertOk()->assertJsonPath('redirect', route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertSame(2, $user->fresh()->versao_sessao);
        $before = $user->fresh()->getRawOriginal();
        $this->redeem($user, $token)->assertUnprocessable();
        $this->assertSame($before, $user->fresh()->getRawOriginal());
        $this->actingAs($user->fresh())->withSession(['auth_session_version' => 1])->getJson(route('admin'))->assertUnauthorized();
    }

    public function test_expired_link_does_not_change_credentials_or_activate_invitation(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['convite_pendente' => true])->save();
        $token = Password::createToken($user);
        $before = $user->fresh()->getRawOriginal();
        $this->travel(31)->minutes();
        $this->redeem($user, $token)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame($before, $user->fresh()->getRawOriginal());
    }

    public function test_tampered_token_wrong_email_and_disabled_account_cannot_redeem(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = Password::createToken($user);
        $this->redeem($user, str_repeat('x', 64))->assertUnprocessable();
        $this->redeem($other, $token)->assertUnprocessable();
        $user->update(['ativo' => false]);
        $this->redeem($user, $token)->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertSame(1, $user->fresh()->versao_sessao);
    }

    public function test_reissue_invalidates_previous_token_but_keeps_current_credentials_and_sessions(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $before = $user->fresh()->getRawOriginal();
        $this->travel(61)->seconds();
        config(['app.url' => 'https://trusted.example.test']);
        $response = $this->withHeader('Host', 'untrusted.example.test')->postJson(route('password.email'), ['email' => $user->email])->assertOk();
        $mail = Mail::sent(AccessLinkMail::class)->sole();
        $this->assertStringStartsWith('https://trusted.example.test/password/reset/', $mail->accessUrl);
        $this->assertStringNotContainsString($mail->accessUrl, $response->getContent());
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->assertTrue(Password::tokenExists($user, basename(parse_url($mail->accessUrl, PHP_URL_PATH))));
        $this->assertSame($before, $user->fresh()->getRawOriginal());
    }

    public function test_public_response_is_the_same_for_unknown_inactive_throttled_and_active_accounts(): void
    {
        $active = User::factory()->create();
        $inactive = User::factory()->create(['ativo' => false]);
        $messages = [];
        foreach (['unknown@example.test', $inactive->email, $active->email, $active->email] as $email) {
            $messages[] = $this->postJson(route('password.email'), ['email' => $email])->assertOk()->json();
        }
        $this->assertCount(1, array_unique(array_map('json_encode', $messages)));
        Mail::assertSentCount(1);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $inactive->email]);
    }

    public function test_public_delivery_failure_is_generic_and_preserves_previous_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->travel(61)->seconds();
        config(['mail.default' => 'log']);
        $expected = $this->postJson(route('password.email'), ['email' => 'unknown@example.test'])->assertOk()->json();
        $this->postJson(route('password.email'), ['email' => $user->email])->assertOk()->assertExactJson($expected);
        $this->assertTrue(Password::tokenExists($user, $token));
        Mail::assertNothingSent();
    }

    public function test_requests_are_rate_limited_per_ip_and_reissues_per_account(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $this->postJson(route('password.email'), ['email' => $user->email])->assertOk();
        $this->actingAs($admin)->postJson(route('usuarios.reset-password', $user), [
            'current_password' => 'password', 'confirmation_email' => $user->email,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->post(route('logout'));
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('password.email'), ['email' => $user->email])->assertOk();
        }
        $this->postJson(route('password.email'), ['email' => $user->email])->assertStatus(429);
        Mail::assertSentCount(1);
    }

    public function test_pending_account_cannot_use_password_or_preexisting_session(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['convite_pendente' => true])->save();
        $this->postJson(route('login'), ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
        $this->assertGuest();
        $this->actingAs($user)->getJson(route('admin'))->assertUnauthorized();
    }

    public function test_pending_administrator_does_not_allow_removal_of_last_usable_administrator(): void
    {
        $admin = User::factory()->create();
        $pending = User::factory()->create();
        $pending->forceFill(['convite_pendente' => true])->save();
        $this->actingAs($admin)->putJson(route('usuarios.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'perfil' => 'editor', 'ativo' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('perfil');
        $this->assertSame('admin', $admin->fresh()->perfil);
    }

    public function test_administrative_email_change_invalidates_links_even_if_old_address_is_reused(): void
    {
        $user = User::factory()->create(['perfil' => 'editor']);
        $token = Password::createToken($user);
        $this->actingAs(User::factory()->create())->putJson(route('usuarios.update', $user), [
            'name' => $user->name, 'email' => 'updated@example.test', 'perfil' => 'editor', 'ativo' => true,
        ])->assertOk();
        $replacement = User::factory()->create(['email' => $user->email]);
        $this->assertFalse(Password::tokenExists($replacement, $token));
    }

    public function test_tokens_are_not_flashed_to_session_on_invalid_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))->post(route('password.update'), [
            'email' => $user->email, 'token' => $token, 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password')->assertSessionMissing('_old_input.token')->assertSessionMissing('_old_input.password');
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_profile_email_change_and_authenticated_password_change_invalidate_old_links(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->actingAs($user)->putJson(route('account.update'), [
            'name' => $user->name, 'email' => 'changed@example.test', 'current_password' => 'password',
        ])->assertOk();
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $user = $user->fresh();
        $token = Password::createToken($user);
        $this->actingAs($user)->putJson(route('account.password.update'), [
            'current_password' => 'password', 'password' => 'outra-senha-segura', 'password_confirmation' => 'outra-senha-segura',
        ])->assertOk();
        $this->assertFalse(Password::tokenExists($user, $token));
    }
}
