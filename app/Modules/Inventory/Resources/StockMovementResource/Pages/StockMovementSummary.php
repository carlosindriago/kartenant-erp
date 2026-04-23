<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Resources\StockMovementResource\Pages;

use App\Modules\Inventory\Models\StockMovement;
use App\Services\StockMovementService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class StockMovementSummary extends Page
{
    protected static string $resource = \App\Modules\Inventory\Resources\StockMovementResource::class;

    protected static string $view = 'filament.pages.stock-movement-summary';

    protected static ?string $title = '¡Movimiento Registrado Exitosamente!';

    public ?array $movementData = null;

    public StockMovement $movement;

    public function mount(int $record): void
    {
        $this->movement = StockMovement::with(['product', 'authorizedBy'])->findOrFail($record);

        // Obtener datos de sesión si existen
        $this->movementData = session('stock_movement_registered');
    }

    /**
     * Descargar PDF en formato especificado
     */
    public function downloadPdf(string $format): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $service = app(StockMovementService::class);

            return $service->downloadMovementPdf($this->movement, $format);
        } catch (\Exception $e) {
            \Log::error('Error descargando comprobante de movimiento', [
                'movement_id' => $this->movement->id,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error al descargar')
                ->body('Hubo un problema al generar el PDF. Intenta nuevamente.')
                ->send();

            return response()->streamDownload(function () {}, 'error.txt');
        }
    }

    /**
     * Registrar nuevo movimiento del mismo tipo
     */
    public function registerAnother(): void
    {
        $type = $this->movement->type;

        // Determinar la ruta correcta según el tipo
        if ($type === 'entrada') {
            $this->redirect($this->getResource()::getUrl('create-entry'));
        } else {
            $this->redirect($this->getResource()::getUrl('create-exit'));
        }
    }

    /**
     * Acciones de la página
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_thermal')
                ->label('Ticket 80mm')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => url("/stock-movements/{$this->movement->id}/download?format=thermal"))
                ->openUrlInNewTab(),

            Action::make('download_a4')
                ->label('PDF A4')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->url(fn () => url("/stock-movements/{$this->movement->id}/download?format=a4"))
                ->openUrlInNewTab(),

            Action::make('register_another')
                ->label($this->movement->type === 'entrada' ? 'Registrar Otra Entrada' : 'Registrar Otra Salida')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->action('registerAnother'),

            Action::make('back_to_list')
                ->label('Ver Todos los Movimientos')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => $this->getResource()::getUrl('index')),
        ];
    }
}
