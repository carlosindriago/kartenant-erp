<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTransactionResource\Pages;
use App\Models\PaymentTransaction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transacciones de Pago';

    protected static ?string $modelLabel = 'Transacción';

    protected static ?string $pluralModelLabel = 'Transacciones de Pago';

    protected static ?string $navigationGroup = 'Gestión del Sistema';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detalles de la Transacción')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('tenant_id')
                                    ->label('Tenant')
                                    ->relationship('tenant', 'name')
                                    ->required()
                                    ->searchable(),
                                
                                Forms\Components\Select::make('subscription_id')
                                    ->label('Suscripción')
                                    ->relationship('subscription', 'id')
                                    ->searchable(),
                                
                                Forms\Components\Select::make('gateway_driver')
                                    ->label('Gateway')
                                    ->options([
                                        'manual_transfer' => 'Transferencia Manual',
                                        'lemon_squeezy' => 'Lemon Squeezy',
                                        'stripe' => 'Stripe',
                                    ])
                                    ->required(),
                            ]),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Monto')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                
                                Forms\Components\Select::make('currency')
                                    ->label('Moneda')
                                    ->options([
                                        'USD' => 'USD',
                                        'ARS' => 'ARS',
                                        'PEN' => 'PEN',
                                        'MXN' => 'MXN',
                                    ])
                                    ->required(),
                                
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        PaymentTransaction::STATUS_PENDING => 'Pendiente',
                                        PaymentTransaction::STATUS_APPROVED => 'Aprobado',
                                        PaymentTransaction::STATUS_REJECTED => 'Rechazado',
                                        PaymentTransaction::STATUS_COMPLETED => 'Completado',
                                        PaymentTransaction::STATUS_FAILED => 'Fallido',
                                    ])
                                    ->required(),
                            ]),
                        
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID de Transacción Externa'),
                        
                        Forms\Components\FileUpload::make('proof_of_payment')
                            ->label('Comprobante de Pago')
                            ->image()
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('gateway_driver')
                    ->label('Gateway')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual_transfer' => 'info',
                        'lemon_squeezy' => 'warning',
                        'stripe' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PaymentTransaction::STATUS_PENDING => 'warning',
                        PaymentTransaction::STATUS_APPROVED => 'success',
                        PaymentTransaction::STATUS_COMPLETED => 'success',
                        PaymentTransaction::STATUS_REJECTED => 'danger',
                        PaymentTransaction::STATUS_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Comprobante')
                    ->square()
                    ->defaultImageUrl(fn () => null)
                    ->visibility('private'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Aprobado Por')
                    ->placeholder('N/A'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PaymentTransaction::STATUS_PENDING => 'Pendiente',
                        PaymentTransaction::STATUS_APPROVED => 'Aprobado',
                        PaymentTransaction::STATUS_REJECTED => 'Rechazado',
                    ]),
                
                Tables\Filters\SelectFilter::make('gateway_driver')
                    ->label('Gateway')
                    ->options([
                        'manual_transfer' => 'Transferencia Manual',
                        'lemon_squeezy' => 'Lemon Squeezy',
                        'stripe' => 'Stripe',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Pago')
                    ->modalDescription(fn ($record) => "¿Confirmar pago de {$record->currency} {$record->amount} para {$record->tenant->name}?")
                    ->action(function ($record) {
                        $user = auth('superadmin')->user();
                        $record->approve($user);
                        
                        Notification::make()
                            ->title('Pago aprobado exitosamente')
                            ->success()
                            ->body("La suscripción ha sido activada.")
                            ->send();
                    }),
                
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->isPending())
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $user = auth('superadmin')->user();
                        $record->reject($user, $data['reason']);
                        
                        Notification::make()
                            ->title('Pago rechazado')
                            ->warning()
                            ->body("Se ha notificado al tenant.")
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTransactions::route('/'),
            'view' => Pages\ViewPaymentTransaction::route('/{record}'),
            'edit' => Pages\EditPaymentTransaction::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            // Ensure we're using the landlord connection for admin panel
            $count = PaymentTransaction::on('landlord')->pending()->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            // If table doesn't exist or connection fails, don't show badge
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            // Ensure we're using the landlord connection for admin panel
            $count = PaymentTransaction::on('landlord')->pending()->count();
            return $count > 0 ? 'warning' : 'gray';
        } catch (\Exception $e) {
            // If table doesn't exist or connection fails, use default color
            return 'gray';
        }
    }
}
