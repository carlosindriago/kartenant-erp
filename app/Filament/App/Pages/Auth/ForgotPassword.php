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

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Mail\SecurityCodeMail;

class ForgotPassword extends SimplePage
{
    protected static string $view = 'filament.app.pages.auth.forgot-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->placeholder('Ingresa tu correo electrónico')
                    ->helperText('Te enviaremos un código de seguridad para restablecer tu contraseña'),
            ])
            ->statePath('data');
    }

    public function sendSecurityCode(): void
    {
        $data = $this->form->getState();
        
        $user = User::where('email', $data['email'])->first();
        
        if (!$user) {
            Notification::make()
                ->title('Email no encontrado')
                ->body('No encontramos una cuenta con ese correo electrónico.')
                ->danger()
                ->send();
            return;
        }

        // Verificar que el usuario tenga acceso al tenant actual
        $tenant = Filament::getTenant();
        if (!$user->canAccessTenant($tenant)) {
            Notification::make()
                ->title('Acceso denegado')
                ->body('Este usuario no tiene acceso a esta empresa.')
                ->danger()
                ->send();
            return;
        }

        // Generar código de seguridad de 6 dígitos
        $securityCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Guardar el código en la sesión con expiración de 10 minutos
        session([
            'password_reset_code' => $securityCode,
            'password_reset_email' => $data['email'],
            'password_reset_expires' => now()->addMinutes(10),
        ]);

        // Enviar el código por email
        try {
            Mail::to($user->email)->send(new SecurityCodeMail($user, $securityCode));
            
            Notification::make()
                ->title('Código enviado')
                ->body('Hemos enviado un código de seguridad a tu correo electrónico.')
                ->success()
                ->send();

            // Redirigir a la página de verificación del código
            $this->redirect(route('tenant.verify-security-code', ['tenant' => $tenant]));
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al enviar email')
                ->body('No pudimos enviar el código. Intenta nuevamente.')
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Enviar Código')
                ->submit('sendSecurityCode')
                ->color('primary'),
            Action::make('back')
                ->label('Volver al Login')
                ->url(fn () => route('filament.app.auth.login', ['tenant' => Filament::getTenant()]))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Recuperar Contraseña';
    }

    public function getHeading(): string
    {
        return 'Recuperar Contraseña';
    }

    public function getSubheading(): string
    {
        return 'Ingresa tu correo electrónico para recibir un código de seguridad';
    }
}
