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

use App\Modules\UserResource;
use App\Models\User;
use App\Models\UserStatusChange;
use App\Services\EmployeeEventService;
use App\Notifications\EmployeeWelcomeNotification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    /**
     * Contraseña temporal para enviar por email
     */
    protected ?string $temporaryPassword = null;
    
    /**
     * Evento de registro del empleado
     */
    protected ?UserStatusChange $registrationEvent = null;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Guardar contraseña temporal antes de hashearla
        if (isset($data['password'])) {
            $this->temporaryPassword = $data['password'];
            $data['password'] = Hash::make($data['password']);
        }
        
        // Ensure is_super_admin is false (employees are never superadmins)
        $data['is_super_admin'] = false;
        
        // Set default values for security fields
        $data['login_attempts'] = 0;
        $data['must_change_password'] = $data['must_change_password'] ?? true;
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        // Extract roles before creating user (roles are handled separately)
        $roles = $data['roles'] ?? [];
        unset($data['roles'], $data['password_confirmation']);
        
        // Create user in central users table (landlord DB)
        // Note: User model uses default connection (landlord)
        $user = static::getModel()::create($data);
        
        // Associate user with current tenant
        $currentTenant = \Filament\Facades\Filament::getTenant();
        $user->tenants()->attach($currentTenant->id);
        
        // Assign roles to user in tenant context
        // Roles are in tenant DB, so we need to switch context
        if (!empty($roles)) {
            // Use the 'web' guard for tenant roles
            $user->assignRole($roles);
        }
        
        return $user;
    }
    
    /**
     * Después de crear el empleado, generar comprobante y enviar email
     */
    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Obtener usuario autenticado de forma confiable en contexto Filament
        $authenticatedUser = \Filament\Facades\Filament::auth()->user();
        
        if (!$authenticatedUser) {
            \Log::warning('No se pudo obtener usuario autenticado para generar comprobante', [
                'user_id' => $user->id,
            ]);
            return;
        }
        
        // 1. Registrar evento de alta con comprobante verificable
        try {
            $service = app(EmployeeEventService::class);
            $this->registrationEvent = $service->registerEmployeeCreation(
                $user,
                $authenticatedUser,
                "Registro inicial en el sistema"
            );
        } catch (\Exception $e) {
            \Log::error('Error generando comprobante de alta de empleado', [
                'user_id' => $user->id,
                'authenticated_user_id' => $authenticatedUser->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        // 2. Enviar email de bienvenida con credenciales
        if ($this->temporaryPassword) {
            try {
                $tenant = \Filament\Facades\Filament::getTenant();
                $loginUrl = route('filament.app.auth.login', ['tenant' => $tenant->domain]);
                
                $user->notify(new EmployeeWelcomeNotification(
                    temporaryPassword: $this->temporaryPassword,
                    tenantName: $tenant->name,
                    loginUrl: $loginUrl,
                    documentNumber: $this->registrationEvent?->document_number
                ));
                
                \Log::info('Email de bienvenida enviado a empleado', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                
            } catch (\Exception $e) {
                \Log::error('Error enviando email de bienvenida', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    /**
     * Redirigir a página de resumen en lugar de la lista
     */
    protected function getRedirectUrl(): string
    {
        // Almacenar datos del registro en sesión para la página de resumen
        session()->flash('employee_registered', [
            'user_id' => $this->record->id,
            'event_id' => $this->registrationEvent?->id,
            'email_sent' => $this->temporaryPassword !== null,
        ]);
        
        return $this->getResource()::getUrl('summary', [
            'record' => $this->record->id
        ]);
    }
    
    /**
     * Desactivar notificación por defecto (usaremos modal personalizado)
     */
    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
