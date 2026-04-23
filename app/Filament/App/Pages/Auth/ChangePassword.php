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

use App\Mail\PasswordChangeVerificationMail;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Page
{
    protected static string $view = 'filament.app.pages.auth.change-password';

    protected static ?string $title = 'Cambiar Contraseña';

    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public bool $codeGenerated = false;

    public function mount(): void
    {
        // Ensure user needs to change password
        $user = \Filament\Facades\Filament::auth()->user();
        if (! $user || ! $user->needsPasswordChange()) {
            $tenant = \Filament\Facades\Filament::getTenant();
            $this->redirect(route('filament.app.pages.dashboard', ['tenant' => $tenant?->domain]));

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Contraseña Actual')
                    ->password()
                    ->required()
                    ->helperText('Ingresa la contraseña temporal que recibiste por email'),

                TextInput::make('password')
                    ->label('Nueva Contraseña')
                    ->password()
                    ->required()
                    ->rule(Password::min(8)->letters()->numbers()->mixedCase())
                    ->helperText('Mínimo 8 caracteres, con mayúsculas, minúsculas y números')
                    ->different('current_password'),

                TextInput::make('password_confirmation')
                    ->label('Confirmar Nueva Contraseña')
                    ->password()
                    ->required()
                    ->same('password'),

                TextInput::make('verification_code')
                    ->label('Código de Verificación')
                    ->required()
                    ->length(6)
                    ->numeric()
                    ->helperText('Ingresa el código de 6 dígitos que enviamos a tu email')
                    ->hidden(fn () => ! $this->codeGenerated),
            ])
            ->statePath('data');
    }

    public function generateCode(): void
    {
        $user = \Filament\Facades\Filament::auth()->user();

        if (! $user) {
            Notification::make()
                ->title('Error')
                ->body('Usuario no autenticado')
                ->danger()
                ->send();

            return;
        }

        // Verify current password first
        if (! isset($this->data['current_password']) || ! Hash::check($this->data['current_password'], $user->password)) {
            Notification::make()
                ->title('Error')
                ->body('La contraseña actual es incorrecta')
                ->danger()
                ->send();

            return;
        }

        // Verify new passwords match
        if (! isset($this->data['password']) || ! isset($this->data['password_confirmation'])) {
            Notification::make()
                ->title('Error')
                ->body('Debes ingresar la nueva contraseña y su confirmación')
                ->danger()
                ->send();

            return;
        }

        if ($this->data['password'] !== $this->data['password_confirmation']) {
            Notification::make()
                ->title('Error')
                ->body('Las contraseñas no coinciden')
                ->danger()
                ->send();

            return;
        }

        // Generate and send verification code
        $code = $user->generatePasswordChangeCode();
        Mail::to($user->email)->send(new PasswordChangeVerificationMail($code));

        $this->codeGenerated = true;

        Notification::make()
            ->title('Código Enviado')
            ->body('Hemos enviado un código de verificación a tu email. Por favor revísalo e ingrésalo a continuación.')
            ->success()
            ->send();
    }

    public function submit(): void
    {
        if (! $this->codeGenerated) {
            $this->generateCode();

            return;
        }

        $data = $this->form->getState();
        $user = \Filament\Facades\Filament::auth()->user();

        if (! $user) {
            Notification::make()
                ->title('Error')
                ->body('Usuario no autenticado')
                ->danger()
                ->send();

            return;
        }

        // Verify current password
        if (! Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Error')
                ->body('La contraseña actual es incorrecta')
                ->danger()
                ->send();

            return;
        }

        // Verify code
        if (! $user->verifyPasswordChangeCode($data['verification_code'])) {
            Notification::make()
                ->title('Código Inválido')
                ->body('El código de verificación es incorrecto o ha expirado. Por favor solicita uno nuevo.')
                ->danger()
                ->send();

            $this->codeGenerated = false;

            return;
        }

        // Update password
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $user->clearPasswordChangeCode();

        Notification::make()
            ->title('Contraseña Actualizada')
            ->body('Tu contraseña ha sido cambiada exitosamente.')
            ->success()
            ->send();

        $tenant = \Filament\Facades\Filament::getTenant();
        $this->redirect(route('filament.app.pages.dashboard', ['tenant' => $tenant?->domain]));
    }
}
