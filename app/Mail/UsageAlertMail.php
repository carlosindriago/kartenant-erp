<?php

namespace App\Mail;

use App\Models\UsageAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsageAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public UsageAlert $alert;

    public function __construct(UsageAlert $alert)
    {
        $this->alert = $alert;
    }

    public function envelope(): Envelope
    {
        $subject = match($this->alert->alert_type) {
            'warning' => '⚠️ Advertencia de Uso - Emporio Digital',
            'overdraft' => '🔴 Límites Excedidos - Emporio Digital',
            'critical' => '🚨 Uso Crítico - Emporio Digital',
            default => 'Notificación de Uso - Emporio Digital',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.usage-alert',
            with: [
                'alert' => $this->alert,
                'tenant' => $this->alert->tenant,
                'tenantUsage' => $this->alert->tenantUsage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}