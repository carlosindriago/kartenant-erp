<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Mail\InvoiceEmail;
use App\Mail\OverdueInvoiceReminder;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('customer_data', Tenant::find($state)?->only(['name', 'owner_email', 'phone', 'address']))),

                        Forms\Components\Select::make('subscription_id')
                            ->label('Suscripción')
                            ->relationship('subscription', function ($query) {
                                return $query->with('plan')->orderBy('created_at', 'desc');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->plan->name} ({$record->billing_cycle})")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Número de Factura')
                            ->required()
                            ->unique(ignorableRecord: true)
                            ->disabled(fn ($context) => $context === 'edit')
                            ->dehydrateStateUsing(fn ($state) => $state ?? Invoice::generateInvoiceNumber()),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'sent' => 'Enviada',
                                'paid' => 'Pagada',
                                'overdue' => 'Vencida',
                                'cancelled' => 'Cancelada',
                                'refunded' => 'Reembolsada',
                            ])
                            ->required()
                            ->default('draft'),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'subscription' => 'Suscripción',
                                'setup_fee' => 'Cuota de Configuración',
                                'extra_usage' => 'Uso Adicional',
                                'penalty' => 'Penalización',
                            ])
                            ->required()
                            ->default('subscription'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Período de Facturación')
                    ->schema([
                        Forms\Components\DatePicker::make('billing_period_start')
                            ->label('Inicio del Período')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('billing_period_end')
                            ->label('Fin del Período')
                            ->required()
                            ->default(now()->addMonth()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Fecha de Vencimiento')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $taxAmount = $state * 0.16; // 16% tax
                                $set('tax_amount', $taxAmount);
                                $set('total_amount', $state + $taxAmount);
                            }),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Impuesto')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->default('USD')
                            ->maxLength(3)
                            ->required(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Información del Plan')
                    ->schema([
                        Forms\Components\TextInput::make('plan_name')
                            ->label('Nombre del Plan')
                            ->required(),

                        Forms\Components\Select::make('billing_cycle')
                            ->label('Ciclo de Facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('plan_price')
                            ->label('Precio del Plan')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Datos del Cliente')
                    ->schema([
                        Forms\Components\Hidden::make('customer_data'),

                        Forms\Components\TextInput::make('billing_name')
                            ->label('Nombre de Facturación')
                            ->required(),

                        Forms\Components\TextInput::make('billing_email')
                            ->label('Email de Facturación')
                            ->email()
                            ->required(),

                        Forms\Components\Textarea::make('billing_address')
                            ->label('Dirección de Facturación')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Información de Pago')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options([
                                'bank_transfer' => 'Transferencia Bancaria',
                                'credit_card' => 'Tarjeta de Crédito',
                                'paypal' => 'PayPal',
                                'mercadopago' => 'Mercado Pago',
                                'manual' => 'Manual',
                            ]),

                        Forms\Components\TextInput::make('provider_payment_id')
                            ->label('ID de Pago del Proveedor'),

                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Fecha de Pago'),

                        Forms\Components\Textarea::make('payment_notes')
                            ->label('Notas de Pago')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Archivos Adjuntos')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->label('Archivos Adjuntos')
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Archivo')
                                    ->directory('invoices/attachments')
                                    ->acceptedFileTypes(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])
                                    ->maxSize(10240), // 10MB

                                Forms\Components\TextInput::make('description')
                                    ->label('Descripción'),
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Notas Adicionales')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notas de Administración')
                            ->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'paid',
                        'warning' => 'overdue',
                        'danger' => 'cancelled',
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviada',
                        'paid' => 'Pagada',
                        'overdue' => 'Vencida',
                        'cancelled' => 'Cancelada',
                        'refunded' => 'Reembolsada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'sent' => 'Enviada',
                        'paid' => 'Pagada',
                        'overdue' => 'Vencida',
                        'cancelled' => 'Cancelada',
                        'refunded' => 'Reembolsada',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'subscription' => 'Suscripción',
                        'setup_fee' => 'Cuota de Configuración',
                        'extra_usage' => 'Uso Adicional',
                        'penalty' => 'Penalización',
                    ]),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->label('Ciclo de Facturación')
                    ->options([
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(fn (Builder $query) => $query
                        ->where('status', '!=', 'paid')
                        ->where('due_date', '<', now())),

                Tables\Filters\Filter::make('due_this_month')
                    ->label('Vencen este Mes')
                    ->query(fn (Builder $query) => $query
                        ->whereMonth('due_date', now()->month)
                        ->whereYear('due_date', now()->year)),

                Tables\Filters\Filter::make('created_this_month')
                    ->label('Creadas este Mes')
                    ->query(fn (Builder $query) => $query
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('invoices.download', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_email')
                    ->label('Enviar Email')
                    ->icon('heroicon-o-envelope')
                    ->action(function ($record) {
                        // TODO: Implement email sending
                    })
                    ->requiresConfirmation()
                    ->color('success'),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Marcar Pagada')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $record->markAsPaid();
                    })
                    ->visible(fn ($record) => $record->status !== 'paid')
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_emails')
                        ->label('Enviar Emails')
                        ->icon('heroicon-o-envelope')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                // TODO: Implement bulk email sending
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('mark_paid')
                        ->label('Marcar Pagadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status !== 'paid') {
                                    $record->markAsPaid();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // Refresh every minute
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', '!=', 'paid')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'overdue')->exists() ? 'danger' : 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->with(['tenant', 'subscription.plan']);
    }

    /**
     * Generate PDF for invoice
     */
    public static function generatePDF(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoices.invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'settings' => PaymentSettings::getDefault(),
        ]);

        $filename = "invoices/{$invoice->invoice_number}.pdf";

        // Ensure directory exists
        $directory = dirname($filename);
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Send invoice email
     */
    public static function sendEmail(Invoice $invoice): bool
    {
        try {
            $tenant = $invoice->tenant;

            if (! $tenant->owner_email) {
                return false;
            }

            // Generate PDF
            $pdfPath = self::generatePDF($invoice);

            // Send email
            \Mail::to($tenant->owner_email)
                ->send(new InvoiceEmail($invoice, $pdfPath));

            // Mark as sent
            $invoice->update([
                'is_sent' => true,
                'sent_at' => now(),
                'sent_via' => 'email',
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Error sending invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process overdue invoices
     */
    public static function processOverdueInvoices(): array
    {
        $results = [
            'processed' => 0,
            'penalties' => 0,
            'reminders' => 0,
        ];

        Invoice::where('due_date', '<', now())
            ->where('status', 'sent')
            ->whereDoesntHave('penalties') // Check if penalty already applied
            ->chunk(50, function ($invoices) use (&$results) {
                foreach ($invoices as $invoice) {
                    $overdueDays = now()->diffInDays($invoice->due_date);

                    // Mark as overdue
                    $invoice->update(['status' => 'overdue']);
                    $results['processed']++;

                    // Apply penalty after 7 days
                    if ($overdueDays >= 7) {
                        self::applyPenalty($invoice);
                        $results['penalties']++;
                    } else {
                        // Send reminder
                        self::sendReminder($invoice);
                        $results['reminders']++;
                    }
                }
            });

        return $results;
    }

    /**
     * Apply penalty to overdue invoice
     */
    private static function applyPenalty(Invoice $invoice): void
    {
        $penaltyAmount = $invoice->total_amount * 0.10; // 10% penalty

        Invoice::create([
            'tenant_id' => $invoice->tenant_id,
            'subscription_id' => $invoice->subscription_id,
            'invoice_number' => Invoice::generateInvoiceNumber('PENALTY'),
            'status' => Invoice::STATUS_DRAFT,
            'type' => 'penalty',
            'billing_period_start' => now(),
            'billing_period_end' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => $penaltyAmount,
            'tax_amount' => $penaltyAmount * 0.16,
            'total_amount' => $penaltyAmount * 1.16,
            'currency' => $invoice->currency,
            'plan_name' => 'Penalización - Factura '.$invoice->invoice_number,
            'billing_cycle' => 'once',
            'plan_price' => $penaltyAmount,
            'customer_data' => $invoice->customer_data,
            'metadata' => [
                'related_invoice_id' => $invoice->id,
                'overdue_days' => now()->diffInDays($invoice->due_date),
            ],
        ]);

        \Log::info('Penalty applied', [
            'original_invoice_id' => $invoice->id,
            'penalty_amount' => $penaltyAmount,
        ]);
    }

    /**
     * Send overdue reminder
     */
    private static function sendReminder(Invoice $invoice): void
    {
        try {
            $tenant = $invoice->tenant;

            if ($tenant->owner_email) {
                \Mail::to($tenant->owner_email)
                    ->send(new OverdueInvoiceReminder($invoice));
            }
        } catch (\Exception $e) {
            \Log::error('Error sending overdue reminder', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
