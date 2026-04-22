<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $securityCode
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Seguridad - Kartenant',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.security-code',
            with: [
                'user' => $this->user,
                'securityCode' => $this->securityCode,
            ],
        );
    }
}
