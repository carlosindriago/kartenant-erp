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
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Detach user from current tenant
                    $currentTenant = Filament::getTenant();
                    $record->tenants()->detach($currentTenant->id);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only hash password if it was provided
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Remove password confirmation field
        unset($data['password_confirmation']);

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Empleado actualizado exitosamente';
    }
}
