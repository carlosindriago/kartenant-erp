<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\UserResource\Pages;

use App\Models\User;
use App\Models\UserStatusChange;
use App\Notifications\EmployeeWelcomeNotification;
use App\Services\EmployeeEventService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class EmployeeRegistrationSummary extends Page
{
    protected static string $resource = \App\Modules\UserResource::class;

    protected static string $view = 'filament.pages.employee-registration-summary';

    protected static ?string $title = '¡Empleado Registrado Exitosamente!';

    public ?array $registrationData = null;

    public User $user;

    public ?UserStatusChange $event = null;

    public function mount(int $record): void
    {
        $this->user = User::findOrFail($record);

        // Obtener datos de sesión
        $this->registrationData = session('employee_registered');

        // Obtener evento si existe
        if ($this->registrationData && isset($this->registrationData['event_id'])) {
            $this->event = UserStatusChange::find($this->registrationData['event_id']);
        }

        // Si no hay evento, buscar el último registro
        if (! $this->event) {
            $this->event = $this->user->statusChanges()
                ->where('action', 'registered')
                ->latest()
                ->first();
        }
    }

    /**
     * Acción para descargar el comprobante
     */
    public function downloadCertificate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $this->event) {
            Notification::make()
                ->warning()
                ->title('No hay comprobante disponible')
                ->body('No se encontró un comprobante de alta para este empleado.')
                ->send();

            return response()->streamDownload(function () {}, 'error.txt');
        }

        try {
            $service = app(EmployeeEventService::class);

            return $service->downloadEventPdf($this->event);
        } catch (\Exception $e) {
            \Log::error('Error descargando comprobante desde resumen', [
                'event_id' => $this->event->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error al descargar')
                ->body('Hubo un problema al generar el PDF. Intenta nuevamente.')
                ->send();

            return response()->streamDownload(function () {}, 'error.txt');
        }
    }

    /**
     * Acción para reenviar el email de bienvenida
     */
    public function resendWelcomeEmail(): void
    {
        try {
            // Generar nueva contraseña temporal
            $temporaryPassword = \Str::random(12);

            // Actualizar contraseña del usuario
            $this->user->update([
                'password' => \Hash::make($temporaryPassword),
                'must_change_password' => true,
            ]);

            // Enviar email
            $tenant = \Filament\Facades\Filament::getTenant();
            $loginUrl = route('filament.app.auth.login', ['tenant' => $tenant->domain]);

            $this->user->notify(new EmployeeWelcomeNotification(
                temporaryPassword: $temporaryPassword,
                tenantName: $tenant->name,
                loginUrl: $loginUrl,
                documentNumber: $this->event?->document_number
            ));

            Notification::make()
                ->success()
                ->title('Email Reenviado')
                ->body("Se envió un nuevo email a {$this->user->email} con una nueva contraseña temporal.")
                ->seconds(6)
                ->send();

            \Log::info('Email de bienvenida reenviado', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error reenviando email de bienvenida', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error al Enviar Email')
                ->body('Hubo un problema al enviar el email. Verifica la configuración de correo.')
                ->send();
        }
    }

    /**
     * Acciones de la página
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_certificate')
                ->label('Descargar Comprobante')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn () => $this->event !== null)
                ->action('downloadCertificate'),

            Action::make('resend_email')
                ->label('Reenviar Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('¿Reenviar Email de Bienvenida?')
                ->modalDescription('Se generará una nueva contraseña temporal y se enviará al empleado.')
                ->modalSubmitActionLabel('Sí, Reenviar')
                ->action('resendWelcomeEmail'),

            Action::make('back_to_list')
                ->label('Ver Lista de Empleados')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => $this->getResource()::getUrl('index')),
        ];
    }
}
