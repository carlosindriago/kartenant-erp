<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected ?string $heading = 'Ver Factura';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil'),

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
                ->requiresConfirmation()
                ->modalHeading('Enviar Factura por Email')
                ->modalDescription("¿Está seguro que desea enviar la factura {$this->record->invoice_number} por email a {$this->record->tenant->owner_email}?"),

            Actions\Action::make('mark_paid')
                ->label('Marcar como Pagada')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->markAsPaid('manual');
                    $this->notify('success', 'Factura marcada como pagada');
                })
                ->visible(fn () => in_array($this->record->status, ['draft', 'sent', 'overdue']))
                ->requiresConfirmation(),

            Actions\Action::make('mark_sent')
                ->label('Marcar como Enviada')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->action(function () {
                    $this->record->update([
                        'status' => 'sent',
                        'is_sent' => true,
                        'sent_at' => now(),
                        'sent_via' => 'manual',
                    ]);
                    $this->notify('success', 'Factura marcada como enviada');
                })
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation(),

            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $this->duplicateInvoice();
                })
                ->requiresConfirmation()
                ->modalHeading('Duplicar Factura')
                ->modalDescription('¿Está seguro que desea duplicar esta factura?'),
        ];
    }

    protected function generateAndDownloadPDF(): \Symfony\Component\HttpFoundation\Response
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
        }
    }

    protected function duplicateInvoice(): void
    {
        try {
            $originalInvoice = $this->record;

            $newInvoice = $originalInvoice->replicate();
            $newInvoice->invoice_number = Invoice::generateInvoiceNumber();
            $newInvoice->status = Invoice::STATUS_DRAFT;
            $newInvoice->is_sent = false;
            $newInvoice->sent_at = null;
            $newInvoice->paid_at = null;
            $newInvoice->payment_provider = null;
            $newInvoice->provider_payment_id = null;
            $newInvoice->paid_amount = 0;
            $newInvoice->created_at = now();
            $newInvoice->updated_at = now();

            $newInvoice->save();

            $this->notify('success', "Factura duplicada: {$newInvoice->invoice_number}");

            // Redirect to edit the new invoice
            $this->redirect(InvoiceResource::getUrl('edit', ['record' => $newInvoice]));

        } catch (\Exception $e) {
            $this->notify('error', 'Error al duplicar factura: '.$e->getMessage());
        }
    }

    public function getFooterWidgets(): array
    {
        return [
            InvoiceResource\Widgets\InvoiceOverviewWidget::class,
            InvoiceResource\Widgets\InvoiceStatusWidget::class,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'customerData' => $this->record->customer_data ?? [],
            'lineItems' => $this->record->line_items ?? [],
            'metadata' => $this->record->metadata ?? [],
            'paymentProofs' => $this->getRelatedPaymentProofs(),
            'transactions' => $this->getRelatedTransactions(),
        ];
    }

    protected function getRelatedPaymentProofs()
    {
        if (! $this->record->subscription) {
            return collect();
        }

        return \App\Models\PaymentProof::where('subscription_id', $this->record->subscription->id)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getRelatedTransactions()
    {
        if (! $this->record->subscription) {
            return collect();
        }

        return \App\Models\PaymentTransaction::where('subscription_id', $this->record->subscription->id)
            ->with('approver')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();

        return [
            $resource::getUrl() => $resource::getNavigationLabel(),
            $this->getRecord()->invoice_number => 'Ver Factura',
        ];
    }
}
