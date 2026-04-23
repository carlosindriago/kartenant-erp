<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\TaxResource\Pages;

use App\Modules\TaxResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTax extends EditRecord
{
    protected static string $resource = TaxResource::class;

    /**
     * Recordatorio de Contexto: Asegura que el tenant esté activo antes de cargar el registro
     */
    public function mount(int|string $record): void
    {
        // Recordatorio de contexto - asegura que el tenant esté activo antes de hidratar el modelo
        Filament::getTenant()?->makeCurrent();

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Recordatorio de contexto antes de eliminar
                    Filament::getTenant()?->makeCurrent();
                }),
        ];
    }

    /**
     * Recordatorio de Contexto: Asegura que el tenant esté activo antes de actualizar
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Recordatorio de contexto - asegura que el tenant esté activo
        Filament::getTenant()?->makeCurrent();

        return parent::handleRecordUpdate($record, $data);
    }
}
