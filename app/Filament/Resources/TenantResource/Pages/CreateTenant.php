<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Services\TenantManagerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Delegamos la creación al servicio dedicado
        /** @var TenantManagerService $tenantManager */
        $tenantManager = app(TenantManagerService::class);
        $tenant = $tenantManager->create($data);

        // Notificación de éxito para nosotros en el panel de Super Admin.
        Notification::make()
            ->title('Tenant Creado Exitosamente')
            ->success()
            ->body("Se ha enviado un email de bienvenida a {$data['contact_email']}.")
            ->send();

        return $tenant;
    }
}
