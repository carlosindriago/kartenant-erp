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

use App\Modules\POS\Models\CashRegister;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class CashRegisterForcedClosureNotification extends Notification
{
    use Queueable;

    protected CashRegister $cashRegister;
    protected string $reason;
    protected string $forcedByName;

    /**
     * Create a new notification instance.
     */
    public function __construct(CashRegister $cashRegister, string $reason, string $forcedByName)
    {
        $this->cashRegister = $cashRegister;
        $this->reason = $reason;
        $this->forcedByName = $forcedByName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('⚠️ Tu caja fue cerrada por un administrador')
                    ->greeting('Hola ' . $notifiable->name)
                    ->line('Tu caja registradora **' . $this->cashRegister->register_number . '** ha sido cerrada por un administrador.')
                    ->line('**Cerrada por:** ' . $this->forcedByName)
                    ->line('**Motivo:** ' . $this->reason)
                    ->line('**Fecha de cierre:** ' . $this->cashRegister->closed_at->format('d/m/Y H:i:s'))
                    ->line('**Monto contado:** $' . number_format($this->cashRegister->actual_amount, 2))
                    ->line('**Diferencia:** $' . number_format($this->cashRegister->difference, 2))
                    ->line('Si tienes dudas sobre este cierre, contacta con tu supervisor.')
                    ->salutation('Equipo de ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Caja cerrada por administrador',
            'message' => "Tu caja {$this->cashRegister->register_number} fue cerrada por {$this->forcedByName}",
            'cash_register_id' => $this->cashRegister->id,
            'register_number' => $this->cashRegister->register_number,
            'forced_by' => $this->forcedByName,
            'reason' => $this->reason,
            'closed_at' => $this->cashRegister->closed_at,
            'actual_amount' => $this->cashRegister->actual_amount,
            'difference' => $this->cashRegister->difference,
            'type' => 'forced_closure',
        ];
    }
    
    /**
     * Enviar notificación a Filament
     */
    public function toFilament(object $notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->warning()
            ->title('⚠️ Caja Cerrada por Administrador')
            ->body("Tu caja {$this->cashRegister->register_number} fue cerrada por {$this->forcedByName}. Motivo: {$this->reason}")
            ->persistent()
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning');
    }
}
