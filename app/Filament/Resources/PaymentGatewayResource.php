<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGatewaySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGatewaySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Pasarelas de Pago';

    protected static ?string $modelLabel = 'Pasarela de Pago';

    protected static ?string $pluralModelLabel = 'Pasarelas de Pago';

    protected static ?string $navigationGroup = 'Configuración del Sistema';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('display_name')
                                    ->label('Nombre de Visualización')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\Select::make('driver_name')
                                    ->label('Driver')
                                    ->options([
                                        'manual_transfer' => 'Transferencia Bancaria Manual',
                                        'lemon_squeezy' => 'Lemon Squeezy',
                                        'stripe' => 'Stripe',
                                    ])
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null)
                                    ->helperText('El driver no puede cambiarse después de crear'),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activa')
                                    ->helperText('Solo una pasarela puede estar activa a la vez')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $record) {
                                        if ($state && $record) {
                                            // Deactivate other gateways
                                            PaymentGatewaySetting::where('id', '!=', $record->id)
                                                ->update(['is_active' => false]);
                                        }
                                    }),
                                
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Configuración del Driver')
                    ->schema([
                        Forms\Components\Placeholder::make('config_helper')
                            ->label('')
                            ->content(fn ($get) => match($get('driver_name')) {
                                'manual_transfer' => '🏦 Transferencia Bancaria - Configura los datos de tu cuenta bancaria',
                                'lemon_squeezy' => '🍋 Lemon Squeezy - Necesitas API Key y Store ID',
                                'stripe' => '💳 Stripe - Necesitas Secret Key y Publishable Key',
                                default => 'Selecciona un driver arriba',
                            }),

                        // Manual Transfer Fields
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('config.bank_name')
                                    ->label('Nombre del Banco')
                                    ->visible(fn ($get) => $get('driver_name') === 'manual_transfer'),
                                
                                Forms\Components\TextInput::make('config.account_holder')
                                    ->label('Titular de la Cuenta')
                                    ->visible(fn ($get) => $get('driver_name') === 'manual_transfer'),
                                
                                Forms\Components\TextInput::make('config.account_number')
                                    ->label('Número de Cuenta')
                                    ->visible(fn ($get) => $get('driver_name') === 'manual_transfer'),
                                
                                Forms\Components\TextInput::make('config.cbu')
                                    ->label('CBU')
                                    ->visible(fn ($get) => $get('driver_name') === 'manual_transfer'),
                                
                                Forms\Components\TextInput::make('config.alias')
                                    ->label('Alias')
                                    ->visible(fn ($get) => $get('driver_name') === 'manual_transfer'),
                                
                                Forms\Components\Select::make('config.currency')
                                    ->label('Moneda')
                                    ->options([
                                        'USD' => 'USD - Dólar Estadounidense',
                                        'ARS' => 'ARS - Peso Argentino',
                                        'PEN' => 'PEN - Sol Peruano',
                                        'MXN' => 'MXN - Peso Mexicano',
                                    ])
                                    ->default('USD')
                                    ->visible(fn ($get) => $get('driver_name') === 'manual_transfer'),
                            ]),
                        
                        Forms\Components\Textarea::make('config.instructions')
                            ->label('Instrucciones para el Cliente')
                            ->rows(3)
                            ->placeholder('Ej: Enviar comprobante a pagos@emporiodigital.com')
                            ->visible(fn ($get) => $get('driver_name') === 'manual_transfer')
                            ->columnSpanFull(),

                        // Lemon Squeezy Fields
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('config.api_key')
                                    ->label('API Key')
                                    ->password()
                                    ->visible(fn ($get) => $get('driver_name') === 'lemon_squeezy'),
                                
                                Forms\Components\TextInput::make('config.store_id')
                                    ->label('Store ID')
                                    ->visible(fn ($get) => $get('driver_name') === 'lemon_squeezy'),
                                
                                Forms\Components\TextInput::make('config.webhook_secret')
                                    ->label('Webhook Secret')
                                    ->password()
                                    ->visible(fn ($get) => $get('driver_name') === 'lemon_squeezy'),
                            ]),

                        // Stripe Fields
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('config.secret_key')
                                    ->label('Secret Key')
                                    ->password()
                                    ->visible(fn ($get) => $get('driver_name') === 'stripe'),
                                
                                Forms\Components\TextInput::make('config.publishable_key')
                                    ->label('Publishable Key')
                                    ->visible(fn ($get) => $get('driver_name') === 'stripe'),
                                
                                Forms\Components\TextInput::make('config.webhook_secret')
                                    ->label('Webhook Secret')
                                    ->password()
                                    ->visible(fn ($get) => $get('driver_name') === 'stripe'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Pasarela')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('driver_name')
                    ->label('Driver')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual_transfer' => 'info',
                        'lemon_squeezy' => 'warning',
                        'stripe' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Deactivate all
                        PaymentGatewaySetting::query()->update(['is_active' => false]);
                        // Activate this one
                        $record->update(['is_active' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Pasarela activada')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin ?? false;
    }
}
