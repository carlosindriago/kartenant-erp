<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentProofResource\Pages;
use App\Models\PaymentProof;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\PaymentApprovalService;
use App\Services\SubscriptionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentProofResource extends Resource
{
    protected static ?string $model = PaymentProof::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Comprobante de Pago';

    protected static ?string $pluralModelLabel = 'Comprobantes de Pago';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pago')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $tenant = Tenant::find($state);
                                if ($tenant) {
                                    // Load active subscriptions for this tenant
                                    $subscriptions = TenantSubscription::where('tenant_id', $state)
                                        ->where('status', 'active')
                                        ->with('plan')
                                        ->get()
                                        ->mapWithKeys(function ($subscription) {
                                            $planName = $subscription->plan->name ?? 'Unknown';

                                            return [$subscription->id => "{$planName} - {$subscription->billing_cycle} (\${$subscription->price})"];
                                        });

                                    $set('subscription_options', $subscriptions);
                                }
                            }),

                        Forms\Components\Select::make('subscription_id')
                            ->label('Suscripción')
                            ->options(fn (callable $get) => $get('subscription_options') ?? [])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $subscription = TenantSubscription::find($state);
                                if ($subscription) {
                                    $set('expected_amount', $subscription->price);
                                    $set('currency', $subscription->currency);
                                }
                            }),

                        Forms\Components\Hidden::make('subscription_options'),

                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options([
                                'bank_transfer' => 'Transferencia Bancaria',
                                'cash' => 'Efectivo',
                                'mobile_money' => 'Dinero Móvil',
                                'other' => 'Otro',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->helperText(fn (callable $get) => $get('expected_amount') ? "Monto esperado: \${$get('expected_amount')}" : null)
                            ->dehydrateStateUsing(function ($state, callable $get) {
                                // Validate amount matches expected amount
                                $expectedAmount = $get('expected_amount');
                                if ($expectedAmount && abs($state - $expectedAmount) > 0.01) {
                                    throw new \Exception('El monto no coincide con el monto esperado de la suscripción');
                                }

                                return $state;
                            }),

                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->default('USD')
                            ->maxLength(3)
                            ->required()
                            ->disabled(),

                        Forms\Components\Hidden::make('expected_amount'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Detalles del Pago')
                    ->schema([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Fecha de Pago')
                            ->required()
                            ->max(today())
                            ->default(today()),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('Número de Referencia')
                            ->helperText('Número de transacción, código de operación, etc.'),

                        Forms\Components\TextInput::make('payer_name')
                            ->label('Nombre del Pagador')
                            ->helperText('Nombre de la persona o empresa que realizó el pago'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->helperText('Información adicional sobre el pago'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Archivos del Comprobante')
                    ->schema([
                        Forms\Components\FileUpload::make('files')
                            ->label('Archivos del Comprobante')
                            ->multiple()
                            ->directory('payment-proofs')
                            ->acceptedFileTypes(['pdf', 'jpg', 'jpeg', 'png'])
                            ->maxSize(10240) // 10MB per file
                            ->maxFiles(5)
                            ->helperText('Suba hasta 5 archivos. Formatos permitidos: PDF, JPG, JPEG, PNG. Tamaño máximo: 10MB por archivo.')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $totalSize = 0;
                                foreach ($state ?? [] as $file) {
                                    if (is_array($file) && isset($file['size'])) {
                                        $totalSize += $file['size'];
                                    }
                                }
                                $totalSizeMb = $totalSize / 1024 / 1024;
                                $set('total_size_mb', round($totalSizeMb, 2));
                            }),

                        Forms\Components\Hidden::make('total_size_mb')
                            ->dehydrateStateUsing(function ($state, callable $get) {
                                $totalSize = 0;
                                foreach ($get('files') ?? [] as $file) {
                                    if (is_array($file) && isset($file['size'])) {
                                        $totalSize += $file['size'];
                                    }
                                }

                                return $totalSize / 1024 / 1024;
                            }),
                    ]),

                Forms\Components\Section::make('Revisión')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'under_review' => 'En Revisión',
                                'approved' => 'Aprobado',
                                'rejected' => 'Rechazado',
                            ])
                            ->required()
                            ->default('pending')
                            ->visible(fn ($context): bool => $context === 'edit'),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo de Rechazo')
                            ->rows(3)
                            ->visible(fn (callable $get): bool => $get('status') === 'rejected')
                            ->required(fn (callable $get): bool => $get('status') === 'rejected'),

                        Forms\Components\Textarea::make('review_notes')
                            ->label('Notas de Revisión')
                            ->rows = 3,

                        Forms\Components\Select::make('reviewed_by')
                            ->label('Revisado por')
                            ->relationship('reviewer', 'name')
                            ->default(fn (): int => auth()->id())
                            ->visible(fn ($context): bool => $context === 'edit'),
                    ])
                    ->visible(fn ($context): bool => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->placeholder('Sin suscripción'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendiente',
                        'under_review' => 'En Revisión',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'bank_transfer' => 'Transferencia',
                        'cash' => 'Efectivo',
                        'mobile_money' => 'Dinero Móvil',
                        'other' => 'Otro',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha de Pago')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payer_name')
                    ->label('Pagador')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_file_size_mb')
                    ->label('Tamaño')
                    ->formatStateUsing(fn ($state) => number_format($state, 2).' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Revisado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Fecha Revisión')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'under_review' => 'En Revisión',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'bank_transfer' => 'Transferencia Bancaria',
                        'cash' => 'Efectivo',
                        'mobile_money' => 'Dinero Móvil',
                        'other' => 'Otro',
                    ]),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('needs_review')
                    ->label('Necesitan Revisión')
                    ->query(fn (Builder $query) => $query
                        ->whereIn('status', ['pending', 'under_review'])),

                Tables\Filters\Filter::make('over_48_hours')
                    ->label('Más de 48 horas')
                    ->query(fn (Builder $query) => $query
                        ->where('created_at', '<', now()->subHours(48))
                        ->whereIn('status', ['pending', 'under_review'])),

                Tables\Filters\Filter::make('payment_date_range')
                    ->label('Rango de Fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query) => $query->whereDate('payment_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query) => $query->whereDate('payment_date', '<=', $data['to'])
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'under_review'])),
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $user = auth()->user();
                        app(PaymentApprovalService::class)->approvePaymentProof($record, $user);
                        $this->notify('success', 'Comprobante de pago aprobado');
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'under_review']))
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Comprobante')
                    ->modalDescription('¿Está seguro que desea aprobar este comprobante de pago?'),
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                            ->rows(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $user = auth()->user();
                        app(PaymentApprovalService::class)->rejectPaymentProof(
                            $record,
                            $user,
                            $data['reason'],
                            $data['notes'] ?? null
                        );
                        $this->notify('success', 'Comprobante de pago rechazado');
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'under_review']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('download_files')
                    ->label('Descargar Archivos')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function ($record) {
                        // TODO: Implement file download logic
                    })
                    ->visible(fn ($record) => ! empty($record->file_paths)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'rejected'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete', PaymentProof::class)),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Aprobar Seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'under_review'])) {
                                    $user = auth()->user();
                                    app(PaymentApprovalService::class)->approvePaymentProof($record, $user);
                                }
                            }
                            $this->notify('success', 'Comprobantes aprobados');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('reject')
                        ->label('Rechazar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo del Rechazo')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'under_review'])) {
                                    $user = auth()->user();
                                    app(PaymentApprovalService::class)->rejectPaymentProof(
                                        $record,
                                        $user,
                                        $data['reason']
                                    );
                                }
                            }
                            $this->notify('success', 'Comprobantes rechazados');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Refresh every 30 seconds
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
            'index' => Pages\ListPaymentProofs::route('/'),
            'create' => Pages\CreatePaymentProof::route('/create'),
            'view' => Pages\ViewPaymentProof::route('/{record}'),
            'edit' => Pages\EditPaymentProof::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['pending', 'under_review'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(48))
            ->exists() ? 'danger' : 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->with(['tenant', 'subscription.plan', 'reviewer']);
    }

    /**
     * Create payment proof with file upload
     */
    public static function createPaymentProof(array $data): PaymentProof
    {
        return DB::transaction(function () use ($data) {
            // Extract uploaded files
            $files = $data['files'] ?? [];
            unset($data['files']);

            // Process files
            $filePaths = [];
            $totalSize = 0;
            $mainFileType = null;

            foreach ($files as $file) {
                if (is_array($file) && isset($file['path'])) {
                    $filePaths[] = $file['path'];
                    $totalSize += $file['size'] ?? 0;

                    if (! $mainFileType) {
                        $mainFileType = pathinfo($file['path'], PATHINFO_EXTENSION);
                    }
                }
            }

            // Create payment proof
            $paymentProof = PaymentProof::create([
                'tenant_id' => $data['tenant_id'],
                'subscription_id' => $data['subscription_id'],
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'payment_date' => $data['payment_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'file_paths' => $filePaths,
                'file_type' => $mainFileType,
                'total_file_size_mb' => $totalSize / 1024 / 1024,
                'status' => PaymentProof::STATUS_PENDING,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Create payment transaction
            app(SubscriptionService::class)->submitPaymentProof(
                $paymentProof->subscription,
                $data,
                $files
            );

            return $paymentProof;
        });
    }
}
