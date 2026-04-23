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

use App\Http\Responses\Auth\TwoFactorChallengeResponse;
use App\Mail\TwoFactorCodeMail;
use App\Services\AuditLogger;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Administración';
    }

    public function getSubheading(): ?string
    {
        return 'Acceso para administradores del sistema';
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

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('authenticate')
            ->label('Iniciar Sesión')
            ->submit('authenticate')
            ->color('danger')
            ->size('lg')
            ->extraAttributes([
                'class' => 'w-full',
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $user = Filament::auth()->getProvider()->retrieveByCredentials(['email' => $data['email']]);

        $allowed = false;
        if ($user && Hash::check($data['password'], $user->password)) {
            if ($user->is_super_admin) {
                $allowed = true;
            } else {
                try {
                    // Chequeo explícito por guard 'superadmin'
                    $allowed = $user->hasPermissionTo('admin.access', 'superadmin');
                } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist|\Spatie\Permission\Exceptions\GuardDoesNotMatch $e) {
                    $allowed = false;
                }
            }
        }
        if (! $allowed) {
            // Log failed admin login attempt
            AuditLogger::log(
                subject: $user,
                causer: $user,
                description: 'Intento de inicio de sesión fallido (admin)',
                event: 'login_failed',
                logName: 'auth',
                guard: 'superadmin',
                properties: ['email' => $data['email']]
            );
            throw ValidationException::withMessages(['data.email' => __('filament-panels::pages/auth/login.messages.failed')]);
        }

        if ($user->locked_until && now()->lt($user->locked_until)) {
            AuditLogger::log(
                subject: $user,
                causer: $user,
                description: 'Cuenta bloqueada temporalmente (admin)',
                event: 'account_locked',
                logName: 'auth',
                guard: 'superadmin',
                properties: ['locked_until' => $user->locked_until]
            );
            Notification::make()->title('Tu cuenta está bloqueada temporalmente.')->danger()->send();

            return null;
        }

        // Auto-sync: otorgar TODOS los permisos landlord a los superadmins antes del 2FA
        if ($user->is_super_admin) {
            // Asegurar que el guard sea 'superadmin' en este contexto
            config()->set('permission.default_guard', 'superadmin');
            config()->set('auth.defaults.guard', 'superadmin');
            $permModelClass = config('permission.models.permission') ?? \Spatie\Permission\Models\Permission::class;
            try {
                $allPermModels = $permModelClass::query()
                    ->where('guard_name', 'superadmin')
                    ->get();
                if ($allPermModels->isNotEmpty()) {
                    // Usar modelos en lugar de nombres para evitar problemas de guard al resolver permisos
                    $user->syncPermissions($allPermModels);
                    // Refrescar caché de permisos
                    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                }
            } catch (\Throwable $e) {
                // No bloquear el login por fallos de sincronización; se puede ejecutar el comando manual luego
            }
        }

        $code = random_int(100000, 999999);
        $user->forceFill([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
            'login_attempts' => 0,
        ])->save();

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail((string) $code));

            AuditLogger::log(
                subject: $user,
                causer: $user,
                description: 'Código 2FA enviado (admin)',
                event: 'two_factor_sent',
                logName: 'auth',
                guard: 'superadmin',
                properties: [
                    'delivery' => 'email',
                    'expires_at' => $user->two_factor_expires_at,
                ]
            );

            // Log adicional para debugging
            \Log::info('Two factor code sent to admin', [
                'email' => $user->email,
                'code' => $code,
                'expires_at' => $user->two_factor_expires_at,
            ]);

        } catch (\Exception $e) {
            // Si el correo falla, mostrar el código en los logs para desarrollo
            \Log::error('Failed to send 2FA email, showing code in logs', [
                'email' => $user->email,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            // En desarrollo, permitir continuar con el código en los logs
            if (app()->environment('local', 'testing')) {
                session(['2fa_development_code' => $code]);
            }
        }

        session(['login.id' => $user->id, 'login.remember' => $data['remember'] ?? false]);

        // Devuelve una respuesta de login personalizada que redirige al desafío 2FA
        return app(TwoFactorChallengeResponse::class);
    }
}
