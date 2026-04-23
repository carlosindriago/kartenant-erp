<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Filament\Notifications\Notification;
use Filament\Notifications\NotificationAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected ?string $heading = 'Crear Factura';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate invoice number if not provided
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = Invoice::generateInvoiceNumber();
        }

        // Calculate totals if not provided
        if (isset($data['subtotal']) && ! isset($data['total_amount'])) {
            $taxAmount = $data['subtotal'] * 0.16; // 16% tax
            $data['tax_amount'] = $taxAmount;
            $data['total_amount'] = $data['subtotal'] + $taxAmount;
        }

        // Set customer data from tenant if subscription is selected
        if (isset($data['subscription_id'])) {
            $subscription = TenantSubscription::with('tenant')->find($data['subscription_id']);
            if ($subscription) {
                $data['customer_data'] = [
                    'name' => $subscription->tenant->name,
                    'email' => $subscription->tenant->owner_email,
                    'phone' => $subscription->tenant->phone ?? null,
                    'address' => $subscription->tenant->address ?? null,
                ];
                $data['billing_name'] = $subscription->tenant->name;
                $data['billing_email'] = $subscription->tenant->owner_email;
                $data['billing_address'] = $subscription->tenant->address ?? null;
            }
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Create line items if not provided
        if (! isset($data['line_items'])) {
            $data['line_items'] = [
                [
                    'description' => $data['plan_name'] ?? 'Servicio',
                    'quantity' => 1,
                    'unit_price' => $data['plan_price'] ?? $data['subtotal'],
                    'total' => $data['subtotal'],
                ],
            ];
        }

        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        parent::afterCreate();

        // Log invoice creation
        \Log::info('Invoice created manually', [
            'invoice_id' => $this->record->id,
            'invoice_number' => $this->record->invoice_number,
            'tenant_id' => $this->record->tenant_id,
            'amount' => $this->record->total_amount,
            'created_by' => auth()->id(),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Crear Factura')
                ->icon('heroicon-o-check'),

            $this->getCreateAnotherFormAction()
                ->label('Crear y Agregar Otro'),

            $this->getCancelFormAction()
                ->label('Cancelar'),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Factura Creada')
            ->body("La factura {$this->record->invoice_number} ha sido creada exitosamente.")
            ->success()
            ->actions([
                NotificationAction::make('view')
                    ->label('Ver Factura')
                    ->url($this->getResource()::getUrl('view', ['record' => $this->record])),

                NotificationAction::make('download')
                    ->label('Descargar PDF')
                    ->url(route('invoices.download', $this->record))
                    ->openUrlInNewTab(),
            ]);
    }
}
