<?php

namespace Tests\Feature;

use App\Mail\AccessLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccessLinkEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_contains_access_expiration_and_correct_link_without_exposing_password_to_admin(): void
    {
        config(['app.url' => 'https://totem.example.test']);
        $mail = new AccessLinkMail('Pessoa <Teste>', 'https://totem.example.test/password/reset/test-only-token?email=pessoa%40example.test', now()->addMinutes(30), true);
        $rendered = $mail->render();
        $this->assertStringContainsString('test-only-token', $rendered);
        $this->assertStringContainsString('https://totem.example.test/password/reset/', $rendered);
        $this->assertStringContainsString('Escolha sua própria senha', $rendered);
        $this->assertStringContainsString(e('Pessoa <Teste>'), $rendered);
        $this->assertStringContainsString(now()->addMinutes(30)->format('d/m/Y'), $rendered);
        $this->assertTrue($mail->isInvitation);
    }

    public function test_log_or_failover_to_log_cannot_create_accounts_or_replace_existing_credentials(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $target = User::factory()->create(['perfil' => 'editor']);
        $before = $target->fresh()->getRawOriginal();
        $oldToken = Password::createToken($target);
        $this->travel(61)->seconds();
        foreach (['log', 'failover'] as $mailer) {
            config(['mail.default' => $mailer]);
            $this->actingAs($admin)->postJson(route('usuarios.store'), [
                'name' => 'Blocked', 'email' => 'blocked@example.test', 'perfil' => 'editor',
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
            $this->postJson(route('usuarios.reset-password', $target), [
                'current_password' => 'password', 'confirmation_email' => $target->email,
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
        }
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertTrue(Password::tokenExists($target, $oldToken));
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.test']);
        Mail::assertNothingSent();
    }

    public function test_transport_failure_rolls_back_account_creation_and_never_returns_transport_secrets(): void
    {
        $admin = User::factory()->create();
        Mail::shouldReceive('to')->once()->with('failed@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp-secret-must-not-leak'));
        $response = $this->actingAs($admin)->postJson(route('usuarios.store'), [
            'name' => 'Failed', 'email' => 'failed@example.test', 'perfil' => 'editor',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertStringNotContainsString('smtp-secret-must-not-leak', $response->getContent());
        $this->assertDatabaseMissing('users', ['email' => 'failed@example.test']);
        $this->assertDatabaseMissing('registros_auditoria', ['acao' => 'user.created']);
    }

    public function test_transport_failure_preserves_previous_password_session_version_and_sessions(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['perfil' => 'editor', 'remember_token' => 'preserved-token']);
        DB::table('sessions')->insert([
            'id' => 'preserved-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time(),
        ]);
        $before = $target->fresh()->getRawOriginal();
        Mail::shouldReceive('to')->once()->with($target->email)->andReturnSelf();
        $oldToken = Password::createToken($target);
        $this->travel(61)->seconds();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('temporary-secret-must-not-leak'));
        $response = $this->actingAs($admin)->postJson(route('usuarios.reset-password', $target), [
            'current_password' => 'password', 'confirmation_email' => $target->email,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertStringNotContainsString('temporary-secret-must-not-leak', $response->getContent());
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertDatabaseHas('sessions', ['id' => 'preserved-session', 'user_id' => $target->id]);
        $this->assertTrue(Password::tokenExists($target, $oldToken));
        $this->assertDatabaseMissing('registros_auditoria', ['acao' => 'user.access_link_requested']);
    }

    public function test_successful_own_reset_sends_link_without_logging_out_or_exposing_token(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->post(route('usuarios.reset-password', $admin), [
            'current_password' => 'password', 'confirmation_email' => $admin->email,
        ])->assertRedirect(route('usuarios.index'));
        $this->assertAuthenticatedAs($admin);
        $response->assertSessionMissing('temporary_password');
        Mail::assertSent(AccessLinkMail::class, fn ($mail) => $mail->hasTo($admin->email) && ! $mail->isInvitation);
        $url = Mail::sent(AccessLinkMail::class)->sole()->accessUrl;
        $this->withoutVite()->get(route('usuarios.index'))->assertOk()->assertDontSee($url);
        $this->assertSame(1, $admin->fresh()->versao_sessao);
    }

    public function test_real_mail_rendering_can_be_accepted_by_the_isolated_array_transport(): void
    {
        config(['mail.default' => 'array']);
        $this->actingAs(User::factory()->create())->postJson(route('usuarios.store'), [
            'name' => 'Mail render', 'email' => 'render@example.test', 'perfil' => 'editor',
        ])->assertOk()->assertJsonMissingPath('temporary_password');
        $this->assertDatabaseHas('users', ['email' => 'render@example.test', 'convite_pendente' => true]);
    }

    public function test_cancelled_message_does_not_create_an_account_or_claim_success(): void
    {
        config(['mail.default' => 'array']);
        Event::listen(MessageSending::class, fn () => false);
        $this->actingAs(User::factory()->create())->postJson(route('usuarios.store'), [
            'name' => 'Cancelled', 'email' => 'cancelled@example.test', 'perfil' => 'editor',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'cancelled@example.test']);
    }
}
