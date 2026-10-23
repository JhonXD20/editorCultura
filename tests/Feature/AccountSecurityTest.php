<?php

namespace Tests\Feature;

use App\Mail\AccessLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_new_account_requires_email_invitation_and_user_chooses_own_password(): void
    {
        $response = $this->actingAs(User::factory()->create())->postJson(route('usuarios.store'), [
            'name' => 'Novo editor', 'email' => 'novo@example.test', 'data_nascimento' => '1994-03-07', 'perfil' => 'editor',
            'password' => 'ignored-arbitrary-password', 'troca_senha_obrigatoria' => false,
        ])->assertOk();
        $response->assertJsonMissingPath('temporary_password');
        $this->assertFalse(session()->has('temporary_password'));
        Mail::assertSent(AccessLinkMail::class, fn ($mail) => $mail->hasTo('novo@example.test') && $mail->isInvitation);
        Mail::assertSentCount(1);
        $mail = Mail::sent(AccessLinkMail::class)->sole();
        $this->assertStringNotContainsString($mail->accessUrl, $response->getContent());
        $token = basename(parse_url($mail->accessUrl, PHP_URL_PATH));
        $user = User::where('email', 'novo@example.test')->firstOrFail();
        $this->assertFalse(Hash::check('ignored-arbitrary-password', $user->password));
        $this->assertTrue($user->convite_pendente);
        $this->assertNull($user->senha_temporaria_expira_em);
        $this->assertFalse($user->troca_senha_obrigatoria);
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->assertNotSame($token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));
        $this->assertArrayNotHasKey('data_nascimento', $user->toArray());
        $this->post(route('logout'));
        $this->postJson(route('login'), ['email' => $user->email, 'password' => 'ignored-arbitrary-password'])->assertUnprocessable();
        $this->withoutVite()->get($mail->accessUrl)->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertSee('minlength="8"', false);
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->postJson(route('password.update'), [
            'token' => $token, 'email' => $user->email, 'password' => '12345678', 'password_confirmation' => '12345678',
        ])->assertOk()->assertJsonPath('redirect', route('login'));
        $this->assertGuest();
        $this->assertFalse($user->fresh()->convite_pendente);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->assertFalse($user->fresh()->troca_senha_obrigatoria);
        $this->assertNull($user->fresh()->senha_temporaria_expira_em);
        $this->assertStringNotContainsString($token, DB::table('registros_auditoria')->get()->toJson());
        $this->assertTrue(Hash::check('12345678', $user->fresh()->password));
        $this->post(route('login'), ['email' => $user->email, 'password' => '12345678'])->assertRedirect(route('admin'));
        $this->get(route('gestao-conteudo.index'))->assertOk();
        $this->assertSame(2, $user->fresh()->versao_sessao);
    }

    public function test_birth_date_credentials_are_refused_even_without_the_temporary_flag(): void
    {
        $user = User::factory()->create(['data_nascimento' => '1988-12-05', 'password' => '05121988']);
        $this->postJson(route('login'), ['email' => $user->email, 'password' => '05121988'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->assertTrue(Hash::check('05121988', $user->fresh()->password));
    }

    public function test_invalid_new_password_does_not_release_the_restriction(): void
    {
        $user = User::factory()->create(['data_nascimento' => '1988-12-05', 'password' => '05121988']);
        $user->forceFill(['troca_senha_obrigatoria' => true])->save();
        $this->actingAs($user)->putJson(route('account.password.update'), [
            'current_password' => 'wrong', 'password' => 'uma-nova-senha-segura', 'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['current_password', 'password']);
        $this->putJson(route('account.password.update'), [
            'current_password' => '05121988', 'password' => '05121988', 'password_confirmation' => '05121988',
        ])->assertUnprocessable();
        $this->putJson(route('account.password.update'), [
            'current_password' => '05121988', 'password' => '1234567', 'password_confirmation' => '1234567',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertTrue($user->fresh()->troca_senha_obrigatoria);
        $this->assertTrue(Hash::check('05121988', $user->fresh()->password));
    }

    public function test_existing_accounts_are_not_reset_and_birth_date_is_not_required_for_creation(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->postJson(route('usuarios.store'), [
            'name' => 'Sem data', 'email' => 'sem@example.test', 'perfil' => 'editor',
        ])->assertOk()->assertJsonMissingPath('temporary_password');
        Mail::assertSent(AccessLinkMail::class, fn ($mail) => $mail->hasTo('sem@example.test'));
        $this->assertFalse($admin->fresh()->troca_senha_obrigatoria);
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_only_admin_can_reset_and_revoke_with_reauthentication_and_matching_email(): void
    {
        $target = User::factory()->create(['data_nascimento' => '1990-01-02', 'perfil' => 'editor']);
        $this->actingAs(User::factory()->create(['perfil' => 'editor']))
            ->postJson(route('usuarios.reset-password', $target), [])->assertForbidden();
        $this->postJson(route('usuarios.revoke-sessions', $target), [])->assertForbidden();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('usuarios.reset-password', $target), [
            'current_password' => 'wrong', 'confirmation_email' => 'wrong@example.test',
        ])->assertUnprocessable()->assertJsonValidationErrors(['current_password', 'confirmation_email']);
        $this->postJson(route('usuarios.revoke-sessions', $target), [])->assertUnprocessable();
        $this->assertSame(1, $target->fresh()->versao_sessao);
        $response = $this->postJson(route('usuarios.reset-password', $target), [
            'current_password' => 'password', 'confirmation_email' => $target->email,
        ])->assertOk();
        $response->assertJsonMissingPath('temporary_password');
        Mail::assertSent(AccessLinkMail::class, fn ($mail) => $mail->hasTo($target->email) && ! $mail->isInvitation);
        $this->assertTrue(Hash::check('password', $target->fresh()->password));
        $this->assertFalse(Hash::check('02011990', $target->fresh()->password));
        $this->assertFalse($target->fresh()->troca_senha_obrigatoria);
        $this->assertSame(1, $target->fresh()->versao_sessao);
        $this->postJson(route('usuarios.revoke-sessions', $target), [
            'current_password' => 'password', 'confirmation_email' => $target->email,
        ])->assertOk();
        $this->assertSame(2, $target->fresh()->versao_sessao);
        $this->actingAs($target->fresh())->withSession(['auth_session_version' => 1])
            ->getJson(route('account.password.edit'))->assertUnauthorized();
    }

    public function test_reset_without_birth_date_sends_link_and_preserves_password(): void
    {
        $target = User::factory()->create();
        $response = $this->actingAs(User::factory()->create())->postJson(route('usuarios.reset-password', $target), [
            'current_password' => 'password', 'confirmation_email' => $target->email,
        ])->assertOk();
        $response->assertJsonMissingPath('temporary_password');
        Mail::assertSent(AccessLinkMail::class, fn ($mail) => $mail->hasTo($target->email));
        $this->assertTrue(Hash::check('password', $target->fresh()->password));
        $this->assertSame(1, $target->fresh()->versao_sessao);
    }

    public function test_expired_temporary_password_cannot_log_in_or_use_an_existing_session(): void
    {
        $user = User::factory()->create(['password' => 'expired-password']);
        $user->forceFill(['troca_senha_obrigatoria' => true, 'senha_temporaria_expira_em' => now()->subMinute()])->save();
        $this->postJson(route('login'), ['email' => $user->email, 'password' => 'expired-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->getJson(route('account.password.edit'))->assertUnauthorized();
    }

    public function test_profile_update_requires_current_password_and_cannot_change_role_or_birth_date(): void
    {
        $user = User::factory()->create(['perfil' => 'editor']);
        $data = ['name' => 'Nome alterado', 'email' => 'alterado@example.test', 'perfil' => 'admin', 'data_nascimento' => '1990-01-01'];
        $this->actingAs($user)->putJson(route('account.update'), $data)->assertUnprocessable();
        $this->putJson(route('account.update'), $data + ['current_password' => 'password'])->assertOk();
        $this->assertSame('editor', $user->fresh()->perfil);
        $this->assertNull($user->fresh()->data_nascimento);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertSame('Nome alterado', $user->fresh()->name);
    }

    public function test_revoking_own_sessions_logs_out_immediately(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->postJson(route('usuarios.revoke-sessions', $admin), [
            'current_password' => 'password', 'confirmation_email' => $admin->email,
        ])->assertOk()->assertJsonPath('redirect', route('login'));
        $this->assertGuest();
        $this->assertSame(2, $admin->fresh()->versao_sessao);
    }

    public function test_email_recovery_also_clears_temporary_password_and_revokes_previous_sessions(): void
    {
        $user = User::factory()->create(['data_nascimento' => '1990-01-02', 'password' => '02011990']);
        $user->forceFill(['troca_senha_obrigatoria' => true])->save();
        $token = Password::createToken($user);
        $this->postJson(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'abcdefg', 'password_confirmation' => 'abcdefg',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertTrue($user->fresh()->troca_senha_obrigatoria);
        $this->assertTrue(Hash::check('02011990', $user->fresh()->password));
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh',
        ])->assertRedirect(route('login'));
        $this->assertFalse($user->fresh()->troca_senha_obrigatoria);
        $this->assertTrue(Hash::check('abcdefgh', $user->fresh()->password));
        $this->assertSame(2, $user->fresh()->versao_sessao);
        $this->assertGuest();
        $this->withoutVite()->get(route('admin'))->assertRedirect(route('login'));
    }

    public function test_partial_form_validation_returns_json_even_when_the_form_expects_html(): void
    {
        $this->actingAs(User::factory()->create())->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html',
        ])->post(route('gestao-conteudo.store'), [])->assertUnprocessable()
            ->assertJsonValidationErrors('pagina_titulo');
    }
}
