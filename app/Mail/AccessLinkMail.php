<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccessLinkMail extends Mailable
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $accessUrl,
        public readonly CarbonInterface $expiresAt,
        public readonly bool $isInvitation = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->isInvitation ? 'TotemSync — convite de acesso' : 'TotemSync — redefinição de senha');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.access-link');
    }
}
