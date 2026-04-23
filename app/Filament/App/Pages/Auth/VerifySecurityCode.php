<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages\Auth;

use App\Mail\SecurityCodeMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Mail;

class VerifySecurityCode extends SimplePage
{
    protected static string $view = 'filament.app.pages.auth.verify-security-code';

    public ?array $data = [];

    public function mount(): void
    {
        // Verificar que existe una sesión de reset activa
        if (! session('password_reset_code') || ! session('password_reset_email')) {
            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));

            return;
        }

        // Verificar que no haya expirado
        if (now()->isAfter(session('password_reset_expires'))) {
            session()->forget(['password_reset_code', 'password_reset_email', 'password_reset_expires']);

            Notification::make()
                ->title('Código expirado')
                ->body('El código de seguridad ha expirado. Solicita uno nuevo.')
                ->warning()
                ->send();

            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('security_code')
                    ->label('Código de Seguridad')
                    ->required()
                    ->length(6)
                    ->numeric()
                    ->placeholder('123456')
                    ->helperText('Ingresa el código de 6 dígitos que recibiste por email')
                    ->extraAttributes(['style' => 'text-align: center; font-size: 1.5rem; letter-spacing: 0.2rem;']),
            ])
            ->statePath('data');
    }

    public function verifyCode(): void
    {
        $data = $this->form->getState();

        $sessionCode = session('password_reset_code');
        $inputCode = $data['security_code'];

        if ($sessionCode !== $inputCode) {
            Notification::make()
                ->title('Código incorrecto')
                ->body('El código ingresado no es válido. Verifica e intenta nuevamente.')
                ->danger()
                ->send();

            return;
        }

        // Código válido, marcar como verificado y redirigir a preguntas de seguridad
        session(['security_code_verified' => true]);

        Notification::make()
            ->title('Código verificado')
            ->body('Código correcto. Ahora responde tus preguntas de seguridad.')
            ->success()
            ->send();

        $this->redirect(route('tenant.security-questions-reset', ['tenant' => Filament::getTenant()]));
    }

    public function resendCode(): void
    {
        $email = session('password_reset_email');

        if (! $email) {
            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));

            return;
        }

        // Generar nuevo código
        $securityCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Actualizar sesión
        session([
            'password_reset_code' => $securityCode,
            'password_reset_expires' => now()->addMinutes(10),
        ]);

        // Enviar nuevo código
        $user = User::where('email', $email)->first();

        try {
            Mail::to($user->email)->send(new SecurityCodeMail($user, $securityCode));

            Notification::make()
                ->title('Código reenviado')
                ->body('Hemos enviado un nuevo código a tu correo electrónico.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al reenviar')
                ->body('No pudimos reenviar el código. Intenta nuevamente.')
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verificar Código')
                ->submit('verifyCode')
                ->color('primary'),
            Action::make('resend')
                ->label('Reenviar Código')
                ->action('resendCode')
                ->color('gray')
                ->outlined(),
            Action::make('back')
                ->label('Volver')
                ->url(fn () => route('tenant.forgot-password', ['tenant' => Filament::getTenant()]))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Verificar Código de Seguridad';
    }

    public function getHeading(): string
    {
        return 'Verificar Código de Seguridad';
    }

    public function getSubheading(): string
    {
        $email = session('password_reset_email');
        $maskedEmail = $this->maskEmail($email);

        return "Ingresa el código de 6 dígitos enviado a {$maskedEmail}";
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = substr($username, 0, 2).str_repeat('*', strlen($username) - 2);

        return $maskedUsername.'@'.$domain;
    }
}
