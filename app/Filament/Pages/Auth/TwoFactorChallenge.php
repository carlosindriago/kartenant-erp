<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Pages\Concerns\HasRoutes;
use Illuminate\Support\Facades\Mail;

class TwoFactorChallenge extends SimplePage implements HasForms
{
    use InteractsWithForms, HasRoutes;

    protected static string $routePath = '/login/challenge';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Verificación de Dos Factores';

    // --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
    protected static string $view = 'filament.pages.auth.two-factor-challenge';

    public ?array $data = [];

    public function mount(): void
    {
        if (! session('login.id') || ! ($user = User::find(session('login.id')))) {
            redirect(Filament::getPanel('admin')->getLoginUrl());
            return;
        }
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')
                ->label('Código de Verificación')
                ->placeholder('123456')
                ->numeric()->required(),
        ])->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('verify')
                ->label('Verificar e Iniciar Sesión')
                ->submit('verify'),
        ];
    }

    public function verify()
    {
        $data = $this->form->getState();
        $user = User::find(session('login.id'));

        if (!$user || ($user->locked_until && now()->lt($user->locked_until))) {
            if ($user) {
                AuditLogger::log(
                    subject: $user,
                    causer: $user,
                    description: 'Cuenta bloqueada: acceso al desafío 2FA (admin)',
                    event: 'account_locked',
                    logName: 'auth',
                    guard: 'superadmin',
                    properties: ['locked_until' => $user->locked_until]
                );
            }
            return redirect(Filament::getPanel('admin')->getLoginUrl());
        }

        if (!$user->two_factor_code || $data['code'] !== $user->two_factor_code || now()->gt($user->two_factor_expires_at)) {
            $user->increment('login_attempts');
            if ($user->login_attempts >= 3) {
                $user->forceFill(['locked_until' => now()->addHour()])->save();
                // Opcional: Enviar email de bloqueo
                Notification::make()->title('Cuenta bloqueada por 1 hora.')->danger()->send();
                AuditLogger::log(
                    subject: $user,
                    causer: $user,
                    description: 'Cuenta bloqueada por intentos 2FA fallidos (admin)',
                    event: 'account_locked',
                    logName: 'auth',
                    guard: 'superadmin',
                    properties: ['attempts' => $user->login_attempts]
                );
                return redirect(Filament::getPanel('admin')->getLoginUrl());
            }
            AuditLogger::log(
                subject: $user,
                causer: $user,
                description: 'Código 2FA inválido o expirado (admin)',
                event: 'two_factor_invalid',
                logName: 'auth',
                guard: 'superadmin',
                properties: [
                    'expired' => now()->gt($user->two_factor_expires_at),
                    'attempts' => $user->login_attempts,
                ]
            );
            Notification::make()->title('Código inválido o expirado.')->warning()->send();
            return;
        }

        // Éxito
        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        AuditLogger::log(
            subject: $user,
            causer: $user,
            description: 'Código 2FA verificado (admin)',
            event: 'two_factor_verified',
            logName: 'auth',
            guard: 'superadmin'
        );

        Filament::auth()->login($user, session('login.remember'));
        session()->forget(['login.id', 'login.remember']);

        // Si el usuario debe cambiar su contraseña, redirigir directamente a la página dedicada
        if ((bool) $user->must_change_password === true) {
            return redirect()->to('/admin/force-password-change');
        }

        return app(LoginResponse::class);
    }
}