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

use App\Services\AuditLogger;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasRoutes;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Hash;

class ForcePasswordChange extends SimplePage implements HasForms
{
    use InteractsWithForms, HasRoutes;

    protected static string $routePath = '/force-password-change';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Cambio de contraseña requerido';
    protected static string $view = 'filament.pages.auth.force-password-change';

    public ?array $data = [];

    public function mount()
    {
        $user = auth('superadmin')->user();
        if (! $user) {
            return redirect()->to('/admin/login');
        }
        if (! (bool) $user->must_change_password) {
            return redirect()->to('/admin');
        }
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('password')
                ->label('Nueva contraseña')
                ->password()
                ->required()
                ->minLength(12)
                ->revealable(),
            TextInput::make('password_confirmation')
                ->label('Confirmar contraseña')
                ->password()
                ->required()
                ->same('password')
                ->revealable(),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('update')
                ->label('Actualizar contraseña')
                ->submit('updatePassword'),
        ];
    }

    public function updatePassword()
    {
        $state = $this->form->getState();
        $user = auth('superadmin')->user();
        if (! $user) {
            return redirect()->to('/admin/login');
        }

        $user->forceFill([
            'password' => Hash::make((string) $state['password']),
            'must_change_password' => false,
        ])->save();

        AuditLogger::log(
            subject: $user,
            causer: $user,
            description: 'Cambio de contraseña forzado completado (admin)',
            event: 'password_changed',
            logName: 'auth',
            guard: 'superadmin'
        );

        Notification::make()
            ->title('Contraseña actualizada correctamente.')
            ->success()
            ->send();

        return redirect()->to('/admin');
    }
}
