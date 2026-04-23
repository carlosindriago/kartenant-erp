<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleResource\Pages;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Módulos y Add-ons';

    protected static ?string $navigationLabel = 'Módulos';

    protected static ?string $modelLabel = 'Módulo';

    protected static ?string $pluralModelLabel = 'Módulos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Information
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Módulo')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Identificador Único')
                            ->required()
                            ->maxLength(255)
                            ->unique(Module::class, 'slug', ignoreRecord: true)
                            ->helperText('Usado para identificar el módulo en el sistema'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Select::make('category')
                            ->label('Categoría')
                            ->options(Module::getAvailableCategories())
                            ->required(),

                        Forms\Components\TextInput::make('icon')
                            ->label('Icono')
                            ->placeholder('heroicon-o-puzzle-piece')
                            ->helperText('Nombre del icono de Heroicons'),

                        Forms\Components\TextInput::make('version')
                            ->label('Versión')
                            ->default('1.0.0')
                            ->required(),

                        Forms\Components\TextInput::make('provider')
                            ->label('Proveedor')
                            ->maxLength(255)
                            ->helperText('Empresa o desarrollador del módulo'),
                    ])
                    ->columns(2),

                // Pricing Configuration
                Forms\Components\Section::make('Configuración de Precios')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('base_price_monthly')
                                    ->label('Precio Mensual')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->default(0),

                                Forms\Components\TextInput::make('base_price_yearly')
                                    ->label('Precio Anual')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->default(0),

                                Forms\Components\TextInput::make('setup_fee')
                                    ->label('Cuota de Configuración')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->nullable(),
                            ]),

                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'USD' => 'USD - Dólar Americano',
                                'EUR' => 'EUR - Euro',
                                'ARS' => 'ARS - Peso Argentino',
                                'MXN' => 'MXN - Peso Mexicano',
                                'BRL' => 'BRL - Real Brasileño',
                            ])
                            ->default('USD')
                            ->required(),

                        Forms\Components\Select::make('billing_cycle')
                            ->label('Ciclo de Facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                                'once' => 'Una vez',
                            ])
                            ->default('monthly')
                            ->required(),

                        Forms\Components\TextInput::make('trial_days')
                            ->label('Días de Prueba')
                            ->numeric()
                            ->default(0)
                            ->helperText('Días de prueba gratuita'),
                    ])
                    ->columns(1),

                // Module Configuration
                Forms\Components\Section::make('Configuración del Módulo')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_custom')
                                    ->label('Módulo Personalizado')
                                    ->helperText('Los módulos personalizados son desarrollados a medida'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Visible')
                                    ->default(true)
                                    ->helperText('Mostrar en el catálogo de módulos'),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Destacado')
                                    ->default(false)
                                    ->helperText('Mostrar como módulo recomendado'),

                                Forms\Components\Toggle::make('auto_renew')
                                    ->label('Renovación Automática')
                                    ->default(true),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ])
                    ->columns(2),

                // Feature Flags
                Forms\Components\Section::make('Flags de Funcionalidades')
                    ->schema([
                        Forms\Components\CheckboxList::make('feature_flags')
                            ->label('Funcionalidades que Habilita')
                            ->options(function () {
                                return array_map(fn ($label) => $label, [
                                    'has_inventory_management' => 'Gestión de Inventario',
                                    'has_stock_tracking' => 'Seguimiento de Stock',
                                    'has_barcode_scanning' => 'Escaneo de Códigos de Barras',
                                    'has_pos_system' => 'Sistema de Punto de Venta',
                                    'has_receipt_printing' => 'Impresión de Tickets',
                                    'has_cash_drawer' => 'Caja Registradora',
                                    'has_advanced_analytics' => 'Análisis Avanzado',
                                    'has_custom_reports' => 'Reportes Personalizados',
                                    'has_data_export' => 'Exportación de Datos',
                                    'has_api_access' => 'Acceso API',
                                    'has_webhook_support' => 'Soporte Webhooks',
                                    'has_third_party_integrations' => 'Integraciones de Terceros',
                                    'has_two_factor_auth' => 'Autenticación de Dos Factores',
                                    'has_advanced_permissions' => 'Permisos Avanzados',
                                    'has_audit_logging' => 'Registro de Auditoría',
                                    'has_custom_branding' => 'Branding Personalizado',
                                    'has_custom_fields' => 'Campos Personalizados',
                                    'has_workflow_automation' => 'Automatización de Flujos',
                                    'has_priority_support' => 'Soporte Prioritario',
                                    'has_multi_currency' => 'Multi-Moneda',
                                    'has_advanced_exports' => 'Exportaciones Avanzadas',
                                    'has_client_management' => 'Gestión de Clientes',
                                    'has_multi_language' => 'Multi-Idioma',
                                    'has_backup_automation' => 'Automatización de Backups',
                                    'has_mobile_app' => 'Aplicación Móvil',
                                ]);
                            })
                            ->helperText('Selecciona las funcionalidades que este módulo habilita')
                            ->columns(3),
                    ]),

                // Dependencies and Configuration
                Forms\Components\Section::make('Dependencias y Configuración')
                    ->schema([
                        Forms\Components\KeyValue::make('dependencies')
                            ->label('Módulos Requeridos')
                            ->helperText('Módulos que deben estar instalados para usar este módulo')
                            ->keyLabel('Slug del Módulo')
                            ->valueLabel('Versión Mínima (opcional)'),

                        Forms\Components\KeyValue::make('conflicts')
                            ->label('Módulos Incompatibles')
                            ->helperText('Módulos que entran en conflicto con este módulo'),

                        Forms\Components\KeyValue::make('limits')
                            ->label('Límites del Módulo')
                            ->keyLabel('Métrica')
                            ->valueLabel('Límite (usar "unlimited" para ilimitado)')
                            ->helperText('Ej: api_requests_per_month: 1000'),

                        Forms\Components\KeyValue::make('configuration')
                            ->label('Configuración por Defecto')
                            ->keyLabel('Clave de Configuración')
                            ->valueLabel('Valor por Defecto'),
                    ])
                    ->columns(2),

                // External Integration
                Forms\Components\Section::make('Integración Externa')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stripe_product_id')
                                    ->label('ID de Producto en Stripe')
                                    ->helperText('ID del producto en Stripe para facturación'),

                                Forms\Components\TextInput::make('stripe_price_monthly_id')
                                    ->label('ID de Precio Mensual en Stripe')
                                    ->helperText('ID del precio mensual en Stripe'),

                                Forms\Components\TextInput::make('stripe_price_yearly_id')
                                    ->label('ID de Precio Anual en Stripe')
                                    ->helperText('ID del precio anual en Stripe'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->formatStateUsing(fn ($state) => Module::getAvailableCategories()[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_price_monthly')
                    ->label('Precio Mensual')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_price_yearly')
                    ->label('Precio Anual')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_custom')
                    ->label('Personalizado')
                    ->boolean()
                    ->trueIcon('heroicon-c-star')
                    ->falseIcon('heroicon-o-cube'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star'),

                Tables\Columns\TextColumn::make('installations_count')
                    ->label('Instalaciones')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' ⭐' : 'N/A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(Module::getAvailableCategories()),

                Tables\Filters\TernaryFilter::make('is_custom')
                    ->label('Personalizado')
                    ->placeholder('Todos')
                    ->trueLabel('Personalizados')
                    ->falseLabel('Estándar'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visible')
                    ->placeholder('Todos')
                    ->trueLabel('Visibles')
                    ->falseLabel('Ocultos'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destacado')
                    ->placeholder('Todos')
                    ->trueLabel('Destacados')
                    ->falseLabel('No destacados'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Module $record) {
                        $installationCount = $record->activeTenants()->count();
                        if ($installationCount > 0) {
                            $action->cancel();
                            $action->failureNotificationTitle("No se puede eliminar el módulo '{$record->name}'");
                            $action->failureNotificationDescription("Tiene {$installationCount} instalaciones activas.");
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            $modulesWithInstallations = $records->filter(fn ($record) => $record->activeTenants()->count() > 0);
                            if ($modulesWithInstallations->isNotEmpty()) {
                                $action->cancel();
                                $names = $modulesWithInstallations->pluck('name')->join(', ');
                                $action->failureNotificationTitle('No se pueden eliminar los módulos seleccionados');
                                $action->failureNotificationDescription("Los siguientes módulos tienen instalaciones activas: {$names}");
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
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
            'index' => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'view' => Pages\ViewModule::route('/{record}'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
