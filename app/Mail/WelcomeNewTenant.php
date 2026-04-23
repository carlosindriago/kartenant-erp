<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User; // <-- Añade este import
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeNewTenant extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public Tenant $tenant; // <-- Añade la propiedad para el tenant

    public string $temporaryPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Tenant $tenant, string $temporaryPassword)
    {
        $this->user = $user;
        $this->tenant = $tenant; // <-- Asigna el tenant
        $this->temporaryPassword = $temporaryPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido a Kartenant!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-tenant',
        );
    }
}
