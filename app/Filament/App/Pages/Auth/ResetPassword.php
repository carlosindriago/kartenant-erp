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

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ResetPassword extends SimplePage
{
    protected static string $view = 'filament.app.pages.auth.reset-password';

    public ?array $data = [];

    public $user;

    public function mount(): void
    {
        // Verificar que las preguntas de seguridad fueron verificadas
        if (! session('security_questions_verified') || ! session('password_reset_email')) {
            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));

            return;
        }

        // Obtener el usuario
        $this->user = User::where('email', session('password_reset_email'))->first();

        if (! $this->user) {
            session()->forget(['password_reset_code', 'password_reset_email', 'password_reset_expires', 'security_code_verified', 'security_questions_verified']);
            $this->redirect(route('tenant.forgot-password', ['tenant' => Filament::getTenant()]));

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->label('Nueva Contraseña')
                    ->password()
                    ->required()
                    ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                    ->helperText('Mínimo 8 caracteres, incluye mayúsculas, minúsculas, números y símbolos'),

                TextInput::make('password_confirmation')
                    ->label('Confirmar Nueva Contraseña')
                    ->password()
                    ->required()
                    ->same('password')
                    ->helperText('Debe coincidir con la contraseña anterior'),
            ])
            ->statePath('data');
    }

    public function resetPassword(): void
    {
        $data = $this->form->getState();

        // Actualizar la contraseña del usuario
        $this->user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false, // Si tenía que cambiar contraseña, ya no
        ]);

        // Limpiar todas las sesiones de reset
        session()->forget([
            'password_reset_code',
            'password_reset_email',
            'password_reset_expires',
            'security_code_verified',
            'security_questions_verified',
        ]);

        Notification::make()
            ->title('Contraseña actualizada')
            ->body('Tu contraseña ha sido actualizada exitosamente. Ya puedes iniciar sesión.')
            ->success()
            ->send();

        // Redirigir al login
        $this->redirect(route('filament.app.auth.login', ['tenant' => Filament::getTenant()]));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('reset')
                ->label('Actualizar Contraseña')
                ->submit('resetPassword')
                ->color('primary'),
        ];
    }

    public function getTitle(): string
    {
        return 'Nueva Contraseña';
    }

    public function getHeading(): string
    {
        return 'Establecer Nueva Contraseña';
    }

    public function getSubheading(): string
    {
        return 'Ingresa tu nueva contraseña segura';
    }
}
