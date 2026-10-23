<?php

namespace App\Support;

use App\Mail\AccessLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AccessLinkDelivery
{
    public static function isConfigured(): bool
    {
        return self::delivers((string) config('mail.default'));
    }

    public static function assertConfigured(): void
    {
        if (! self::isConfigured()) {
            throw ValidationException::withMessages(['email' => 'O envio de e-mail não está configurado. Configure o SMTP antes de criar contas ou resetar senhas.']);
        }
    }

    public static function send(User $user): void
    {
        self::assertConfigured();
        try {
            // Caller holds the user lock and transaction. Failed delivery restores the previous token.
            $token = Password::createToken($user);
            $url = rtrim(config('app.url'), '/').route('password.reset', ['token' => $token, 'email' => $user->email], false);
            $expiresAt = now()->addMinutes((int) config('auth.passwords.users.expire'));
            // Synchronous delivery keeps access tokens out of queues and failed jobs.
            $sent = Mail::to($user->email)->send(new AccessLinkMail(
                $user->name, $url, $expiresAt, $user->convite_pendente,
            ));
            if ($sent === null && ! Mail::isFake()) {
                throw new \RuntimeException('Mensagem não aceita pelo transporte.');
            }
        } catch (Throwable) {
            // Transport exceptions can include the message. Never log or return their contents.
            throw ValidationException::withMessages(['email' => 'Não foi possível enviar o link por e-mail. Nenhuma alteração foi salva. Confira o serviço de e-mail e tente novamente.']);
        }
    }

    private static function delivers(string $mailer, array $visited = []): bool
    {
        if (in_array($mailer, $visited, true)) {
            return false;
        }
        $driver = config('mail.mailers.'.$mailer.'.transport');
        if ($driver === 'array') {
            return app()->environment('testing');
        }
        if (in_array($driver, ['failover', 'roundrobin'], true)) {
            $children = config('mail.mailers.'.$mailer.'.mailers', []);
            if ($children === []) {
                return false;
            }
            foreach ($children as $child) {
                if (! self::delivers($child, [...$visited, $mailer])) {
                    return false;
                }
            }

            return true;
        }

        return in_array($driver, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true);
    }
}
