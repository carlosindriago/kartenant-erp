<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class VerificationAccessSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    
    protected static ?string $navigationLabel = 'Acceso a Verificación';
    
    protected static ?string $title = 'Configuración de Acceso a Verificación';
    
    protected static ?string $navigationGroup = 'Seguridad';
    
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.app.pages.verification-access-settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $tenant = Filament::getTenant();
        
        $this->form->fill([
            'verification_enabled' => $tenant->verification_enabled ?? true,
            'verification_access_type' => $tenant->verification_access_type ?? 'public',
            'verification_allowed_roles' => $tenant->verification_allowed_roles ?? [],
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración de Acceso a Verificación de Documentos')
                    ->description('Configura quién puede acceder a la página pública de verificación de documentos (/verify)')
                    ->schema([
                        Forms\Components\Toggle::make('verification_enabled')
                            ->label('Habilitar Verificación Pública')
                            ->helperText('Si está desactivado, nadie podrá acceder a la página de verificación')
                            ->live()
                            ->default(true),
                        
                        Forms\Components\Radio::make('verification_access_type')
                            ->label('Tipo de Acceso')
                            ->options([
                                'public' => 'Público - Cualquier persona puede verificar documentos',
                                'private' => 'Privado - Solo usuarios registrados de este tenant',
                                'role_based' => 'Por Roles - Solo usuarios con roles específicos',
                            ])
                            ->descriptions([
                                'public' => 'Ideal para compartir documentos con clientes o proveedores externos',
                                'private' => 'Requiere login pero no roles específicos',
                                'role_based' => 'Mayor control: solo ciertos roles pueden verificar',
                            ])
                            ->default('public')
                            ->live()
                            ->disabled(fn (Forms\Get $get) => !$get('verification_enabled')),
                        
                        Forms\Components\Select::make('verification_allowed_roles')
                            ->label('Roles Permitidos')
                            ->helperText('Selecciona los roles que pueden acceder a la verificación')
                            ->multiple()
                            ->options(function () {
                                $tenant = Filament::getTenant();
                                $roles = [];
                                
                                $tenant->execute(function () use (&$roles) {
                                    $roles = \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray();
                                });
                                
                                return $roles;
                            })
                            ->visible(fn (Forms\Get $get) => $get('verification_access_type') === 'role_based')
                            ->required(fn (Forms\Get $get) => $get('verification_access_type') === 'role_based')
                            ->disabled(fn (Forms\Get $get) => !$get('verification_enabled')),
                    ]),
                    
                Forms\Components\Section::make('Información')
                    ->schema([
                        Forms\Components\Placeholder::make('url_info')
                            ->label('URL de Verificación')
                            ->content(fn () => route('verify.index')),
                        
                        Forms\Components\Placeholder::make('security_info')
                            ->label('Nota de Seguridad')
                            ->content('Los cambios se aplicarán inmediatamente. Si seleccionas "Privado" o "Por Roles", los usuarios deberán iniciar sesión para verificar documentos.'),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        $tenant = Filament::getTenant();
        
        // Validar que si es role_based, se hayan seleccionado roles
        if ($data['verification_access_type'] === 'role_based' && empty($data['verification_allowed_roles'])) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Debes seleccionar al menos un rol para el acceso por roles')
                ->send();
            return;
        }
        
        // Actualizar el tenant
        $tenant->update([
            'verification_enabled' => $data['verification_enabled'],
            'verification_access_type' => $data['verification_access_type'],
            'verification_allowed_roles' => $data['verification_allowed_roles'] ?? null,
        ]);
        
        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->body('Los cambios se han aplicado correctamente')
            ->send();
    }
    
    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Guardar Configuración')
                ->submit('save'),
        ];
    }
}
