<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewModule extends ViewRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    $installationCount = $this->record->activeTenants()->count();
                    if ($installationCount > 0) {
                        $action->cancel();
                        $action->failureNotificationTitle("No se puede eliminar el módulo '{$this->record->name}'");
                        $action->failureNotificationDescription("Tiene {$installationCount} instalaciones activas.");
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Basic Information
                Infolists\Components\Section::make('Información Básica')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nombre')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('slug')
                                    ->label('Slug'),

                                Infolists\Components\TextEntry::make('category')
                                    ->label('Categoría')
                                    ->formatStateUsing(fn ($state) => $this->record->getDisplayCategory()),

                                Infolists\Components\TextEntry::make('version')
                                    ->label('Versión'),

                                Infolists\Components\TextEntry::make('provider')
                                    ->label('Proveedor')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->dateTime(),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Pricing Information
                Infolists\Components\Section::make('Información de Precios')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('base_price_monthly')
                                    ->label('Precio Mensual')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('base_price_yearly')
                                    ->label('Precio Anual')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('setup_fee')
                                    ->label('Cuota de Configuración')
                                    ->money('USD')
                                    ->placeholder('Gratis'),

                                Infolists\Components\TextEntry::make('currency')
                                    ->label('Moneda'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('billing_cycle')
                                    ->label('Ciclo de Facturación')
                                    ->formatStateUsing(fn ($state) => $this->record->getDisplayBillingCycle()),

                                Infolists\Components\TextEntry::make('trial_days')
                                    ->label('Días de Prueba')
                                    ->formatStateUsing(fn ($state) => $this->record->getTrialPeriodDisplay()),

                                Infolists\Components\TextEntry::make('auto_renew')
                                    ->label('Renovación Automática')
                                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                            ]),
                    ])
                    ->columns(2),

                // Module Statistics
                Infolists\Components\Section::make('Estadísticas del Módulo')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('installations_count')
                                    ->label('Instalaciones Activas')
                                    ->alignCenter()
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('average_rating')
                                    ->label('Rating Promedio')
                                    ->formatStateUsing(fn ($state) => $state ? new HtmlString($state.' ⭐') : 'N/A')
                                    ->alignCenter(),

                                Infolists\Components\TextEntry::make('rating_count')
                                    ->label('Total de Ratings')
                                    ->alignCenter(),

                                Infolists\Components\TextEntry::make('activeTenantsCount')
                                    ->label('Tenants Activos')
                                    ->state(fn ($record) => $record->activeTenants()->count())
                                    ->alignCenter(),
                            ]),
                    ])
                    ->columns(2),

                // Module Configuration
                Infolists\Components\Section::make('Configuración del Módulo')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_custom')
                                    ->label('Personalizado')
                                    ->boolean(),

                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Activo')
                                    ->boolean(),

                                Infolists\Components\IconEntry::make('is_visible')
                                    ->label('Visible')
                                    ->boolean(),

                                Infolists\Components\IconEntry::make('is_featured')
                                    ->label('Destacado')
                                    ->boolean(),

                                Infolists\Components\TextEntry::make('sort_order')
                                    ->label('Orden'),

                                Infolists\Components\TextEntry::make('icon')
                                    ->label('Icono')
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->columns(3),

                // Feature Flags
                Infolists\Components\Section::make('Funcionalidades Habilitadas')
                    ->schema([
                        Infolists\Components\TextEntry::make('feature_flags')
                            ->label('Flags de Funcionalidades')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'Ninguna funcionalidad definida';
                                }

                                $featureNames = [];
                                foreach ($state as $flag) {
                                    $featureNames[] = "<span class='inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1 mb-1'>".Str::title(str_replace('_', ' ', $flag)).'</span>';
                                }

                                return new HtmlString(implode(' ', $featureNames));
                            })
                            ->columnSpanFull(),
                    ]),

                // Dependencies and Limits
                Infolists\Components\Section::make('Dependencias y Límites')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('dependencies')
                                    ->label('Módulos Requeridos')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return 'Sin dependencias';
                                        }

                                        return implode(', ', array_keys($state));
                                    }),

                                Infolists\Components\TextEntry::make('conflicts')
                                    ->label('Módulos Incompatibles')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return 'Sin conflictos';
                                        }

                                        return implode(', ', $state);
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('limits')
                            ->label('Límites del Módulo')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'Sin límites definidos';
                                }

                                $limitTexts = [];
                                foreach ($state as $limit => $value) {
                                    $limitTexts[] = "<strong>{$limit}:</strong> {$value}";
                                }

                                return new HtmlString(implode('<br>', $limitTexts));
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Active Tenants
                Infolists\Components\Section::make('Tenants con este Módulo')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('activeTenants')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Tenant'),

                                        Infolists\Components\TextEntry::make('pivot.status')
                                            ->label('Estado')
                                            ->badge()
                                            ->color(fn ($state) => match ($state) {
                                                'active' => 'success',
                                                'suspended' => 'warning',
                                                'cancelled' => 'danger',
                                                default => 'secondary',
                                            }),

                                        Infolists\Components\TextEntry::make('pivot.starts_at')
                                            ->label('Instalado')
                                            ->dateTime(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->record->activeTenants()->exists()),
            ]);
    }
}
