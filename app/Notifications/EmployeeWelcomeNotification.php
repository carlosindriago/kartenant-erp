<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Credenciales temporales del empleado
     */
    public function __construct(
        public string $temporaryPassword,
        public string $tenantName,
        public string $loginUrl,
        public ?string $documentNumber = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        
        return (new MailMessage)
            ->subject("🎉 Bienvenido a {$this->tenantName}")
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("Te damos la bienvenida al equipo de **{$this->tenantName}**. Tu cuenta de empleado ha sido creada exitosamente.")
            ->line('---')
            ->line('📧 **Tus Credenciales de Acceso:**')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Contraseña temporal:** `{$this->temporaryPassword}`")
            ->line('---')
            ->action('🔐 Iniciar Sesión Ahora', $this->loginUrl)
            ->line('---')
            ->line('⚠️ **IMPORTANTE - Seguridad:**')
            ->line('1. **Debes cambiar tu contraseña** en tu primer inicio de sesión')
            ->line('2. No compartas tus credenciales con nadie')
            ->line('3. Usa una contraseña segura (mínimo 8 caracteres)')
            ->line('4. Combina letras mayúsculas, minúsculas, números y símbolos')
            ->line('---')
            ->line('📋 **Información Adicional:**')
            ->line("• Roles asignados: " . $notifiable->roles->pluck('name')->join(', '))
            ->when($this->documentNumber, function ($message) {
                return $message->line("• Número de comprobante: **{$this->documentNumber}**");
            })
            ->line('---')
            ->line('Si tienes alguna pregunta o problema para acceder, contacta a tu supervisor o al administrador del sistema.')
            ->salutation("¡Éxito en tu nuevo rol! 🚀  
El equipo de {$this->tenantName}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'login_url' => $this->loginUrl,
            'document_number' => $this->documentNumber,
            'sent_at' => now()->toISOString(),
        ];
    }
}
