<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Suscripciones';

    protected static ?string $navigationLabel = 'Planes de Suscripción';

    protected static ?string $modelLabel = 'Plan de Suscripción';

    protected static ?string $pluralModelLabel = 'Planes de Suscripción';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Plan')
                    ->description('Información básica del plan de suscripción')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del Plan')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Plan Profesional')
                                    ->helperText('Nombre visible para los clientes')
                                    ->prefixIcon('heroicon-o-tag'),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Identificador Único')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('profesional')
                                    ->helperText('Se usa en URLs. Solo letras minúsculas y guiones')
                                    ->prefixIcon('heroicon-o-link'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción del Plan')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe los beneficios y características principales...')
                            ->helperText('Esta descripción aparecerá en la página de precios')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Precios y Facturación')
                    ->description('Configura los precios y ciclos de facturación')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('price_monthly')
                                    ->label('Precio Mensual')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Precio del ciclo mensual'),

                                Forms\Components\TextInput::make('price_yearly')
                                    ->label('Precio Anual')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Precio del ciclo anual (usualmente con descuento)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $monthly = (float) $get('price_monthly');
                                        $yearly = (float) $state;

                                        if ($monthly > 0 && $yearly > 0) {
                                            $monthlyTotal = $monthly * 12;
                                            $savings = round((($monthlyTotal - $yearly) / $monthlyTotal) * 100, 1);
                                            $set('yearly_savings_indicator', $savings > 0 ? "Ahorro: {$savings}%" : 'Sin descuento');
                                        } else {
                                            $set('yearly_savings_indicator', '');
                                        }
                                    }),

                                Forms\Components\Select::make('currency')
                                    ->label('Moneda')
                                    ->required()
                                    ->options([
                                        'USD' => 'USD - Dólar Estadounidense',
                                        'EUR' => 'EUR - Euro',
                                        'MXN' => 'MXN - Peso Mexicano',
                                        'ARS' => 'ARS - Peso Argentino',
                                        'CLP' => 'CLP - Peso Chileno',
                                        'COP' => 'COP - Peso Colombiano',
                                    ])
                                    ->default('USD')
                                    ->searchable()
                                    ->helperText('Moneda para todos los precios'),
                            ]),

                        Forms\Components\Placeholder::make('yearly_savings_indicator')
                            ->label('Indicador de Ahorro Anual')
                            ->content(fn ($get) => $get('yearly_savings_indicator') ?: 'Completa ambos precios para calcular ahorro')
                            ->columnSpan(3),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('has_trial')
                                    ->label('Ofrecer Período de Prueba')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Permite a los clientes probar gratis antes de pagar'),

                                Forms\Components\TextInput::make('trial_days')
                                    ->label('Días de Prueba Gratuita')
                                    ->numeric()
                                    ->default(14)
                                    ->minValue(1)
                                    ->maxValue(90)
                                    ->suffix('días')
                                    ->visible(fn ($get) => $get('has_trial'))
                                    ->helperText('Duración del período de prueba'),
                            ]),
                    ]),

                Forms\Components\Section::make('Límites de Recursos')
                    ->description('Define los límites de uso para este plan. Deja en 0 para ilimitado.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('limits.monthly_sales')
                                    ->label('Ventas Mensuales')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->placeholder('0 = Ilimitado')
                                    ->suffix('ventas/mes')
                                    ->helperText('Límite de ventas por mes. 0 = sin límite')
                                    ->prefixIcon('heroicon-o-chart-bar')
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('limits.users')
                                    ->label('Usuarios Máximos')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->placeholder('1')
                                    ->suffix('usuarios')
                                    ->helperText('Número máximo de usuarios en el plan')
                                    ->prefixIcon('heroicon-o-users')
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('limits.storage')
                                    ->label('Almacenamiento')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1024)
                                    ->placeholder('1024')
                                    ->suffix('MB')
                                    ->helperText('Espacio de almacenamiento. 1024 MB = 1 GB')
                                    ->prefixIcon('heroicon-o-server')
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('limits.products')
                                    ->label('Productos Máximos')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->placeholder('0 = Ilimitado')
                                    ->suffix('productos')
                                    ->helperText('Límite de productos en catálogo. 0 = sin límite')
                                    ->prefixIcon('heroicon-o-cube')
                                    ->live(onBlur: true),
                            ]),
                    ]),

                Forms\Components\Section::make('Estrategia de Exceso')
                    ->description('Configura cómo el sistema manejará los excesos de límites')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('overage_strategy')
                                    ->label('Estrategia de Límites')
                                    ->options([
                                        'strict' => '🔴 Estricto - Bloqueo inmediato',
                                        'soft' => '🟢 Flexible - Permite tolerancia',
                                    ])
                                    ->default('soft')
                                    ->required()
                                    ->live()
                                    ->helperText('Estricto: Bloquea al alcanzar límite. Flexible: Permite exceso con tolerancia.')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('overage_tolerance', $state === 'soft' ? 20 : null);
                                    }),

                                Forms\Components\TextInput::make('overage_tolerance')
                                    ->label('Porcentaje de Tolerancia')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(20)
                                    ->suffix('%')
                                    ->visible(fn ($get) => $get('overage_strategy') === 'soft')
                                    ->required(fn ($get) => $get('overage_strategy') === 'soft')
                                    ->helperText('Porcentaje adicional permitido sobre el límite base (1-50%)')
                                    ->live(onBlur: true),
                            ]),

                        Forms\Components\Placeholder::make('effective_limits_preview')
                            ->label('Vista Previa de Límites Efectivos')
                            ->content(function ($get) {
                                $strategy = $get('overage_strategy');
                                $tolerance = (int) ($get('overage_tolerance') ?? 0);
                                $limits = $get('limits') ?? [];

                                if (empty($limits) || ! is_array($limits)) {
                                    return 'Configura los límites para ver la vista previa';
                                }

                                $previewLines = [];
                                foreach (['monthly_sales' => 'Ventas', 'users' => 'Usuarios', 'storage' => 'Almacenamiento', 'products' => 'Productos'] as $key => $label) {
                                    $baseLimit = (int) ($limits[$key] ?? 0);
                                    if ($baseLimit > 0) {
                                        if ($strategy === 'soft' && $tolerance > 0) {
                                            $effective = $baseLimit + (int) ($baseLimit * $tolerance / 100);
                                            $excess = $effective - $baseLimit;
                                            $previewLines[] = "**{$label}**: {$baseLimit} → {$effective} (permite {$excess} de exceso)";
                                        } else {
                                            $previewLines[] = "**{$label}**: {$baseLimit} (límite estricto)";
                                        }
                                    } else {
                                        $previewLines[] = "**{$label}**: Ilimitado";
                                    }
                                }

                                return empty($previewLines) ? 'Configura al menos un límite' : implode("  \n", $previewLines);
                            })
                            ->columnSpanFull()
                            ->visible(fn ($get) => ! empty($get('limits'))),
                    ]),

                Forms\Components\Section::make('Características Adicionales')
                    ->description('Activa las características especiales incluidas en este plan')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('features.has_api_access')
                                    ->label('Acceso API')
                                    ->default(false)
                                    ->helperText('Permite acceso a la API REST para integraciones'),

                                Forms\Components\Toggle::make('features.has_advanced_analytics')
                                    ->label('Análisis Avanzado')
                                    ->default(false)
                                    ->helperText('Reportes detallados y métricas avanzadas'),

                                Forms\Components\Toggle::make('features.has_priority_support')
                                    ->label('Soporte Prioritario')
                                    ->default(false)
                                    ->helperText('Respuesta prioritaria en tickets de soporte'),

                                Forms\Components\Toggle::make('features.has_custom_branding')
                                    ->label('Branding Personalizado')
                                    ->default(false)
                                    ->helperText('Permite personalizar colores y logos del sistema'),
                            ]),
                    ]),

                Forms\Components\Section::make('Módulos del Sistema')
                    ->description('Selecciona los módulos que estarán disponibles en este plan')
                    ->icon('heroicon-o-cube-transparent')
                    ->schema([
                        Forms\Components\CheckboxList::make('enabled_modules')
                            ->label('Módulos Habilitados')
                            ->columns(3)
                            ->options([
                                'inventory' => '📦 Inventario',
                                'pos' => '💳 Punto de Venta (POS)',
                                'clients' => '👥 Clientes',
                                'suppliers' => '🏪 Proveedores',
                                'purchases' => '🛒 Compras',
                                'reports' => '📊 Reportes',
                                'accounting' => '📈 Contabilidad',
                                'manufacturing' => '🏭 Manufactura',
                                'ecommerce' => '🌐 eCommerce',
                            ])
                            ->helperText('Selecciona todos los módulos que los clientes podrán usar')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuración de Publicación')
                    ->description('Controla cómo y dónde se muestra este plan')
                    ->icon('heroicon-o-eye')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Plan Activo')
                                    ->default(true)
                                    ->helperText('Acepta nuevas suscripciones'),

                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Visible al Público')
                                    ->default(true)
                                    ->helperText('Se muestra en página de precios'),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Plan Destacado')
                                    ->default(false)
                                    ->helperText('Marcado como recomendado'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Número más bajo = aparece primero'),
                            ]),
                    ]),

                Forms\Components\Section::make('Integración con Stripe')
                    ->description('Configura los IDs de productos y precios de Stripe (opcional)')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('stripe_product_id')
                                    ->label('Product ID')
                                    ->maxLength(255)
                                    ->placeholder('prod_XXXXXXXXXX')
                                    ->helperText('ID del producto en Stripe'),

                                Forms\Components\TextInput::make('stripe_price_monthly_id')
                                    ->label('Price ID Mensual')
                                    ->maxLength(255)
                                    ->placeholder('price_XXXXXXXXXX')
                                    ->helperText('ID del precio mensual en Stripe'),

                                Forms\Components\TextInput::make('stripe_price_yearly_id')
                                    ->label('Price ID Anual')
                                    ->maxLength(255)
                                    ->placeholder('price_XXXXXXXXXX')
                                    ->helperText('ID del precio anual en Stripe'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Plan')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (SubscriptionPlan $record): string => $record->slug),

                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Precio Mensual')
                    ->formatStateUsing(fn (SubscriptionPlan $record) => $record->getFormattedPrice('monthly'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('price_yearly')
                    ->label('Precio Anual')
                    ->formatStateUsing(fn (SubscriptionPlan $record) => $record->getFormattedPrice('yearly'))
                    ->description(fn (SubscriptionPlan $record) => $record->getYearlySavingsPercentage() > 0
                        ? 'Ahorro: '.$record->getYearlySavingsPercentage().'%'
                        : null)
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Suscripciones')
                    ->counts('subscriptions')
                    ->badge()
                    ->color(fn ($record) => $record->subscriptions()->count() > 0 ? 'warning' : 'success')
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->subscriptions()->count() > 0
                        ? 'Este plan no puede ser eliminado mientras tenga suscripciones activas'
                        : 'Este plan puede ser eliminado'),

                Tables\Columns\IconColumn::make('has_trial')
                    ->label('Trial')
                    ->boolean()
                    ->alignCenter()
                    ->tooltip(fn (SubscriptionPlan $record) => $record->has_trial
                        ? $record->trial_days.' días'
                        : 'Sin trial'),

                Tables\Columns\TextColumn::make('limits_summary')
                    ->label('Límites')
                    ->formatStateUsing(function (SubscriptionPlan $record): string {
                        $limits = [];

                        // Use new limits structure first, fall back to legacy if needed
                        $usersLimit = $record->getConfigurableLimit('users') ?: $record->max_users;
                        $productsLimit = $record->getConfigurableLimit('products') ?: $record->max_products;
                        $salesLimit = $record->getConfigurableLimit('monthly_sales') ?: $record->max_sales_per_month;

                        if ($usersLimit) {
                            $limits[] = $usersLimit.' 👥';
                        }
                        if ($productsLimit) {
                            $limits[] = $productsLimit.' 📦';
                        }
                        if ($salesLimit) {
                            $limits[] = $salesLimit.' 💳';
                        }

                        if (empty($limits)) {
                            return 'Ilimitado ∞';
                        }

                        $summary = implode(' • ', $limits);

                        // Add strategy indicator
                        if ($record->overage_strategy === 'soft') {
                            $summary .= ' (flexible)';
                        } elseif ($record->overage_strategy === 'strict') {
                            $summary .= ' (estricto)';
                        }

                        return $summary;
                    })
                    ->wrap()
                    ->size('sm')
                    ->tooltip(function (SubscriptionPlan $record): string {
                        if (! $record->hasConfigurableLimits() && ! $record->max_users && ! $record->max_products && ! $record->max_sales_per_month) {
                            return 'Este plan no tiene límites configurados';
                        }

                        $tooltip = "Configuración de límites:\n";

                        $usersLimit = $record->getConfigurableLimit('users') ?: $record->max_users;
                        $productsLimit = $record->getConfigurableLimit('products') ?: $record->max_products;
                        $salesLimit = $record->getConfigurableLimit('monthly_sales') ?: $record->max_sales_per_month;

                        if ($usersLimit) {
                            $tooltip .= "• Usuarios: {$usersLimit}\n";
                        }
                        if ($productsLimit) {
                            $tooltip .= "• Productos: {$productsLimit}\n";
                        }
                        if ($salesLimit) {
                            $tooltip .= "• Ventas/mes: {$salesLimit}\n";
                        }

                        if ($record->allowsOverage()) {
                            $tolerance = $record->overage_tolerance ?? $record->overage_percentage ?? 0;
                            $tooltip .= "\n💡 Estrategia flexible con {$tolerance}% de tolerancia";
                        } elseif ($record->overage_strategy === 'strict') {
                            $tooltip .= "\n⚡ Estrategia estricta (bloqueo inmediato)";
                        }

                        return $tooltip;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('can_be_deleted')
                    ->label('🗑️')
                    ->boolean()
                    ->alignCenter()
                    ->getStateUsing(function (SubscriptionPlan $record): bool {
                        return $record->subscriptions()->count() === 0
                            && ! $record->is_active
                            && ! $record->is_visible
                            && ! $record->is_featured;
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(function (SubscriptionPlan $record): string {
                        $issues = [];

                        if ($record->subscriptions()->count() > 0) {
                            $issues[] = "tiene {$record->subscriptions()->count()} suscripciones";
                        }
                        if ($record->is_active) {
                            $issues[] = 'está activo';
                        }
                        if ($record->is_visible) {
                            $issues[] = 'es visible';
                        }
                        if ($record->is_featured) {
                            $issues[] = 'está destacado';
                        }

                        if (empty($issues)) {
                            return '✅ Este plan puede ser eliminado';
                        }

                        return '❌ No se puede eliminar: '.implode(', ', $issues);
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo')
                    ->placeholder('Todos los planes')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visible')
                    ->placeholder('Todos los planes')
                    ->trueLabel('Solo visibles')
                    ->falseLabel('Solo ocultos'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destacado')
                    ->placeholder('Todos los planes')
                    ->trueLabel('Solo destacados')
                    ->falseLabel('Solo no destacados'),

                Tables\Filters\TernaryFilter::make('has_trial')
                    ->label('Con Trial')
                    ->placeholder('Todos los planes')
                    ->trueLabel('Con período de prueba')
                    ->falseLabel('Sin período de prueba'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (SubscriptionPlan $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (SubscriptionPlan $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (SubscriptionPlan $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (SubscriptionPlan $record) => $record->is_active ? 'Desactivar Plan Activo' : 'Activar Plan Inactivo')
                    ->modalDescription(fn (SubscriptionPlan $record) => $record->is_active
                        ? '¿Estás seguro de que quieres desactivar este plan? No aceptará nuevas suscripciones pero las existentes continuarán activas.'
                        : '¿Estás seguro de que quieres activar este plan? Estará disponible para nuevas suscripciones.')
                    ->modalSubmitActionLabel(fn (SubscriptionPlan $record) => $record->is_active ? 'Sí, Desactivar' : 'Sí, Activar')
                    ->action(fn (SubscriptionPlan $record) => $record->update(['is_active' => ! $record->is_active]))
                    ->successNotificationTitle(fn (SubscriptionPlan $record) => 'Plan '.($record->is_active ? 'activado' : 'desactivado').' correctamente'),

                Tables\Actions\DeleteAction::make()
                    ->before(function (SubscriptionPlan $record, Tables\Actions\DeleteAction $action) {
                        $restrictions = [];

                        // Check for active subscriptions
                        if ($record->subscriptions()->count() > 0) {
                            $restrictions[] = "tiene {$record->subscriptions()->count()} suscripciones asociadas";
                        }

                        // Check if plan is active
                        if ($record->is_active) {
                            $restrictions[] = 'está activo';
                        }

                        // Check if plan is visible
                        if ($record->is_visible) {
                            $restrictions[] = 'es visible públicamente';
                        }

                        // Check if plan is featured
                        if ($record->is_featured) {
                            $restrictions[] = 'está destacado';
                        }

                        // If any restriction exists, prevent deletion
                        if (! empty($restrictions)) {
                            $action->cancel();
                            $restrictionsText = implode(', ', $restrictions);
                            $planName = $record->name;
                            $action->failureNotificationTitle('No se puede eliminar el plan');
                            $action->failureNotificationDescription("El plan '{$planName}' {$restrictionsText}. Solo pueden eliminarse planes inactivos, no visibles, no destacados y sin suscripciones activas.");
                        }
                    }),

                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicar')
                    ->excludeAttributes(['slug', 'stripe_product_id', 'stripe_price_monthly_id', 'stripe_price_yearly_id'])
                    ->beforeReplicaSaved(function (SubscriptionPlan $replica): void {
                        $replica->name = $replica->name.' (Copia)';
                        $replica->slug = $replica->slug.'-copy-'.time();
                        $replica->is_active = false;
                    })
                    ->successNotificationTitle('Plan duplicado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            $blockedPlans = [];

                            foreach ($records as $record) {
                                $restrictions = [];

                                // Check for active subscriptions
                                if ($record->subscriptions()->count() > 0) {
                                    $restrictions[] = "tiene {$record->subscriptions()->count()} suscripciones";
                                }

                                // Check if plan is active
                                if ($record->is_active) {
                                    $restrictions[] = 'está activo';
                                }

                                // Check if plan is visible
                                if ($record->is_visible) {
                                    $restrictions[] = 'es visible';
                                }

                                // Check if plan is featured
                                if ($record->is_featured) {
                                    $restrictions[] = 'está destacado';
                                }

                                // If any restriction exists, add to blocked plans
                                if (! empty($restrictions)) {
                                    $restrictionsText = implode(', ', $restrictions);
                                    $blockedPlans[] = "{$record->name} ({$restrictionsText})";
                                }
                            }

                            // If there are blocked plans, prevent deletion and show notification
                            if (! empty($blockedPlans)) {
                                Notification::make()
                                    ->danger()
                                    ->title('No se pueden eliminar los planes seleccionados')
                                    ->body('Bloqueados: '.implode(' | ', $blockedPlans).'. Solo pueden eliminarse planes inactivos, no visibles, no destacados y sin suscripciones activas.')
                                    ->send();

                                $action->halt();
                            }
                        }),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('hide_from_public')
                        ->label('Ocultar seleccionados')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_visible' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('show_to_public')
                        ->label('Mostrar seleccionados')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_visible' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('set_as_featured')
                        ->label('Marcar como destacados')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('remove_from_featured')
                        ->label('Quitar destacados')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_featured' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
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
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'archived' => Pages\ListArchivedPlans::route('/archived'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'view' => Pages\ViewSubscriptionPlan::route('/{record}'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('deleted_at') // Show non-archived plans (both active and inactive)
            ->ordered();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Plan')
                ->modalHeading('Crear Nuevo Plan de Suscripción')
                ->modalDescription('Configura un nuevo plan de suscripción con precios, límites y características.')
                ->successNotificationTitle('Plan creado correctamente'),

            Actions\Action::make('view_archived')
                ->label('Gestionar Archivados')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->url(fn () => SubscriptionPlanResource::getUrl('archived'))
                ->openUrlInNewTab(false)
                ->tooltip('Ver planes inactivos y archivados - Restaurar, activar o eliminar permanentemente'),
        ];
    }
}
