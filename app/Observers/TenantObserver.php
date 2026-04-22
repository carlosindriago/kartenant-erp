<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Observers;

use App\Models\Tenant;
use App\Services\TenantActivityService;
use App\Services\TenantStatsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        Artisan::call('db:create', ['name' => $tenant->database]);

        // Ejecutar SOLO las migraciones del tenant y sembrar roles/permisos en la conexión 'tenant'
        // Aseguramos el contexto del tenant para que la conexión 'tenant' apunte a su BD
        $tenant->execute(function () {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--seed' => true,
                '--seeder' => 'Database\\Seeders\\DefaultRolesSeeder',
                '--force' => true,
            ]);
        });

        // Crear registro inicial de configuración del tenant con valores por defecto
        // Esto asegura que cada nuevo tenant tenga su configuración lista para usar
        $tenant->execute(function () use ($tenant) {
            \App\Models\TenantSetting::create([
                'tenant_id' => $tenant->id,
                'allow_cashier_void_last_sale' => true,
                'cashier_void_time_limit_minutes' => 5,
                'cashier_void_requires_same_day' => true,
                'cashier_void_requires_own_sale' => true,
            ]);
        });

        // Log tenant creation activity
        try {
            $creator = Auth::user();

            // Set default status if not provided
            if (!$tenant->status) {
                $tenant->update(['status' => Tenant::STATUS_TRIAL]);
            }

            // Log tenant creation
            TenantActivityService::logTenantCreated($tenant, $creator);

        } catch (\Exception $e) {
            // Log error but don't break the tenant creation
            \Log::error("Failed to log tenant creation: " . $e->getMessage());
        }
    }

    /**
     * Handle the Tenant "updated" event.
     */
    public function updated(Tenant $tenant): void
    {
        try {
            $updater = Auth::user();

            // Check if status changed
            if ($tenant->wasChanged('status')) {
                $oldStatus = $tenant->getOriginal('status');
                $newStatus = $tenant->status;

                TenantActivityService::logStatusChange(
                    tenant: $tenant,
                    oldStatus: $oldStatus,
                    newStatus: $newStatus,
                    changedBy: $updater
                );
            }

            // Check if trial period changed
            if ($tenant->wasChanged('trial_ends_at')) {
                if ($tenant->isExpired() && $tenant->status === Tenant::STATUS_TRIAL) {
                    // Auto-mark as expired if trial ended
                    $tenant->markAsExpired();
                    TenantActivityService::logTrialExpired($tenant);
                }
            }

            // Log general settings updates (excluding status which is handled above)
            $changedFields = $tenant->getDirty();
            unset($changedFields['status'], $changedFields['trial_ends_at'], $changedFields['updated_at']);

            if (!empty($changedFields)) {
                TenantActivityService::logSettingsUpdated(
                    tenant: $tenant,
                    changedFields: $changedFields,
                    updatedBy: $updater
                );
            }

            // Clear tenant stats cache when tenant is updated
            app(TenantStatsService::class)->clearTenantCache($tenant);

        } catch (\Exception $e) {
            // Log error but don't break the tenant update
            \Log::error("Failed to log tenant update: " . $e->getMessage());
        }
    }

    /**
     * Handle the Tenant "deleted" event.
     */
    public function deleted(Tenant $tenant): void
    {
        try {
            $deleter = Auth::user();

            // Log tenant soft delete
            TenantActivityService::logTenantDeleted($tenant, $deleter);

            // Clear tenant stats cache
            app(TenantStatsService::class)->clearTenantCache($tenant);

        } catch (\Exception $e) {
            // Log error but don't break the tenant deletion
            \Log::error("Failed to log tenant deletion: " . $e->getMessage());
        }
    }

    /**
     * Handle the Tenant "restored" event.
     */
    public function restored(Tenant $tenant): void
    {
        try {
            $restorer = Auth::user();

            // Log tenant restoration
            TenantActivityService::logTenantRestored($tenant, $restorer);

        } catch (\Exception $e) {
            // Log error but don't break the tenant restoration
            \Log::error("Failed to log tenant restoration: " . $e->getMessage());
        }
    }

    /**
     * Handle the Tenant "force deleted" event.
     */
    public function forceDeleted(Tenant $tenant): void
    {
        try {
            $deleter = Auth::user();

            // Log permanent tenant deletion
            TenantActivityService::log(
                tenant: $tenant,
                action: TenantActivity::ACTION_DELETED,
                description: "Tenant '{$tenant->name}' was permanently deleted",
                user: $deleter,
                metadata: [
                    'permanent_delete' => true,
                    'tenant_name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'database' => $tenant->database,
                ]
            );

            // Clear tenant stats cache
            app(TenantStatsService::class)->clearTenantCache($tenant);

        } catch (\Exception $e) {
            // Log error but don't break the tenant deletion
            \Log::error("Failed to log tenant permanent deletion: " . $e->getMessage());
        }
    }

    /**
     * Handle the Tenant "saving" event.
     */
    public function saving(Tenant $tenant): void
    {
        // Auto-set status based on trial period
        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isPast() && $tenant->status === Tenant::STATUS_TRIAL) {
            $tenant->status = Tenant::STATUS_EXPIRED;
        }
    }
}
