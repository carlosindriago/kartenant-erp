<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\ProductResource\Pages;

use App\Modules\ProductResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

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
            // Registrar Entrada de Stock
            Actions\Action::make('register_entry')
                ->label('Registrar Entrada')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(function () {
                    $tenant = Filament::getTenant();

                    return route('filament.app.resources.stock-movements.create-entry', [
                        'tenant' => $tenant,
                        'product' => $this->record->id,
                    ]);
                })
                ->tooltip('Registrar entrada de mercadería para este producto'),

            // Registrar Salida de Stock
            Actions\Action::make('register_exit')
                ->label('Registrar Salida')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->url(function () {
                    $tenant = Filament::getTenant();

                    return route('filament.app.resources.stock-movements.create-exit', [
                        'tenant' => $tenant,
                        'product' => $this->record->id,
                    ]);
                })
                ->tooltip('Registrar salida de mercadería de este producto'),

            // Inhabilitar/Habilitar Producto
            Actions\Action::make('toggle_status')
                ->label(fn () => $this->record->status ? 'Inhabilitar' : 'Habilitar')
                ->icon(fn () => $this->record->status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->status ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->status ? 'Inhabilitar Producto' : 'Habilitar Producto')
                ->modalDescription(fn () => $this->record->status
                    ? 'El producto se ocultará de las ventas y reportes. Puedes habilitarlo nuevamente en cualquier momento.'
                    : 'El producto volverá a estar disponible para ventas y reportes.')
                ->modalSubmitActionLabel(fn () => $this->record->status ? 'Inhabilitar' : 'Habilitar')
                ->action(function () {
                    Filament::getTenant()?->makeCurrent();

                    $this->record->status = ! $this->record->status;
                    $this->record->save();

                    Notification::make()
                        ->title($this->record->status ? 'Producto Habilitado' : 'Producto Inhabilitado')
                        ->body($this->record->status
                            ? 'El producto está ahora disponible para ventas.'
                            : 'El producto ha sido inhabilitado correctamente.')
                        ->success()
                        ->send();

                    // Refrescar la página para actualizar el botón
                    $this->redirect(static::getUrl('edit', ['record' => $this->record]));
                })
                ->tooltip(fn () => $this->record->status
                    ? 'Deshabilitar este producto (no estará disponible para ventas)'
                    : 'Habilitar este producto (estará disponible para ventas)'),

            // Eliminar Producto
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

        // Si no hay nueva imagen en $data, mantener la existente
        if (! isset($data['image']) && $record->image) {
            $data['image'] = $record->image;
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
