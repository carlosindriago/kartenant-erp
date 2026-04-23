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

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public bool $requiresTwoFactor = true;

    public ?string $pendingEmail = null;

    protected function getRedirectUrl(): string
    {
        return '/tenant/dashboard';
    }

    public function getHeading(): string
    {
        return 'Iniciar Sesión';
    }

    public function getSubheading(): ?string
    {
        return 'Accede a tu cuenta para continuar';
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Correo Electrónico')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Checkbox
    {
        return Checkbox::make('remember')
            ->label('Recordarme');
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent()->hidden(fn () => $this->requiresTwoFactor),
                        $this->getPasswordFormComponent()->hidden(fn () => $this->requiresTwoFactor),
                        $this->getRememberFormComponent()->hidden(fn () => $this->requiresTwoFactor),

                        // 2FA Code field (shown only after credentials verified)
                        TextInput::make('two_factor_code')
                            ->label('Código de Verificación')
                            ->required(fn () => $this->requiresTwoFactor)
                            ->length(6)
                            ->numeric()
                            ->helperText('Ingresa el código de 6 dígitos que enviamos a tu email')
                            ->hidden(fn () => ! $this->requiresTwoFactor),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        if (! $this->requiresTwoFactor) {
            // Step 1: Verify credentials
            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
                ]);
            }

            // Generate and send 2FA code
            $code = $user->generateEmail2FACode();
            Mail::to($user->email)->send(new TwoFactorCodeMail($code));

            $this->pendingEmail = $user->email;
            $this->requiresTwoFactor = true;

            Notification::make()
                ->title('Código Enviado')
                ->body('Hemos enviado un código de verificación a tu email. Por favor revísalo e ingrésalo.')
                ->success()
                ->send();

            return null;
        }

        // Step 2: Verify 2FA code and login
        $user = User::where('email', $this->pendingEmail)->first();

        if (! $user || ! $user->verifyEmail2FACode($data['two_factor_code'])) {
            throw ValidationException::withMessages([
                'data.two_factor_code' => 'El código de verificación es incorrecto o ha expirado.',
            ]);
        }

        // Clear 2FA code
        $user->clearEmail2FACode();

        // Actually log in the user
        Auth::guard('tenant')->login($user, $data['remember'] ?? false);

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function resendCode(): void
    {
        if (! $this->pendingEmail) {
            return;
        }

        $user = User::where('email', $this->pendingEmail)->first();

        if ($user) {
            $code = $user->generateEmail2FACode();
            Mail::to($user->email)->send(new TwoFactorCodeMail($code));

            Notification::make()
                ->title('Código Reenviado')
                ->body('Hemos enviado un nuevo código de verificación a tu email.')
                ->success()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Iniciar Sesión')
            ->submit('authenticate')
            ->color('primary')
            ->size('lg')
            ->extraAttributes([
                'class' => 'w-full',
            ]);
    }
}
