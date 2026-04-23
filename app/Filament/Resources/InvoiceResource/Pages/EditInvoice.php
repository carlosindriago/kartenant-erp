<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Notifications\NotificationAction;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\Response;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected ?string $heading = 'Editar Factura';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent editing paid invoices
        if ($this->record->status === 'paid') {
            throw new \Exception('No se pueden editar facturas pagadas');
        }

        // Recalculate totals if subtotal changed
        if (isset($data['subtotal'])) {
            $taxAmount = $data['subtotal'] * 0.16; // 16% tax
            $data['tax_amount'] = $taxAmount;
            $data['total_amount'] = $data['subtotal'] + $taxAmount;
        }

        // Update line items if plan price changed
        if (isset($data['plan_price']) && ! isset($data['line_items'])) {
            $data['line_items'] = [
                [
                    'description' => ($data['plan_name'] ?? $this->record->plan_name).' - '.($data['billing_cycle'] ?? $this->record->billing_cycle),
                    'quantity' => 1,
                    'unit_price' => $data['plan_price'] ?? $this->record->plan_price,
                    'total' => $data['subtotal'] ?? $this->record->subtotal,
                ],
            ];
        }

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Invoice
    {
        $record = parent::handleRecordUpdate($record, $data);

        // Log invoice update
        \Log::info('Invoice updated', [
            'invoice_id' => $record->id,
            'invoice_number' => $record->invoice_number,
            'changes' => $record->getDirty(),
            'updated_by' => auth()->id(),
        ]);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver')
                ->icon('heroicon-o-eye'),

            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('download_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $this->generateAndDownloadPDF();
                })
                ->visible(fn () => $this->record->status !== 'draft'),

            Actions\Action::make('send_email')
                ->label('Enviar por Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->action(function () {
                    $success = InvoiceResource::sendEmail($this->record);

                    if ($success) {
                        $this->notify('success', 'Factura enviada exitosamente');
                    } else {
                        $this->notify('error', 'Error al enviar la factura');
                    }
                })
                ->visible(fn () => $this->record->status !== 'draft')
                ->requiresConfirmation(),
        ];
    }

    protected function generateAndDownloadPDF(): ?Response
    {
        try {
            // Generate PDF
            $pdf = Pdf::loadView('pdf.invoices.invoice', [
                'invoice' => $this->record,
                'tenant' => $this->record->tenant,
                'settings' => PaymentSettings::getDefault(),
            ]);

            // Generate filename
            $filename = "invoice_{$this->record->invoice_number}.pdf";

            // Download the PDF
            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $filename,
                ['Content-Type' => 'application/pdf']
            );

        } catch (\Exception $e) {
            $this->notify('error', 'Error al generar PDF: '.$e->getMessage());

            return null;
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Guardar Cambios')
                ->icon('heroicon-o-check'),

            $this->getCancelFormAction()
                ->label('Cancelar'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Factura Actualizada')
            ->body("La factura {$this->record->invoice_number} ha sido actualizada exitosamente.")
            ->success()
            ->actions([
                NotificationAction::make('view')
                    ->label('Ver Factura')
                    ->url($this->getResource()::getUrl('view', ['record' => $this->record])),
            ]);
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();

        return [
            $resource::getUrl() => $resource::getNavigationLabel(),
            $this->getRecord()->invoice_number => 'Ver Factura',
            'Editar' => 'Editar Factura',
        ];
    }
}
