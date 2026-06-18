<?php

namespace App\Services\Multitenancy\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Illuminate\Support\Facades\Storage;

class SwitchTenantFilesystemTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        /** @var \App\Models\Tenant $tenant */
        // Tomamos el disco por defecto del sistema (local, public o s3)
        $defaultDisk = config('filesystems.default', 'public');
        $baseConfig = config("filesystems.disks.{$defaultDisk}");

        if ($baseConfig) {
            // Creamos un disco virtual 'tenant' anexando el ID del tenant a la raíz
            $tenantRoot = rtrim($baseConfig['root'] ?? '', '/') . '/tenants/' . $tenant->id;
            
            $tenantConfig = array_merge($baseConfig, [
                'root' => $tenantRoot,
                // Si es un disco público local, también ajustamos la URL para generar enlaces correctos
                'url' => isset($baseConfig['url']) ? rtrim($baseConfig['url'], '/') . '/tenants/' . $tenant->id : config('app.url').'/storage/tenants/'.$tenant->id,
            ]);

            config(['filesystems.disks.tenant' => $tenantConfig]);
        }
    }

    public function forgetCurrent(): void
    {
        config(['filesystems.disks.tenant' => null]);
    }
}
