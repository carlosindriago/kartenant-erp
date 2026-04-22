<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Resources\SubscriptionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Notifications\Notification;

class ViewSubscriptionPlan extends ViewRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar Plan')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->tooltip('Editar toda la configuración del plan'),

            Actions\DeleteAction::make()
                ->label('Eliminar Plan')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->tooltip('Eliminar este plan permanentemente'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Plan')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nombre'),
                                Infolists\Components\TextEntry::make('slug')
                                    ->label('Slug')
                                    ->badge()
                                    ->color('gray'),
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Activo')
                                    ->boolean(),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Precios y Período de Prueba')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('price_monthly')
                                    ->label('Precio Mensual')
                                    ->formatStateUsing(fn ($record) => $record->getFormattedPrice('monthly'))
                                    ->size('lg')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('price_yearly')
                                    ->label('Precio Anual')
                                    ->formatStateUsing(fn ($record) => $record->getFormattedPrice('yearly'))
                                    ->size('lg')
                                    ->weight('bold')
                                    ->helperText(fn ($record) => $record->getYearlySavingsPercentage() > 0
                                        ? 'Ahorro de ' . $record->getYearlySavingsPercentage() . '% al pagar anual'
                                        : null),

                                Infolists\Components\TextEntry::make('trial_period')
                                    ->label('Período de Prueba')
                                    ->formatStateUsing(fn ($record) =>
                                        $record->has_trial
                                            ? $record->trial_days . ' días gratis'
                                            : 'Sin período de prueba')
                                    ->badge()
                                    ->color(fn ($record) => $record->has_trial ? 'success' : 'gray'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Límites y Estrategia del Plan')
                    ->description('Configuración de límites y estrategia de manejo de excesos')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('overage_strategy')
                                    ->label('Estrategia de Límites')
                                    ->formatStateUsing(fn ($state): string =>
                                        match($state) {
                                            'strict' => '🔴 Estricta (Bloqueo inmediato)',
                                            'soft' => '🟢 Flexible (Permite tolerancia)',
                                            default => '⚪ No configurada'
                                        }
                                    )
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'strict' => 'danger',
                                        'soft' => 'success',
                                        default => 'gray'
                                    })
                                    ->helperText('Cómo el sistema maneja los excesos'),

                                Infolists\Components\TextEntry::make('overage_tolerance')
                                    ->label('Tolerancia de Exceso')
                                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : 'N/A')
                                    ->badge()
                                    ->color('warning')
                                    ->visible(fn ($record) => $record->overage_strategy === 'soft')
                                    ->helperText('Porcentaje adicional permitido sobre el límite base'),
                            ]),

                        Infolists\Components\TextEntry::make('strategy_description')
                            ->label('Comportamiento de la Estrategia')
                            ->formatStateUsing(function ($record): string {
                                $strategy = $record->overage_strategy ?? 'not_configured';
                                $tolerance = $record->overage_tolerance ?? 0;

                                return match($strategy) {
                                    'strict' => '⚡ **Bloqueo Estricto**: Las acciones administrativas se bloquean inmediatamente al alcanzar cualquier límite. El POS continúa operando normalmente.',
                                    'soft' => "💪 **Tolerancia Flexible**: Permite exceder los límites hasta un {$tolerance}% adicional. Ideal para picos de negocio temporales. El POS nunca se bloquea.",
                                    default => '❌ **Sin Configurar**: La estrategia de límites no ha sido configurada. Edita el plan para definirla.'
                                };
                            })
                            ->columnSpanFull()
                            ->markdown(),

                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('users_limit')
                                    ->label('Usuarios')
                                    ->state(function ($record): string {
                                        $baseLimit = $record->getConfigurableLimit('users');
                                        $effectiveLimit = $record->getEffectiveLimit('users');

                                        if (!$baseLimit || $baseLimit <= 0) return '∞ Ilimitado';
                                        if ($effectiveLimit === $baseLimit) return (string) $baseLimit;
                                        return "{$baseLimit} → {$effectiveLimit}";
                                    })
                                    ->badge()
                                    ->color('success')
                                    ->helperText('Límite de usuarios'),

                                Infolists\Components\TextEntry::make('products_limit')
                                    ->label('Productos')
                                    ->state(function ($record): string {
                                        $baseLimit = $record->getConfigurableLimit('products');
                                        $effectiveLimit = $record->getEffectiveLimit('products');

                                        if (!$baseLimit || $baseLimit <= 0) return '∞ Ilimitado';
                                        if ($effectiveLimit === $baseLimit) return (string) $baseLimit;
                                        return "{$baseLimit} → {$effectiveLimit}";
                                    })
                                    ->badge()
                                    ->color('success')
                                    ->helperText('Límite de productos'),

                                Infolists\Components\TextEntry::make('monthly_sales_limit')
                                    ->label('Ventas/Mes')
                                    ->state(function ($record): string {
                                        $baseLimit = $record->getConfigurableLimit('monthly_sales');
                                        $effectiveLimit = $record->getEffectiveLimit('monthly_sales');

                                        if (!$baseLimit || $baseLimit <= 0) return '∞ Ilimitado';
                                        if ($effectiveLimit === $baseLimit) return (string) $baseLimit;
                                        return "{$baseLimit} → {$effectiveLimit}";
                                    })
                                    ->badge()
                                    ->color('success')
                                    ->helperText('Ventas mensuales'),

                                Infolists\Components\TextEntry::make('storage_limit')
                                    ->label('Almacenamiento')
                                    ->state(function ($record): string {
                                        $baseLimit = $record->getConfigurableLimit('storage_mb');
                                        $effectiveLimit = $record->getEffectiveLimit('storage_mb');

                                        if (!$baseLimit || $baseLimit <= 0) return '∞ Ilimitado';
                                        if ($effectiveLimit === $baseLimit) {
                                            return $baseLimit >= 1024
                                                ? number_format($baseLimit / 1024, 1) . ' GB'
                                                : $baseLimit . ' MB';
                                        }
                                        $baseDisplay = $baseLimit >= 1024
                                            ? number_format($baseLimit / 1024, 1) . ' GB'
                                            : $baseLimit . ' MB';
                                        $effectiveDisplay = $effectiveLimit >= 1024
                                            ? number_format($effectiveLimit / 1024, 1) . ' GB'
                                            : $effectiveLimit . ' MB';
                                        return "{$baseDisplay} → {$effectiveDisplay}";
                                    })
                                    ->badge()
                                    ->color('success')
                                    ->helperText('Espacio de almacenamiento'),
                            ]),

                        Infolists\Components\TextEntry::make('limits_explanation')
                            ->label('Leyenda de Límites')
                            ->state(function ($record): string {
                                if (!$record->hasConfigurableLimits()) {
                                    return '⚠️ **Sin límites configurados**: Este plan no tiene límites definidos. Edita el plan para configurarlos.';
                                }

                                if ($record->allowsOverage()) {
                                    $tolerance = $record->overage_tolerance ?? 0;
                                    return "📊 **Formato**: `base → efectivo`  \n🎯 **Base**: Límite estándar  \n⚡ **Efectivo**: Límite con tolerancia del {$tolerance}%  \n💡 El sistema avisa al superar el límite base pero permite continuar hasta el efectivo.";
                                }

                                return "📊 **Formato**: `límite único`  \n⚡ **Límite estricto**: Bloqueo inmediato al alcanzar el límite  \n🚫 Sin tolerancia permitida.";
                            })
                            ->columnSpanFull()
                            ->markdown()
                            ->visible(fn ($record) => $record->hasConfigurableLimits()),
                    ])
                    ->visible(fn ($record) => true), // Always show limits section

                Infolists\Components\Section::make('Módulos Habilitados')
                    ->schema([
                        Infolists\Components\TextEntry::make('enabled_modules')
                            ->label('Módulos')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(20),
                    ])
                    ->visible(fn ($record) => !empty($record->enabled_modules)),

                Infolists\Components\Section::make('Características del Plan')
                    ->schema([
                        Infolists\Components\TextEntry::make('features')
                            ->label('Features')
                            ->formatStateUsing(function ($record): string {
                                $features = $record->getFormattedFeatures();
                                return empty($features) ? '' : implode("\n", array_map(fn($feature) => "• {$feature}", $features));
                            })
                            ->markdown(),
                    ])
                    ->visible(fn ($record) => !empty($record->getFormattedFeatures())),

                Infolists\Components\Section::make('Configuración Avanzada')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_visible')
                                    ->label('Visible en Precios')
                                    ->boolean(),

                                Infolists\Components\IconEntry::make('is_featured')
                                    ->label('Plan Destacado')
                                    ->boolean(),

                                Infolists\Components\TextEntry::make('sort_order')
                                    ->label('Orden de Visualización')
                                    ->badge(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Integración con Stripe')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('stripe_product_id')
                                    ->label('Product ID')
                                    ->placeholder('No configurado')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('stripe_price_monthly_id')
                                    ->label('Price ID Mensual')
                                    ->placeholder('No configurado')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('stripe_price_yearly_id')
                                    ->label('Price ID Anual')
                                    ->placeholder('No configurado')
                                    ->copyable(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Estadísticas')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('subscriptions_count')
                                    ->label('Suscripciones Activas')
                                    ->state(fn ($record) => $record->subscriptions()->count())
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Creado')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }
}
