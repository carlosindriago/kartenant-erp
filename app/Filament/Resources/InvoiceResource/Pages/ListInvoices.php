<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected ?string $heading = 'Facturas';

    #[Url]
    public ?string $filter = null;

    public function getHeader(): View
    {
        return view('filament.resources.invoice-resource.headers.list-header', [
            'totalAmount' => $this->getTotalAmount(),
            'paidAmount' => $this->getPaidAmount(),
            'pendingAmount' => $this->getPendingAmount(),
            'overdueAmount' => $this->getOverdueAmount(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Factura')
                ->icon('heroicon-o-plus')
                ->modalHeading('Crear Nueva Factura'),

            Action::make('generate_monthly_invoices')
                ->label('Generar Facturas Mensuales')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->action(function () {
                    $results = app(\App\Services\BillingService::class)->generateMonthlyInvoices();

                    $this->notify('success', "Se generaron {$results['generated']} facturas mensuales por un total de {$results['total_amount']} USD");
                })
                ->requiresConfirmation()
                ->modalHeading('Generar Facturas Mensuales')
                ->modalDescription('Esta acción generará facturas para todas las suscripciones activas con ciclo mensual.'),

            Action::make('process_overdue_invoices')
                ->label('Procesar Facturas Vencidas')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->action(function () {
                    $results = app(\App\Services\BillingService::class)->processOverdueInvoices();

                    $this->notify('info', "Se procesaron {$results['processed']} facturas vencidas. {$results['reminders']} recordatorios enviados.");
                })
                ->requiresConfirmation()
                ->modalHeading('Procesar Facturas Vencidas')
                ->modalDescription('Esta acción procesará todas las facturas vencidas y aplicará penalizaciones si es necesario.'),

            Action::make('export_invoices')
                ->label('Exportar Facturas')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('invoices.export'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getTotalAmount(): float
    {
        return Invoice::query()
            ->sum('total_amount');
    }

    protected function getPaidAmount(): float
    {
        return Invoice::where('status', 'paid')
            ->sum('total_amount');
    }

    protected function getPendingAmount(): float
    {
        return Invoice::whereIn('status', ['draft', 'sent'])
            ->sum('total_amount');
    }

    protected function getOverdueAmount(): float
    {
        return Invoice::where('status', 'overdue')
            ->sum('total_amount');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas')
                ->badge(Invoice::count()),

            'draft' => Tab::make('Borrador')
                ->badge(Invoice::where('status', 'draft')->count())
                ->query(fn ($query) => $query->where('status', 'draft')),

            'sent' => Tab::make('Enviadas')
                ->badge(Invoice::where('status', 'sent')->count())
                ->query(fn ($query) => $query->where('status', 'sent')),

            'paid' => Tab::make('Pagadas')
                ->badge(Invoice::where('status', 'paid')->count())
                ->query(fn ($query) => $query->where('status', 'paid')),

            'overdue' => Tab::make('Vencidas')
                ->badge(Invoice::where('status', 'overdue')->count())
                ->query(fn ($query) => $query->where('status', 'overdue')),

            'this_month' => Tab::make('Este Mes')
                ->badge(Invoice::whereMonth('created_at', now()->month)->count())
                ->query(fn ($query) => $query->whereMonth('created_at', now()->month)),

            'overdue_this_month' => Tab::make('Vencen este Mes')
                ->badge(Invoice::whereMonth('due_date', now()->month)->where('status', '!=', 'paid')->count())
                ->query(fn ($query) => $query
                    ->whereMonth('due_date', now()->month)
                    ->where('status', '!=', 'paid')),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function getDefaultTableRecordsPerPageSelection(): int
    {
        return 25;
    }
}
