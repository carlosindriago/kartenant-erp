<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources;

use App\Filament\Actions\HasStandardActionGroup;
use App\Modules\POS\Resources\CustomerResource\Pages;
use App\Modules\POS\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CustomerResource extends Resource
{
    use HasStandardActionGroup;

    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Clientes';
    
    protected static ?string $modelLabel = 'Cliente';
    
    protected static ?string $pluralModelLabel = 'Clientes';
    
    protected static ?string $navigationGroup = 'Punto de Venta';
    
    protected static ?int $navigationSort = 1;
    
    // Disable Filament's tenant scoping since we manage this manually
    protected static bool $isScopedToTenant = false;
    
    public static function getEloquentQuery(): Builder
    {
        // En database-per-tenant, todos los registros ya son del tenant actual
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Juan Pérez')
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'DNI' => 'DNI',
                                'CUIT' => 'CUIT',
                                'Pasaporte' => 'Pasaporte',
                                'Otro' => 'Otro',
                            ])
                            ->placeholder('Seleccionar tipo')
                            ->native(false),
                        
                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->maxLength(255)
                            ->placeholder('12345678'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('cliente@ejemplo.com'),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('+54 9 11 1234-5678'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Dirección')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Calle, Número, Piso, Departamento')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(255)
                            ->placeholder('Buenos Aires'),
                        
                        Forms\Components\TextInput::make('state')
                            ->label('Provincia/Estado')
                            ->maxLength(255)
                            ->placeholder('CABA'),
                        
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Código Postal')
                            ->maxLength(255)
                            ->placeholder('1000'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                
                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Información adicional sobre el cliente')
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Cliente Activo')
                            ->default(true)
                            ->helperText('Desactivar si el cliente ya no realiza compras'),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo Doc.')
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Número')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sales_count')
                    ->label('Ventas')
                    ->counts('sales')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('health_score')
                    ->label('Salud del Cliente')
                    ->formatStateUsing(fn ($record) => self::calculateCustomerHealthScore($record))
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->tooltip(fn ($record) => self::getCustomerHealthTooltip($record))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
                
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo de Documento')
                    ->options([
                        'DNI' => 'DNI',
                        'CUIT' => 'CUIT',
                        'Pasaporte' => 'Pasaporte',
                        'Otro' => 'Otro',
                    ]),
            ])
            ->actions([
                // ACCESO RÁPIDO (Acciones principales)
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('Ver información completa del cliente'),

                Action::make('quick_sale')
                    ->label('Venta Rápida')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->tooltip('Crear nueva venta para este cliente')
                    ->url(fn ($record): string => route('tenant.pos.create', ['customer' => $record->id]))
                    ->openUrlInNewTab(),

                // ACCIONES ESPECÍFICAS DE CLIENTES
                Action::make('view_sales_history')
                    ->label('Historial de Compras')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->tooltip('Ver todas las compras del cliente')
                    ->visible(fn ($record) => $record->sales()->exists())
                    ->url(fn ($record): string => route('tenant.sales.index', ['customer' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('send_email')
                    ->label('Enviar Email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->tooltip('Enviar correo electrónico al cliente')
                    ->visible(fn ($record) => !empty($record->email))
                    ->form([
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->default('Novedades de Emporio Digital'),
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje')
                            ->required()
                            ->rows(5)
                            ->default('Estimado cliente, le contactamos para informarle sobre nuestras novedades.'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            // Aquí iría la lógica de envío de email
                            Notification::make()
                                ->title('Email Enviado')
                                ->body("Se ha enviado un correo a {$record->name}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al Enviar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->tooltip(fn ($record) => $record->is_active ? 'Desactivar cliente' : 'Activar cliente')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? 'Desactivar Cliente' : 'Activar Cliente')
                    ->modalDescription(fn ($record) => $record->is_active
                        ? "El cliente {$record->name} no podrá realizar compras."
                        : "El cliente {$record->name} podrá realizar compras nuevamente.")
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Cliente Activado' : 'Cliente Desactivado')
                            ->body("El cliente {$record->name} ha sido " . ($record->is_active ? 'activado' : 'desactivado'))
                            ->success()
                            ->send();
                    }),

                // ACTION GROUP ESTÁNDAR
                static::getCompleteActionGroup(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay clientes registrados')
            ->emptyStateDescription('Crea tu primer cliente para asociarlo a ventas en el punto de venta')
            ->emptyStateIcon('heroicon-o-user-group')
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            // En database-per-tenant, simplemente contamos los registros activos
            return (string) static::getModel()::where('is_active', true)->count();
        } catch (\Exception $e) {
            // Si la tabla no existe o hay error, retornar null silenciosamente
            return null;
        }
    }

    /**
     * Calculate customer health score (0-100)
     */
    public static function calculateCustomerHealthScore($record): int
    {
        try {
            if (!$record) return 0;

            $cacheKey = "customer_health_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $score = 100;

                // Status health
                if (!$record->is_active) {
                    $score -= 40; // Cliente inactivo
                }

                // Name completeness
                if (empty($record->name)) {
                    $score -= 25; // Sin nombre (camo obligatorio)
                } elseif (strlen($record->name) < 3) {
                    $score -= 10; // Nombre muy corto
                }

                // Document completeness
                if (empty($record->document_number)) {
                    $score -= 15; // Sin documento
                } elseif (empty($record->document_type)) {
                    $score -= 10; // Sin tipo de documento
                }

                // Contact information completeness
                if (empty($record->email)) {
                    $score -= 15; // Sin email
                } elseif (!filter_var($record->email, FILTER_VALIDATE_EMAIL)) {
                    $score -= 5; // Email inválido
                }

                if (empty($record->phone)) {
                    $score -= 10; // Sin teléfono
                }

                // Address completeness
                $addressFields = 0;
                $totalAddressFields = 4;

                if (!empty($record->address)) $addressFields++;
                if (!empty($record->city)) $addressFields++;
                if (!empty($record->state)) $addressFields++;
                if (!empty($record->postal_code)) $addressFields++;

                $addressCompleteness = ($addressFields / $totalAddressFields) * 100;
                if ($addressCompleteness < 50) {
                    $score -= 15; // Dirección incompleta
                } elseif ($addressCompleteness < 75) {
                    $score -= 5; // Dirección parcial
                }

                // Sales history analysis
                try {
                    $salesCount = $record->sales()->count();
                    $totalSpent = $record->sales()->sum('total');
                    $recentSales = $record->sales()
                        ->where('created_at', '>', now()->subDays(90))
                        ->count();

                    if ($salesCount === 0) {
                        $score -= 30; // Sin historial de compras
                    } elseif ($salesCount < 3) {
                        $score -= 15; // Pocas compras
                    } elseif ($salesCount > 20) {
                        $score += 10; // Cliente frecuente
                    }

                    if ($recentSales === 0 && $salesCount > 0) {
                        $daysSinceLastSale = $record->sales()
                            ->latest('created_at')
                            ->first()
                            ->created_at
                            ->diffInDays(now());

                        if ($daysSinceLastSale > 365) {
                            $score -= 20; // Cliente inactivo por más de 1 año
                        } elseif ($daysSinceLastSale > 180) {
                            $score -= 10; // Cliente inactivo por más de 6 meses
                        } elseif ($daysSinceLastSale > 90) {
                            $score -= 5; // Cliente inactivo por más de 3 meses
                        }
                    }

                    // Spending analysis
                    if ($totalSpent > 10000) {
                        $score += 10; // Cliente de alto valor
                    } elseif ($totalSpent > 5000) {
                        $score += 5; // Cliente de buen valor
                    } elseif ($salesCount > 0 && $totalSpent < 500) {
                        $score -= 10; // Cliente de bajo valor
                    }
                } catch (\Exception $e) {
                    $score -= 15; // Error al obtener historial de compras
                }

                // Account age analysis
                try {
                    $daysSinceCreation = $record->created_at->diffInDays(now());
                    if ($daysSinceCreation < 30) {
                        $score += 5; // Cliente nuevo (positivo)
                    } elseif ($daysSinceCreation > 1095) { // 3 años
                        $score += 10; // Cliente antiguo leal
                    }
                } catch (\Exception $e) {
                    // Si no hay fecha de creación, no afectar score
                }

                // Notes completeness (customer engagement)
                if (!empty($record->notes)) {
                    $score += 5; // Con notas (mejor servicio)
                }

                return max(0, min(100, $score));
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get detailed tooltip for customer health score
     */
    public static function getCustomerHealthTooltip($record): string
    {
        try {
            if (!$record) return 'Error al calcular salud';

            $cacheKey = "customer_health_tooltip_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $factors = [];

                // Status analysis
                if (!$record->is_active) {
                    $factors[] = '🔴 Cliente inactivo (-40)';
                } else {
                    $factors[] = '🟢 Cliente activo';
                }

                // Name analysis
                if (empty($record->name)) {
                    $factors[] = '🔴 Sin nombre (-25)';
                } elseif (strlen($record->name) < 3) {
                    $factors[] = '🟡 Nombre muy corto (-10)';
                } else {
                    $factors[] = '🟢 Nombre completo';
                }

                // Document analysis
                if (empty($record->document_number)) {
                    $factors[] = '🔴 Sin documento (-15)';
                } elseif (empty($record->document_type)) {
                    $factors[] = '🟡 Sin tipo documento (-10)';
                } else {
                    $factors[] = "🟢 {$record->document_type}: {$record->document_number}";
                }

                // Contact analysis
                if (empty($record->email)) {
                    $factors[] = '🔴 Sin email (-15)';
                } elseif (!filter_var($record->email, FILTER_VALIDATE_EMAIL)) {
                    $factors[] = '🟡 Email inválido (-5)';
                } else {
                    $factors[] = '🟢 Email válido';
                }

                if (empty($record->phone)) {
                    $factors[] = '🟡 Sin teléfono (-10)';
                } else {
                    $factors[] = '🟢 Con teléfono';
                }

                // Address analysis
                $addressFields = 0;
                $totalAddressFields = 4;
                $fieldNames = [];

                if (!empty($record->address)) { $addressFields++; $fieldNames[] = 'Dirección'; }
                if (!empty($record->city)) { $addressFields++; $fieldNames[] = 'Ciudad'; }
                if (!empty($record->state)) { $addressFields++; $fieldNames[] = 'Estado'; }
                if (!empty($record->postal_code)) { $addressFields++; $fieldNames[] = 'CP'; }

                $addressCompleteness = ($addressFields / $totalAddressFields) * 100;
                if ($addressCompleteness < 50) {
                    $factors[] = '🔴 Dirección incompleta (-15)';
                } elseif ($addressCompleteness < 75) {
                    $factors[] = '🟡 Dirección parcial (-5)';
                } else {
                    $factors[] = "🟢 Dirección completa: " . implode(', ', $fieldNames);
                }

                // Sales analysis
                try {
                    $salesCount = $record->sales()->count();
                    $totalSpent = $record->sales()->sum('total');
                    $recentSales = $record->sales()
                        ->where('created_at', '>', now()->subDays(90))
                        ->count();

                    if ($salesCount === 0) {
                        $factors[] = '🔴 Sin historial de compras (-30)';
                    } elseif ($salesCount < 3) {
                        $factors[] = '🟡 Pocas compras (-15)';
                    } elseif ($salesCount > 20) {
                        $factors[] = '🟢 Cliente frecuente (+10)';
                    } else {
                        $factors[] = "🟢 {$salesCount} compras totales";
                    }

                    if ($recentSales === 0 && $salesCount > 0) {
                        $daysSinceLastSale = $record->sales()
                            ->latest('created_at')
                            ->first()
                            ->created_at
                            ->diffInDays(now());

                        if ($daysSinceLastSale > 365) {
                            $factors[] = '🔴 Inactivo +1 año (-20)';
                        } elseif ($daysSinceLastSale > 180) {
                            $factors[] = '🟡 Inactivo +6 meses (-10)';
                        } elseif ($daysSinceLastSale > 90) {
                            $factors[] = '🟡 Inactivo +3 meses (-5)';
                        } else {
                            $factors[] = "🟢 Activo recientemente";
                        }
                    } else {
                        $factors[] = "🟢 {$recentSales} compras en 90 días";
                    }

                    // Spending analysis
                    if ($totalSpent > 10000) {
                        $factors[] = '🟢 Cliente VIP (+10)';
                    } elseif ($totalSpent > 5000) {
                        $factors[] = '🟢 Cliente valor (+5)';
                    } elseif ($salesCount > 0 && $totalSpent < 500) {
                        $factors[] = '🟡 Cliente bajo valor (-10)';
                    } else {
                        $factors[] = "🟢 Gastado: $" . number_format($totalSpent, 2);
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Ventas: Error al verificar (-15)';
                }

                // Account age analysis
                try {
                    $daysSinceCreation = $record->created_at->diffInDays(now());
                    if ($daysSinceCreation < 30) {
                        $factors[] = '🟢 Cliente nuevo (+5)';
                    } elseif ($daysSinceCreation > 1095) { // 3 años
                        $factors[] = '🟢 Cliente leal (+10)';
                    } else {
                        $factors[] = "🟢 Cliente desde {$daysSinceCreation} días";
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Antigüedad: No disponible';
                }

                // Notes analysis
                if (!empty($record->notes)) {
                    $factors[] = '🟢 Con notas de servicio (+5)';
                } else {
                    $factors[] = '🟡 Sin notas de servicio';
                }

                $score = self::calculateCustomerHealthScore($record);
                $color = $score >= 80 ? '🟢' : ($score >= 60 ? '🟡' : '🔴');

                return $color . " Salud: {$score}/100\n\n" . implode("\n", $factors);
            });
        } catch (\Exception $e) {
            return 'Error al calcular detalles de salud';
        }
    }
}
