<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * El código de verificación de 6 dígitos.
     * Al ser pública, esta propiedad estará disponible automáticamente en tu vista de Blade.
     */
    public string $code;

    /**
     * Crea una nueva instancia del mensaje.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Define el "sobre" del mensaje (asunto, remitente, etc.).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu Código de Verificación',
        );
    }

    /**
     * Define el contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            // Apunta al archivo de la vista que diseñará el cuerpo del email.
            view: 'emails.two-factor-code',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
