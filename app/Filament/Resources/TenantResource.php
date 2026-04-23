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

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Mail\WelcomeNewTenant;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantStatsService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Tiendas Activas';

    protected static ?string $modelLabel = 'Tienda Activa';

    protected static ?string $pluralModelLabel = 'Tiendas Activas';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos de la Empresa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('Nombre de la Empresa')
                            ->maxLength(255),
                        TextInput::make('domain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Dominio')
                            ->helperText('Ejemplo: tornillostore (se accederá como tornillostore.kartenant.test)')
                            ->alphaDash()
                            ->maxLength(255),
                        TextInput::make('database')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Nombre de Base de Datos')
                            ->helperText('Prefijo recomendado: tenant_')
                            ->alphaDash()
                            ->maxLength(255),
                        TextInput::make('cuit')
                            ->label('CUIT / RUT / RFC')
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                    ]),

                Section::make('Plan de Suscripción')
                    ->description('Selecciona el plan inicial para este tenant')
                    ->columns(2)
                    ->schema([
                        Select::make('subscription_plan_id')
                            ->label('Plan de Suscripción')
                            ->options(function () {
                                return DB::connection('landlord')
                                    ->table('subscription_plans')
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('subscription_plan_details', $state))
                            ->helperText('Selecciona el plan que tendrá este tenant'),

                        Select::make('billing_cycle')
                            ->label('Ciclo de Facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                            ])
                            ->default('monthly')
                            ->required()
                            ->reactive(),

                        Placeholder::make('plan_info')
                            ->label('Información del Plan')
                            ->content(function ($get) {
                                $planId = $get('subscription_plan_id');
                                if (! $planId) {
                                    return 'Selecciona un plan para ver detalles';
                                }

                                $plan = SubscriptionPlan::find($planId);
                                if (! $plan) {
                                    return 'Plan no encontrado';
                                }

                                $cycle = $get('billing_cycle') ?? 'monthly';
                                $price = $plan->getFormattedPrice($cycle);

                                $limits = [];
                                if ($plan->max_users) {
                                    $limits[] = "{$plan->max_users} usuarios";
                                }
                                if ($plan->max_products) {
                                    $limits[] = "{$plan->max_products} productos";
                                }
                                if ($plan->max_sales_per_month) {
                                    $limits[] = "{$plan->max_sales_per_month} ventas/mes";
                                }

                                $limitsText = count($limits) > 0 ? implode(' • ', $limits) : 'Sin límites';

                                return new \Illuminate\Support\HtmlString(
                                    "<div class='space-y-2'>
                                        <div><strong>Precio:</strong> {$price}</div>
                                        <div><strong>Límites:</strong> {$limitsText}</div>
                                        ".($plan->has_trial ? "<div class='text-green-600'><strong>Trial:</strong> {$plan->trial_days} días gratis</div>" : '').'
                                    </div>'
                                );
                            })
                            ->columnSpanFull(),

                        Toggle::make('start_trial')
                            ->label('Iniciar con Período de Prueba')
                            ->helperText('Si está activado, el tenant comenzará en trial gratuito')
                            ->reactive()
                            ->default(true)
                            ->visible(function ($get) {
                                $planId = $get('subscription_plan_id');
                                if (! $planId) {
                                    return false;
                                }
                                $plan = SubscriptionPlan::find($planId);

                                return $plan && $plan->has_trial;
                            }),

                        TextInput::make('trial_days_override')
                            ->label('Días de Trial (Override)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->helperText('Dejar vacío para usar días de trial del plan')
                            ->visible(fn ($get) => $get('start_trial') === true),
                    ]),

                Section::make('Configuración Regional')
                    ->columns(3)
                    ->schema([
                        Select::make('timezone')
                            ->label('Zona Horaria')
                            ->options([
                                // === PAÍSES PRIORITARIOS ===
                                '🇦🇷 Argentina' => [
                                    'America/Argentina/Buenos_Aires' => 'Buenos Aires (GMT-3)',
                                    'America/Argentina/Cordoba' => 'Córdoba (GMT-3)',
                                    'America/Argentina/Mendoza' => 'Mendoza (GMT-3)',
                                    'America/Argentina/Salta' => 'Salta (GMT-3)',
                                    'America/Argentina/Ushuaia' => 'Ushuaia (GMT-3)',
                                    'America/Argentina/Tucuman' => 'Tucumán (GMT-3)',
                                ],
                                '🇵🇪 Perú' => [
                                    'America/Lima' => 'Lima (GMT-5)',
                                ],
                                '🇻🇪 Venezuela' => [
                                    'America/Caracas' => 'Caracas (GMT-4)',
                                ],
                                '🇨🇴 Colombia' => [
                                    'America/Bogota' => 'Bogotá (GMT-5)',
                                ],

                                // === RESTO DE AMÉRICA (ALFABÉTICO) ===
                                '🇧🇴 Bolivia' => [
                                    'America/La_Paz' => 'La Paz (GMT-4)',
                                ],
                                '🇧🇷 Brasil' => [
                                    'America/Sao_Paulo' => 'São Paulo (GMT-3)',
                                    'America/Rio_Branco' => 'Acre (GMT-5)',
                                    'America/Manaus' => 'Manaus (GMT-4)',
                                    'America/Recife' => 'Recife (GMT-3)',
                                    'America/Fortaleza' => 'Fortaleza (GMT-3)',
                                    'America/Belem' => 'Belém (GMT-3)',
                                    'America/Noronha' => 'Fernando de Noronha (GMT-2)',
                                ],
                                '🇨🇦 Canadá' => [
                                    'America/Toronto' => 'Toronto (GMT-5/-4)',
                                    'America/Vancouver' => 'Vancouver (GMT-8/-7)',
                                    'America/Edmonton' => 'Edmonton (GMT-7/-6)',
                                    'America/Winnipeg' => 'Winnipeg (GMT-6/-5)',
                                    'America/Halifax' => 'Halifax (GMT-4/-3)',
                                    'America/St_Johns' => 'St. Johns (GMT-3:30/-2:30)',
                                ],
                                '🇨🇱 Chile' => [
                                    'America/Santiago' => 'Santiago (GMT-4/-3)',
                                    'Pacific/Easter' => 'Isla de Pascua (GMT-6/-5)',
                                ],
                                '🇨🇷 Costa Rica' => [
                                    'America/Costa_Rica' => 'San José (GMT-6)',
                                ],
                                '🇨🇺 Cuba' => [
                                    'America/Havana' => 'La Habana (GMT-5/-4)',
                                ],
                                '🇪🇨 Ecuador' => [
                                    'America/Guayaquil' => 'Guayaquil (GMT-5)',
                                    'Pacific/Galapagos' => 'Galápagos (GMT-6)',
                                ],
                                '🇸🇻 El Salvador' => [
                                    'America/El_Salvador' => 'San Salvador (GMT-6)',
                                ],
                                '🇺🇸 Estados Unidos' => [
                                    'America/New_York' => 'Nueva York (GMT-5/-4)',
                                    'America/Chicago' => 'Chicago (GMT-6/-5)',
                                    'America/Denver' => 'Denver (GMT-7/-6)',
                                    'America/Los_Angeles' => 'Los Ángeles (GMT-8/-7)',
                                    'America/Phoenix' => 'Phoenix (GMT-7)',
                                    'America/Anchorage' => 'Alaska (GMT-9/-8)',
                                    'Pacific/Honolulu' => 'Hawái (GMT-10)',
                                ],
                                '🇬🇹 Guatemala' => [
                                    'America/Guatemala' => 'Ciudad de Guatemala (GMT-6)',
                                ],
                                '🇬🇾 Guyana' => [
                                    'America/Guyana' => 'Georgetown (GMT-4)',
                                ],
                                '🇭🇹 Haití' => [
                                    'America/Port-au-Prince' => 'Puerto Príncipe (GMT-5/-4)',
                                ],
                                '🇭🇳 Honduras' => [
                                    'America/Tegucigalpa' => 'Tegucigalpa (GMT-6)',
                                ],
                                '🇯🇲 Jamaica' => [
                                    'America/Jamaica' => 'Kingston (GMT-5)',
                                ],
                                '🇲🇽 México' => [
                                    'America/Mexico_City' => 'Ciudad de México (GMT-6/-5)',
                                    'America/Tijuana' => 'Tijuana (GMT-8/-7)',
                                    'America/Monterrey' => 'Monterrey (GMT-6/-5)',
                                    'America/Cancun' => 'Cancún (GMT-5)',
                                    'America/Chihuahua' => 'Chihuahua (GMT-7/-6)',
                                ],
                                '🇳🇮 Nicaragua' => [
                                    'America/Managua' => 'Managua (GMT-6)',
                                ],
                                '🇵🇦 Panamá' => [
                                    'America/Panama' => 'Ciudad de Panamá (GMT-5)',
                                ],
                                '🇵🇾 Paraguay' => [
                                    'America/Asuncion' => 'Asunción (GMT-4/-3)',
                                ],
                                '🇵🇷 Puerto Rico' => [
                                    'America/Puerto_Rico' => 'San Juan (GMT-4)',
                                ],
                                '🇩🇴 República Dominicana' => [
                                    'America/Santo_Domingo' => 'Santo Domingo (GMT-4)',
                                ],
                                '🇸🇷 Surinam' => [
                                    'America/Paramaribo' => 'Paramaribo (GMT-3)',
                                ],
                                '🇺🇾 Uruguay' => [
                                    'America/Montevideo' => 'Montevideo (GMT-3)',
                                ],

                                // === OTROS ===
                                '🌍 Otros' => [
                                    'Europe/Madrid' => 'España - Madrid (GMT+1/+2)',
                                    'Europe/Lisbon' => 'Portugal - Lisboa (GMT+0/+1)',
                                    'UTC' => 'UTC (GMT+0)',
                                ],
                            ])
                            ->default('America/Argentina/Buenos_Aires')
                            ->required()
                            ->searchable()
                            ->helperText('Zona horaria para fechas y horas del sistema')
                            ->native(false),

                        Select::make('locale')
                            ->label('Idioma')
                            ->options([
                                'es' => 'Español',
                                'en' => 'English',
                                'pt' => 'Português',
                            ])
                            ->default('es')
                            ->required(),

                        Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'USD' => 'USD - Dólar Estadounidense',
                                'EUR' => 'EUR - Euro',
                                'MXN' => 'MXN - Peso Mexicano',
                                'ARS' => 'ARS - Peso Argentino',
                                'CLP' => 'CLP - Peso Chileno',
                                'COP' => 'COP - Peso Colombiano',
                                'PEN' => 'PEN - Sol Peruano',
                                'BRL' => 'BRL - Real Brasileño',
                            ])
                            ->default('USD')
                            ->required()
                            ->searchable(),
                    ]),

                Section::make('Administrador Principal del Tenant')
                    ->description('Datos del usuario administrador que se creará')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_name')
                            ->required()
                            ->label('Nombre del Administrador')
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->required()
                            ->email()
                            ->label('Email del Administrador')
                            ->helperText('Se enviará un email de bienvenida con credenciales temporales')
                            ->unique(table: User::class, column: 'email', ignoreRecord: true)
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // HEADER SECTION - Tenant Information & Status
                Components\Section::make('Perfil de la Tienda')
                    ->description('Información general y estado actual del tenant')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                // Logo Display
                                Components\ImageEntry::make('logo_path')
                                    ->label('Logo')
                                    ->visible(fn ($record) => $record->logo_path && $record->logo_type === 'image')
                                    ->size(80)
                                    ->circular()
                                    ->defaultImageUrl(fn ($record) => $record->logo_type === 'text'
                                        ? 'data:image/svg+xml;base64,'.base64_encode(
                                            '<svg width="80" height="80" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="80" height="80" fill="'.($record->logo_background_color ?? '#3B82F6').'"/>
                                                <text x="40" y="45" font-family="Arial" font-size="24" font-weight="bold"
                                                      text-anchor="middle" fill="'.($record->logo_text_color ?? '#FFFFFF').'">
                                                    '.strtoupper(substr($record->name, 0, 2)).'
                                                </text>
                                            </svg>'
                                        )
                                        : null
                                    ),

                                // Basic Info Grid
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('name')
                                            ->label('Nombre de la Tienda')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->columnSpan(2),

                                        Components\TextEntry::make('status')
                                            ->label('Estado')
                                            ->badge()
                                            ->color(fn ($record) => $record->status_color)
                                            ->formatStateUsing(fn ($state) => match ($state) {
                                                'active' => 'Activo ✅',
                                                'trial' => 'En Prueba 🧪',
                                                'suspended' => 'Suspendido ⚠️',
                                                'expired' => 'Expirado ❌',
                                                'archived' => 'Archivado 📦',
                                                'inactive' => 'Inactivo 💤',
                                                default => 'Desconocido ❓',
                                            }),
                                    ])
                                    ->columnSpan(3),
                            ]),

                        // Contact & Access Information
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('domain')
                                    ->label('Dominio')
                                    ->copyable()
                                    ->copyMessage('Dominio copiado')
                                    ->url(fn ($record) => "https://{$record->domain}.emporiodigital.test")
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-link'),

                                Components\TextEntry::make('contact_email')
                                    ->label('Email de Contacto')
                                    ->copyable()
                                    ->copyMessage('Email copiado')
                                    ->icon('heroicon-o-envelope'),

                                Components\TextEntry::make('contact_name')
                                    ->label('Contacto Principal')
                                    ->icon('heroicon-o-user'),

                                Components\TextEntry::make('phone')
                                    ->label('Teléfono')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('No configurado'),
                            ]),

                        // Subscription Information
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('activeSubscription.plan.name')
                                    ->label('Plan Actual')
                                    ->badge()
                                    ->default('Sin Plan')
                                    ->color(fn ($record) => $record->activeSubscription ? 'success' : 'gray'),

                                Components\TextEntry::make('activeSubscription.billing_cycle')
                                    ->label('Ciclo de Facturación')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'monthly' => 'Mensual 📅',
                                        'yearly' => 'Anual 📆',
                                        default => $state,
                                    })
                                    ->placeholder('N/A'),

                                Components\TextEntry::make('activeSubscription.ends_at')
                                    ->label('Próximo Vencimiento')
                                    ->date('d/m/Y')
                                    ->placeholder('N/A')
                                    ->color(function ($record) {
                                        if (! $record->activeSubscription || ! $record->activeSubscription->ends_at) {
                                            return 'gray';
                                        }
                                        $days = now()->diffInDays($record->activeSubscription->ends_at, false);
                                        if ($days < 0) {
                                            return 'danger';
                                        }
                                        if ($days <= 7) {
                                            return 'warning';
                                        }

                                        return 'success';
                                    })
                                    ->formatStateUsing(function ($record) {
                                        if (! $record->activeSubscription || ! $record->activeSubscription->ends_at) {
                                            return null;
                                        }
                                        $diff = now()->diff($record->activeSubscription->ends_at);
                                        if ($diff->days > 0) {
                                            return "{$diff->days} días restantes";
                                        }

                                        return 'Vencido';
                                    }),
                            ]),
                    ])
                    ->columns(1),

                // MODULES SECTION - Tenant Modules Summary
                Components\Section::make('Módulos Activos')
                    ->description('Módulos y add-ons instalados para este tenant')
                    ->icon('heroicon-o-cube')
                    ->collapsible(false)
                    ->extraAttributes(['class' => 'ring-2 ring-blue-200 bg-blue-50/30 rounded-lg'])
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                // Module Count
                                Components\TextEntry::make('active_modules_count')
                                    ->label('Módulos Activos')
                                    ->formatStateUsing(fn ($record) => $record->activeModules()->count())
                                    ->icon('heroicon-o-puzzle-piece')
                                    ->size('lg')
                                    ->color('success'),

                                Components\TextEntry::make('modules_monthly_cost')
                                    ->label('Costo Mensual')
                                    ->formatStateUsing(fn ($record) => '$'.number_format($record->getModulesMonthlyCost(), 2))
                                    ->icon('heroicon-o-banknotes')
                                    ->color('warning'),

                                // Expiring Soon
                                Components\TextEntry::make('expiring_modules_count')
                                    ->label('Expiran en 7 días')
                                    ->formatStateUsing(fn ($record) => $record->getModulesExpiringSoon(7)->count())
                                    ->icon('heroicon-o-clock')
                                    ->color(fn ($record) => $record->getModulesExpiringSoon(7)->count() > 0 ? 'danger' : 'success'),

                                // Violations
                                Components\TextEntry::make('violations_count')
                                    ->label('Violaciones')
                                    ->formatStateUsing(fn ($record) => count($record->checkModuleLimits()))
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->color(fn ($record) => count($record->checkModuleLimits()) > 0 ? 'danger' : 'success'),
                            ]),

                        // Active Modules List
                        Components\RepeatableEntry::make('active_modules')
                            ->label('')
                            ->schema([
                                Components\Grid::make(4)
                                    ->schema([
                                        Components\TextEntry::make('name')
                                            ->label('Módulo')
                                            ->formatStateUsing(fn ($record) => $record['name']),

                                        Components\TextEntry::make('category')
                                            ->label('Categoría')
                                            ->formatStateUsing(fn ($record) => $record['category']),

                                        Components\TextEntry::make('status')
                                            ->label('Estado')
                                            ->badge()
                                            ->color('success'),

                                        Components\TextEntry::make('monthly_cost')
                                            ->label('Costo Mensual')
                                            ->formatStateUsing(fn ($record) => '$'.number_format($record['monthly_cost'], 2)),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->state(fn ($record) => $record->activeModules()
                                ->map(function ($module) use ($record) {
                                    $tenantModule = $record->getTenantModule($module->slug);

                                    return [
                                        'name' => $module->name,
                                        'category' => $module->getDisplayCategory(),
                                        'status' => 'Activo',
                                        'monthly_cost' => $tenantModule ? $tenantModule->getMonthlyCost() : 0,
                                    ];
                                })
                                ->toArray()
                            )
                            ->visible(fn ($record) => $record->activeModules()->isNotEmpty()),
                    ]),

                // STATISTICS SECTION - Business Metrics
                Components\Section::make('Métricas de Negocio')
                    ->description('Estadísticas clave del tenant')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                // Storage Metrics
                                Components\Section::make('Almacenamiento')
                                    ->schema([
                                        Components\TextEntry::make('storage_used')
                                            ->label('Espacio Usado')
                                            ->formatStateUsing(fn ($record) => self::getTenantStorageUsage(fn () => $record->database))
                                            ->icon('heroicon-o-server')
                                            ->size('lg')
                                            ->color('primary'),

                                        Components\TextEntry::make('file_count')
                                            ->label('Archivos Subidos')
                                            ->formatStateUsing(fn ($record) => self::getTenantFileCount(fn () => $record->id))
                                            ->icon('heroicon-o-document'),
                                    ])
                                    ->compact(),

                                // User Metrics
                                Components\Section::make('Usuarios')
                                    ->schema([
                                        Components\TextEntry::make('user_count')
                                            ->label('Total Usuarios')
                                            ->formatStateUsing(fn ($record) => self::getTenantUserCount(fn () => $record->id))
                                            ->icon('heroicon-o-users')
                                            ->size('lg')
                                            ->color('success'),

                                        Components\TextEntry::make('last_active')
                                            ->label('Última Actividad')
                                            ->formatStateUsing(fn ($record) => self::getTenantLastActivity(fn () => $record->id))
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('Sin actividad'),
                                    ])
                                    ->compact(),

                                // Business Metrics
                                Components\Section::make('Operaciones')
                                    ->schema([
                                        Components\TextEntry::make('product_count')
                                            ->label('Productos')
                                            ->formatStateUsing(fn ($record) => self::getTenantProductCount(fn () => $record->database))
                                            ->icon('heroicon-o-cube')
                                            ->size('lg')
                                            ->color('info'),

                                        Components\TextEntry::make('sales_count')
                                            ->label('Ventas Totales')
                                            ->formatStateUsing(fn ($record) => self::getTenantSalesCount(fn () => $record->database))
                                            ->icon('heroicon-o-currency-dollar'),
                                    ])
                                    ->compact(),

                                // Performance Metrics
                                Components\Section::make('Rendimiento')
                                    ->schema([
                                        Components\TextEntry::make('health_score')
                                            ->label('Salud del Sistema')
                                            ->formatStateUsing(fn ($record) => self::calculateTenantHealthScore(fn () => $record))
                                            ->icon('heroicon-o-heart')
                                            ->size('lg')
                                            ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger')),

                                        Components\TextEntry::make('api_calls_today')
                                            ->label('Llamadas API (Hoy)')
                                            ->formatStateUsing(fn ($record) => self::getTenantApiCallsToday(fn () => $record->id))
                                            ->icon('heroicon-o-chart-bar'),
                                    ])
                                    ->compact(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),

                // ACTIVITY SECTION
                Components\Section::make('Actividad Reciente')
                    ->description('Últimas acciones del tenant')
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes(['class' => 'opacity-70'])
                    ->schema([
                        Components\RepeatableEntry::make('recentActivities')
                            ->label('')
                            ->schema([
                                Components\Grid::make(4)
                                    ->schema([
                                        Components\TextEntry::make('created_at')
                                            ->label('Fecha')
                                            ->dateTime('d/m/Y H:i')
                                            ->size('sm'),

                                        Components\TextEntry::make('user.name')
                                            ->label('Usuario')
                                            ->formatStateUsing(fn ($state) => $state ?? 'Sistema')
                                            ->size('sm'),

                                        Components\TextEntry::make('action')
                                            ->label('Acción')
                                            ->badge()
                                            ->color(fn ($record) => $record->action_color)
                                            ->formatStateUsing(fn ($record) => $record->action_label)
                                            ->size('sm'),

                                        Components\TextEntry::make('description')
                                            ->label('Descripción')
                                            ->size('sm')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->hidden(fn ($record) => ! $record->recentActivities()->exists()),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('name')
                    ->searchable()
                    ->label('Nombre')
                    ->sortable()
                    ->grow(true),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Activo',
                        'trial' => 'Prueba',
                        'suspended' => 'Suspendido',
                        'expired' => 'Expirado',
                        'archived' => 'Archivado',
                        'inactive' => 'Inactivo',
                        default => 'Desconocido',
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'suspended' => 'warning',
                        'expired' => 'danger',
                        'archived' => 'gray',
                        'inactive' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('activeSubscription.plan.name')
                    ->label('Plan')
                    ->badge()
                    ->default('Sin Plan')
                    ->color(fn ($record) => $record->activeSubscription ? 'primary' : 'gray')
                    ->description(fn ($record) => $record->activeSubscription
                        ? ucfirst($record->activeSubscription->billing_cycle)
                        : null)
                    ->sortable(),

                TextColumn::make('user_count')
                    ->label('Usuarios')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        try {
                            return $record->users()->count();
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->tooltip('Total de usuarios registrados')
                    ->sortable(),

                TextColumn::make('health_score')
                    ->label('Health Score')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $score = self::calculateTenantHealthScore(function () use ($record) {
                            return $record;
                        });
                        if ($score >= 80) {
                            return '🟢 '.$score;
                        } elseif ($score >= 60) {
                            return '🟡 '.$score;
                        } else {
                            return '🔴 '.$score;
                        }
                    })
                    ->color(function ($record) {
                        $score = self::calculateTenantHealthScore(function () use ($record) {
                            return $record;
                        });
                        if ($score >= 80) {
                            return 'success';
                        }
                        if ($score >= 60) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->tooltip(function ($record) {
                        return self::getHealthScoreTooltip($record);
                    })
                    ->action(
                        function ($record) {
                            return Tables\Actions\Action::make('view_health_details')
                                ->label('Ver análisis de salud')
                                ->icon('heroicon-o-heart')
                                ->modalHeading('Análisis de Salud del Tenant')
                                ->modalDescription(function ($record) {
                                    $score = self::calculateTenantHealthScore(function () use ($record) {
                                        return $record;
                                    });
                                    $statusText = $score >= 80 ? 'Saludable' : ($score >= 60 ? 'Advertencia' : 'Crítico');

                                    return "Tenant: {$record->name} | Score: {$score}/100 | Estado: {$statusText}";
                                })
                                ->action(function ($record) {
                                    // Esta acción puede dispatchear un evento o simplemente mostrar el modal
                                    return true;
                                });
                        }
                    )
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->tooltip('Fecha de creación del tenant'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->label('Estado de Suscripción')
                    ->options([
                        'active' => 'Activo',
                        'expired' => 'Expirado',
                        'cancelled' => 'Cancelado',
                        'suspended' => 'Suspendido',
                        'no_subscription' => 'Sin Suscripción',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        $status = $data['value'];

                        if ($status === 'no_subscription') {
                            return $query->doesntHave('activeSubscription');
                        }

                        return $query->whereHas('activeSubscription', function ($q) use ($status) {
                            if ($status === 'expired') {
                                $q->where(function ($sq) {
                                    $sq->where('status', 'expired')
                                        ->orWhere(function ($ssq) {
                                            $ssq->where('status', 'active')
                                                ->where('ends_at', '<', now());
                                        });
                                });
                            } else {
                                $q->where('status', $status);
                            }
                        });
                    }),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Vence en 7 días')
                    ->query(fn (Builder $query) => $query->whereHas('activeSubscription', function ($q) {
                        $q->where('status', 'active')
                            ->whereBetween('ends_at', [now(), now()->addDays(7)]);
                    })
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('on_trial')
                    ->label('En Trial')
                    ->query(fn (Builder $query) => $query->whereHas('activeSubscription', function ($q) {
                        $q->where('status', 'active')
                            ->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>', now());
                    })
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('plan')
                    ->label('Plan')
                    ->options(\App\Models\SubscriptionPlan::where('is_active', true)->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('activeSubscription', function ($q) use ($data) {
                            $q->where('subscription_plan_id', $data['value']);
                        });
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->label('Ciclo de Facturación')
                    ->options([
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('activeSubscription', function ($q) use ($data) {
                            $q->where('billing_cycle', $data['value']);
                        });
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // VIEW ACTION - Primary Action (Navigate to Details Page)
                Tables\Actions\Action::make('view')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('Ver detalles del tenant')
                    ->url(fn ($record) => TenantResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (): bool => auth('superadmin')->user()?->can('admin.tenants.view') ?? false),

                // EDIT ACTION
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Editar tenant')
                    ->visible(fn (): bool => auth('superadmin')->user()?->can('admin.tenants.update') ?? false),

                // ADVANCED ACTIONS DROPDOWN
                Tables\Actions\ActionGroup::make([
                    // TENANT DASHBOARD ACCESS
                    Action::make('access_dashboard')
                        ->label('Acceder al Dashboard')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn ($record) => "https://{$record->domain}.emporiodigital.test/app")
                        ->openUrlInNewTab()
                        ->color('success')
                        ->tooltip('Abrir dashboard del tenant')
                        ->visible(fn (): bool => auth('superadmin')->user()?->can('admin.tenants.view') ?? false),

                    // UNLOCK USER ACCOUNTS
                    Action::make('unlock_accounts')
                        ->label('Desbloquear Cuentas')
                        ->icon('heroicon-o-lock-open')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Desbloquear Cuentas de Usuario')
                        ->modalDescription('Esta acción eliminará todos los bloqueos de seguridad 2FA de los usuarios de este tenant. ¿Estás seguro que deseas continuar?')
                        ->modalSubmitActionLabel('Sí, Desbloquear')
                        ->modalCancelActionLabel('Cancelar')
                        ->tooltip('Desbloquear cuentas 2FA')
                        ->action(function ($record) {
                            self::unlockTenantAccounts($record);
                        })
                        ->visible(fn (): bool => auth('superadmin')->user()?->can('admin.tenants.update') ?? false)
                        ->badge(function ($record) {
                            $lockedCount = self::getLockedAccountsCount($record);

                            return $lockedCount > 0 ? $lockedCount : null;
                        })
                        ->color(fn ($record) => self::getLockedAccountsCount($record) > 0 ? 'danger' : 'gray'),

                    // RESEND WELCOME EMAIL
                    Action::make('resend_welcome')
                        ->label('Reenviar Bienvenida')
                        ->icon('heroicon-o-envelope')
                        ->tooltip('Reenviar email de bienvenida')
                        ->requiresConfirmation()
                        ->modalHeading('Reenviar Email de Bienvenida')
                        ->modalDescription('Se generará una nueva contraseña temporal y se enviará el email de bienvenida al usuario.')
                        ->visible(fn () => auth('superadmin')->user()?->can('admin.tenants.update') ?? false)
                        ->action(function ($record) {
                            $user = User::where('email', $record->contact_email)->first();

                            if (! $user) {
                                Notification::make()
                                    ->title('Usuario no encontrado')
                                    ->body("No se encontró un usuario con el email {$record->contact_email}")
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Generate secure hexadecimal password (20 chars, email-safe)
                            $newPassword = bin2hex(random_bytes(10));

                            // Update user with new password and force password change
                            $user->update([
                                'password' => Hash::make($newPassword),
                                'must_change_password' => true,
                            ]);

                            // Send welcome email with new credentials
                            Mail::to($user->email)->send(new WelcomeNewTenant($user, $record, $newPassword));

                            Notification::make()
                                ->title('Email Reenviado')
                                ->body("Se ha enviado el email de bienvenida a {$user->email} con una nueva contraseña temporal.")
                                ->success()
                                ->send();
                        }),

                    // BACKUP ACTION
                    Action::make('backup')
                        ->label('Backup Manual')
                        ->icon('heroicon-o-circle-stack')
                        ->color('info')
                        ->tooltip('Ejecutar backup manual')
                        ->requiresConfirmation()
                        ->modalHeading('Ejecutar Backup Manual')
                        ->modalDescription(fn ($record) => "Se creará un backup de la base de datos '{$record->database}' de forma inmediata.")
                        ->modalSubmitActionLabel('Ejecutar Backup')
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false)
                        ->action(function ($record) {
                            $backupService = app(\App\Services\TenantBackupService::class);

                            Notification::make()
                                ->title('Backup Iniciado')
                                ->body("Ejecutando backup de {$record->name}...")
                                ->info()
                                ->send();

                            // Execute backup
                            $result = $backupService->backupDatabase($record->database, $record->id, 'manual');

                            if ($result['success']) {
                                Notification::make()
                                    ->title('Backup Exitoso')
                                    ->body("Backup completado: {$record->database} (".round($result['file_size'] / 1024 / 1024, 2).' MB)')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Backup Fallido')
                                    ->body("Error: {$result['error']}")
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // MAINTENANCE MODE
                    Action::make('maintenance_mode')
                        ->label('Modo Mantenimiento')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('warning')
                        ->tooltip('Activar modo mantenimiento')
                        ->requiresConfirmation()
                        ->modalHeading('Activar Modo Mantenimiento')
                        ->modalDescription('La tienda será temporalmente inaccesible para los usuarios.')
                        ->modalSubmitActionLabel('Activar Mantenimiento')
                        ->visible(fn () => auth('superadmin')->user()?->can('admin.tenants.update') ?? false)
                        ->action(function ($record) {
                            $record->update(['status' => 'suspended']);
                            Notification::make()
                                ->title('Modo Mantenimiento Activado')
                                ->body("La tienda {$record->name} está ahora en mantenimiento.")
                                ->warning()
                                ->send();
                        }),

                    // HIGH-RISK ACTIONS - High Friction Security
                    Action::make('deactivate_tenant')
                        ->label('Desactivar Tienda')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->tooltip('Desactivar tienda (riesgoso)')
                        ->requiresConfirmation()
                        ->modalHeading('⚠️ Confirmar Desactivación de Tienda')
                        ->modalDescription(function ($record) {
                            return "**ESTA ACCIÓN AFECTARÁ EL ACCESO DEL CLIENTE**\n\n".
                                   "La tienda \"{$record->name}\" será desactivada y los usuarios no podrán acceder.\n\n".
                                   "**Consecuencias:**\n".
                                   "• Todos los usuarios perderán acceso al sistema\n".
                                   "• Las operaciones comerciales se detendrán\n".
                                   "• Reactivación requiere aprobación manual\n".
                                   '• No se eliminarán datos';
                        })
                        ->modalSubmitActionLabel('Desactivar Tienda')
                        ->action(function ($record, array $data) {
                            // Verify tenant name matches exactly
                            if ($data['confirm_tenant_name'] !== $record->name) {
                                Notification::make()
                                    ->title('Error de Confirmación')
                                    ->body('El nombre de la tienda no coincide. Desactivación cancelada.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Verify admin password
                            $admin = auth('superadmin')->user();
                            if (! Hash::check($data['admin_password'], $admin->password)) {
                                Notification::make()
                                    ->title('Error de Autenticación')
                                    ->body('La contraseña de administrador es incorrecta.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Deactivate tenant
                            $record->deactivate();

                            // Log the action for audit
                            activity()
                                ->causedBy($admin)
                                ->performedOn($record)
                                ->withProperties([
                                    'action' => 'deactivate',
                                    'reason' => $data['reason'] ?? 'No especificado',
                                    'ip' => request()->ip(),
                                ])
                                ->log('Tenant desactivado con confirmación de dos factores');

                            Notification::make()
                                ->title('Tienda Desactivada')
                                ->body("La tienda {$record->name} ha sido desactivada exitosamente.")
                                ->warning()
                                ->send();
                        })
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reason')
                                ->label('Motivo de la desactivación')
                                ->required()
                                ->rows(3)
                                ->helperText('Este motivo quedará registrado en el auditoría.'),
                            \Filament\Forms\Components\TextInput::make('confirm_tenant_name')
                                ->label('Confirmar nombre de la tienda')
                                ->required()
                                ->placeholder(function ($record) {
                                    return 'Escribe: '.($record->name ?? '[nombre de la tienda]');
                                })
                                ->helperText('Debes escribir el nombre exacto de la tienda para confirmar.'),
                            \Filament\Forms\Components\TextInput::make('admin_password')
                                ->label('Contraseña de Administrador')
                                ->required()
                                ->password()
                                ->revealable()
                                ->helperText('Confirma tu identidad con tu contraseña de administrador.'),
                        ])
                        ->visible(fn ($record): bool => $record->isActive() && (auth('superadmin')->user()?->can('admin.tenants.update') ?? false)),

                    Action::make('archive_tenant')
                        ->label('Archivar Tienda')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('danger')
                        ->tooltip('Archivar tienda (casi irreversible)')
                        ->requiresConfirmation()
                        ->modalHeading('🔒 ARCHIVAR TIENDA - ACCIÓN IRREVERSIBLE')
                        ->modalDescription(function ($record) {
                            return "**PELIGRO: Esta acción es casi permanente**\n\n".
                                   "Archivar la tienda \"{$record->name}\" significa:\n\n".
                                   "• La tienda será eliminada del listado activo\n".
                                   "• El acceso quedará completamente bloqueado\n".
                                   "• Los datos serán conservados solo para auditoría\n".
                                   "• Reactivación requerirá intervención técnica\n".
                                   '• Esta acción no puede deshacerse fácilmente';
                        })
                        ->modalSubmitActionLabel('Entiendo, Archivar Tienda')
                        ->action(function ($record, array $data) {
                            // Additional security: require OTP code
                            $expectedCode = 'ARCHIVE'.strtoupper(substr($record->name, 0, 4));
                            if (empty($data['otp_code']) || $data['otp_code'] !== $expectedCode) {
                                Notification::make()
                                    ->title('Código de Verificación Incorrecto')
                                    ->body('El código OTP no es válido. Archivado cancelado.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Archive tenant
                            $record->archive();

                            // Log the critical action
                            activity()
                                ->causedBy(auth('superadmin')->user())
                                ->performedOn($record)
                                ->withProperties([
                                    'action' => 'archive',
                                    'ip' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'confirmation_code' => 'OTP_VERIFIED',
                                ])
                                ->log('Tenant archivado con verificación OTP');

                            Notification::make()
                                ->title('Tienda Archivada')
                                ->body("La tienda {$record->name} ha sido archivada. Esta acción ha sido registrada.")
                                ->danger()
                                ->duration(10000)
                                ->send();
                        })
                        ->form([
                            \Filament\Forms\Components\TextInput::make('otp_code')
                                ->label('Código de Verificación (OTP)')
                                ->required()
                                ->placeholder(function ($record) {
                                    $name = $record->name ?? 'XXXX';

                                    return 'Escribe: ARCHIVE'.strtoupper(substr($name, 0, 4));
                                })
                                ->helperText('Para seguridad adicional, ingresa el código exacto que se muestra en la advertencia.'),
                            \Filament\Forms\Components\Checkbox::make('understand_consequences')
                                ->label('Entiendo que esta acción es casi irreversible y afectará el acceso del cliente.')
                                ->required(),
                            \Filament\Forms\Components\Checkbox::make('confirm_backup')
                                ->label('Confirmo que existe un backup reciente de esta tienda.')
                                ->required(),
                        ])
                        ->visible(fn ($record): bool => ! $record->trashed() && (auth('superadmin')->user()?->is_super_admin ?? false)),

                    Tables\Actions\RestoreAction::make()
                        ->tooltip('Restaurar tenant archivado')
                        ->visible(fn ($record): bool => $record->trashed() && (auth('superadmin')->user()?->can('admin.tenants.restore') ?? false)),

                    Tables\Actions\ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Permanentemente')
                        ->modalDescription('Esta acción no se puede deshacer. Se eliminarán permanentemente todos los datos del tenant.')
                        ->modalSubmitActionLabel('Sí, Eliminar Permanentemente')
                        ->tooltip('Eliminar permanentemente (destructivo)')
                        ->visible(fn ($record): bool => $record->trashed() && (auth('superadmin')->user()?->can('admin.tenants.force-delete') ?? false)),
                ])
                    ->label('')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip('Acciones avanzadas'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('')
                        ->icon('heroicon-o-trash')
                        ->tooltip('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar Eliminación')
                        ->modalDescription('Esta acción eliminará permanentemente todos los datos de los tenants seleccionados. No se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, Eliminar')
                        ->modalCancelActionLabel('Cancelar')
                        ->visible(fn (): bool => auth('superadmin')->user()?->can('admin.tenants.delete') ?? false),
                    Tables\Actions\BulkAction::make('backup_selected')
                        ->label('')
                        ->icon('heroicon-o-circle-stack')
                        ->color('info')
                        ->tooltip('Backup de seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Ejecutar Backup de Tenants Seleccionados')
                        ->modalDescription(fn ($records) => 'Se crearán backups de '.$records->count().' tenant(s) seleccionado(s).')
                        ->modalSubmitActionLabel('Ejecutar Backups')
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false)
                        ->action(function ($records) {
                            $backupService = app(\App\Services\TenantBackupService::class);
                            $successCount = 0;
                            $failedCount = 0;

                            foreach ($records as $tenant) {
                                $result = $backupService->backupDatabase($tenant->database, $tenant->id, 'manual');

                                if ($result['success']) {
                                    $successCount++;
                                } else {
                                    $failedCount++;
                                }
                            }

                            if ($failedCount === 0) {
                                Notification::make()
                                    ->title('Backups Completados')
                                    ->body("Se completaron exitosamente {$successCount} backup(s)")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Backups Completados con Errores')
                                    ->body("Exitosos: {$successCount} | Fallidos: {$failedCount}")
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('status', '!=', \App\Models\Tenant::STATUS_ARCHIVED) // Exclude archived tenants
            ->whereNull('deleted_at'); // Only show non-deleted tenants
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.tenants.view', 'superadmin') ?? false);
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.tenants.create', 'superadmin') ?? false);
    }

    public static function canEdit($record): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.tenants.update', 'superadmin') ?? false);
    }

    public static function canDelete($record): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.tenants.delete', 'superadmin') ?? false);
    }

    /**
     * TENANT STATISTICS METHODS
     * These methods provide real-time metrics for tenant profiles
     */

    /**
     * Get tenant storage usage in human readable format
     */
    public static function getTenantStorageUsage($databaseCallback): string
    {
        try {
            $database = $databaseCallback();
            if (! $database) {
                return 'N/A';
            }

            // Cache for 5 minutes to improve performance
            $cacheKey = "tenant_storage_{$database}";

            return Cache::remember($cacheKey, 300, function () use ($database) {
                // Get database size from PostgreSQL
                $result = DB::select('
                    SELECT pg_size_pretty(pg_database_size(?)) as size
                    FROM pg_database WHERE datname = ?
                ', [$database, $database]);

                if (! empty($result)) {
                    return $result[0]->size ?? '0 MB';
                }

                // Fallback: check file storage
                $storagePath = storage_path("app/tenant-uploads/{$database}");
                if (is_dir($storagePath)) {
                    $size = 0;
                    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storagePath)) as $file) {
                        $size += $file->getSize();
                    }

                    return round($size / 1024 / 1024, 2).' MB';
                }

                return '0 MB';
            });
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    /**
     * Get tenant file count
     */
    public static function getTenantFileCount($tenantIdCallback): int
    {
        try {
            $tenantId = $tenantIdCallback();
            if (! $tenantId) {
                return 0;
            }

            $cacheKey = "tenant_files_{$tenantId}";

            return Cache::remember($cacheKey, 300, function () use ($tenantId) {
                $storagePath = storage_path("app/tenant-uploads/{$tenantId}");
                if (! is_dir($storagePath)) {
                    return 0;
                }

                $fileCount = 0;
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storagePath));
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileCount++;
                    }
                }

                return $fileCount;
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get tenant user count
     */
    public static function getTenantUserCount($tenantIdCallback): int
    {
        try {
            $tenantId = $tenantIdCallback();
            if (! $tenantId) {
                return 0;
            }

            $cacheKey = "tenant_users_{$tenantId}";

            return Cache::remember($cacheKey, 600, function () use ($tenantId) {
                return \App\Models\User::where('tenant_id', $tenantId)->count();
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get tenant last activity
     */
    public static function getTenantLastActivity($tenantIdCallback): string
    {
        try {
            $tenantId = $tenantIdCallback();
            if (! $tenantId) {
                return 'Sin datos';
            }

            $cacheKey = "tenant_activity_{$tenantId}";

            $lastActivity = Cache::remember($cacheKey, 300, function () use ($tenantId) {
                $activity = \App\Models\TenantActivity::where('tenant_id', $tenantId)
                    ->latest('created_at')
                    ->first();

                return $activity ? $activity->created_at : null;
            });

            if (! $lastActivity) {
                return 'Sin actividad';
            }

            $diff = $lastActivity->diffForHumans(now());

            if ($lastActivity->isToday()) {
                return "Hoy, {$lastActivity->format('H:i')}";
            } elseif ($lastActivity->isYesterday()) {
                return "Ayer, {$lastActivity->format('H:i')}";
            } else {
                return $lastActivity->format('d/m H:i');
            }
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    /**
     * Get tenant product count (requires tenant database access)
     */
    public static function getTenantProductCount($databaseCallback): int
    {
        try {
            $database = $databaseCallback();
            if (! $database) {
                return 0;
            }

            $cacheKey = "tenant_products_{$database}";

            return Cache::remember($cacheKey, 600, function () {
                // Switch to tenant connection temporarily
                $originalConnection = config('database.default');
                config(['database.default' => 'tenant']);

                try {
                    $count = \App\Modules\Inventory\Models\Product::count();
                    config(['database.default' => $originalConnection]);

                    return $count;
                } catch (\Exception $e) {
                    config(['database.default' => $originalConnection]);

                    return 0;
                }
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get tenant sales count (requires tenant database access)
     */
    public static function getTenantSalesCount($databaseCallback): int
    {
        try {
            $database = $databaseCallback();
            if (! $database) {
                return 0;
            }

            $cacheKey = "tenant_sales_{$database}";

            return Cache::remember($cacheKey, 600, function () {
                // Switch to tenant connection temporarily
                $originalConnection = config('database.default');
                config(['database.default' => 'tenant']);

                try {
                    $count = \App\Modules\POS\Models\Sale::count();
                    config(['database.default' => $originalConnection]);

                    return $count;
                } catch (\Exception $e) {
                    config(['database.default' => $originalConnection]);

                    return 0;
                }
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate tenant health score (0-100)
     */
    public static function calculateTenantHealthScore($tenantCallback): int
    {
        try {
            $tenant = $tenantCallback();
            if (! $tenant) {
                return 0;
            }

            $cacheKey = "tenant_health_{$tenant->id}";

            return Cache::remember($cacheKey, 900, function () use ($tenant) {
                $score = 100;

                // Check tenant status
                if ($tenant->status === 'suspended') {
                    $score -= 40;
                }
                if ($tenant->status === 'expired') {
                    $score -= 50;
                }
                if ($tenant->status === 'inactive') {
                    $score -= 30;
                }

                // Check subscription
                if ($tenant->activeSubscription) {
                    $daysUntilExpiry = now()->diffInDays($tenant->activeSubscription->ends_at, false);
                    if ($daysUntilExpiry < 0) {
                        $score -= 35;
                    } elseif ($daysUntilExpiry < 7) {
                        $score -= 15;
                    }
                } else {
                    $score -= 25;
                }

                // Check backup status
                $latestBackup = \App\Models\BackupLog::where('tenant_id', $tenant->id)
                    ->latest('created_at')
                    ->first();

                if (! $latestBackup) {
                    $score -= 20;
                } elseif ($latestBackup->status === 'failed') {
                    $score -= 15;
                } elseif ($latestBackup->created_at->diffInDays(now()) > 2) {
                    $score -= 10;
                }

                // Check recent activity
                $recentActivity = \App\Models\TenantActivity::where('tenant_id', $tenant->id)
                    ->where('created_at', '>', now()->subDays(7))
                    ->count();

                if ($recentActivity === 0) {
                    $score -= 15;
                } elseif ($recentActivity < 5) {
                    $score -= 5;
                }

                return max(0, min(100, $score));
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get tenant API calls today (placeholder for implementation)
     */
    public static function getTenantApiCallsToday($tenantIdCallback): int
    {
        try {
            $tenantId = $tenantIdCallback();
            if (! $tenantId) {
                return 0;
            }

            $cacheKey = "tenant_api_calls_{$tenantId}_".date('Y-m-d');

            return Cache::remember($cacheKey, 300, function () {
                // This would typically query a logs table or API usage tracking
                // For now, return a placeholder value
                return rand(50, 500);
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Unlock all 2FA locked accounts for a tenant
     */
    private static function unlockTenantAccounts($record): void
    {
        try {
            $tenant = $record;
            $unlockedCount = 0;

            // Get all users for this tenant
            $users = $tenant->users;

            foreach ($users as $user) {
                $lockoutKey = '2fa_lockout:'.$user->id;
                $attemptKey = '2fa_attempts:'.$user->id;

                // Clear 2FA lockout and attempts
                if (Cache::has($lockoutKey)) {
                    Cache::forget($lockoutKey);
                    $unlockedCount++;
                }

                if (Cache::has($attemptKey)) {
                    Cache::forget($attemptKey);
                }
            }

            // Log the action
            \Log::info('Tenant accounts unlocked by superadmin', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'unlocked_count' => $unlockedCount,
                'admin_id' => auth('superadmin')->id(),
                'admin_email' => auth('superadmin')->user()->email,
            ]);

            // Show success notification
            \Filament\Notifications\Notification::make()
                ->title('✅ Cuentas Desbloqueadas')
                ->body("Se han desbloqueado {$unlockedCount} cuentas del tenant {$tenant->name}.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Error unlocking tenant accounts', [
                'tenant_id' => $record->id,
                'error' => $e->getMessage(),
                'admin_id' => auth('superadmin')->id(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('❌ Error al Desbloquear')
                ->body('No se pudieron desbloquear las cuentas. Por favor intenta nuevamente.')
                ->danger()
                ->send();
        }
    }

    /**
     * Get count of locked accounts for a tenant
     */
    private static function getLockedAccountsCount($record): int
    {
        try {
            $tenant = $record;
            $lockedCount = 0;

            // Get all users for this tenant
            $users = $tenant->users;

            foreach ($users as $user) {
                $lockoutKey = '2fa_lockout:'.$user->id;
                if (Cache::has($lockoutKey)) {
                    $lockedCount++;
                }
            }

            return $lockedCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get detailed tooltip for health score
     */
    public static function getHealthScoreTooltip($record): string
    {
        try {
            $statsService = app(TenantStatsService::class);
            $health = $statsService->getTenantHealth($record);
            $score = self::calculateTenantHealthScore(function () use ($record) {
                return $record;
            });

            $tooltip = "**Puntuación: {$score}/100**\n\n";

            if (! empty($health['issues'])) {
                $tooltip .= "⚠️ **Issues detectados:**\n";
                foreach ($health['issues'] as $issue) {
                    $tooltip .= "• {$issue}\n";
                }
            } else {
                $tooltip .= "✅ Todo en orden\n";
            }

            // Add specific metrics
            try {
                $stats = $statsService->getTenantStats($record);

                $tooltip .= "\n**📊 Métricas clave:**\n";
                $tooltip .= '• Usuarios activos: '.($stats['active_users_count'] ?? 0)."\n";
                $tooltip .= '• Productos: '.($stats['products_count'] ?? 0)."\n";
                $tooltip .= '• Ventas del mes: '.($stats['sales_last_month'] ?? 0)."\n";

                // Last activity
                if (isset($stats['last_activity']) && $stats['last_activity']) {
                    $tooltip .= '• Última actividad: '.$stats['last_activity']->diffForHumans()."\n";
                }

                // Subscription status
                if ($record->activeSubscription) {
                    $daysUntilExpiry = now()->diffInDays($record->activeSubscription->ends_at, false);
                    if ($daysUntilExpiry < 0) {
                        $tooltip .= "• Suscripción: Expirada\n";
                    } elseif ($daysUntilExpiry < 7) {
                        $tooltip .= "• Suscripción: Expira en {$daysUntilExpiry} días\n";
                    } else {
                        $tooltip .= "• Suscripción: Activa\n";
                    }
                } else {
                    $tooltip .= "• Suscripción: No encontrada\n";
                }

            } catch (\Exception $e) {
                $tooltip .= "• Error al obtener métricas detalladas\n";
            }

            $tooltip .= "\n*Haz clic para ver detalles y recomendaciones*";

            return $tooltip;

        } catch (\Exception $e) {
            return 'Error al cargar información del health score';
        }
    }

    /**
     * Get modal content for health score details
     */
    public static function getHealthScoreModalContent($record): string
    {
        try {
            $statsService = app(TenantStatsService::class);
            $health = $statsService->getTenantHealth($record);
            $score = self::calculateTenantHealthScore(function () use ($record) {
                return $record;
            });
            $stats = $statsService->getTenantStats($record);

            // Calculate status
            $status = 'success';
            $statusText = 'Saludable';
            $statusIcon = '🟢';
            $statusColorClass = 'text-green-600';
            if ($score < 60) {
                $status = 'danger';
                $statusText = 'Crítico';
                $statusIcon = '🔴';
                $statusColorClass = 'text-red-600';
            } elseif ($score < 80) {
                $status = 'warning';
                $statusText = 'Advertencia';
                $statusIcon = '🟡';
                $statusColorClass = 'text-yellow-600';
            }

            $content = "
            <div class='space-y-6'>
                <!-- Header con Score y Status -->
                <div class='text-center bg-gray-50 rounded-lg p-6'>
                    <div class='text-6xl font-bold {$statusColorClass}'>
                        {$statusIcon} {$score}/100
                    </div>
                    <div class='text-xl font-semibold mt-2'>Estado: {$statusText}</div>
                    <div class='text-sm text-gray-600 mt-1'>Tenant: {$record->name}</div>
                </div>

                <!-- Métricas Detalladas -->
                <div class='grid grid-cols-2 md:grid-cols-4 gap-4'>
                    <div class='bg-blue-50 rounded-lg p-4 text-center'>
                        <div class='text-2xl font-bold text-blue-600'>".($stats['users_count'] ?? 0)."</div>
                        <div class='text-sm text-gray-600'>Usuarios Totales</div>
                    </div>
                    <div class='bg-green-50 rounded-lg p-4 text-center'>
                        <div class='text-2xl font-bold text-green-600'>".($stats['active_users_count'] ?? 0)."</div>
                        <div class='text-sm text-gray-600'>Usuarios Activos</div>
                    </div>
                    <div class='bg-purple-50 rounded-lg p-4 text-center'>
                        <div class='text-2xl font-bold text-purple-600'>".($stats['products_count'] ?? 0)."</div>
                        <div class='text-sm text-gray-600'>Productos</div>
                    </div>
                    <div class='bg-orange-50 rounded-lg p-4 text-center'>
                        <div class='text-2xl font-bold text-orange-600'>".($stats['sales_count'] ?? 0)."</div>
                        <div class='text-sm text-gray-600'>Ventas Totales</div>
                    </div>
                </div>

                <!-- Problemas Detectados -->
                <div class='space-y-3'>";

            if (! empty($health['issues'])) {
                $content .= "
                    <h3 class='text-lg font-semibold text-gray-900 flex items-center'>
                        <span class='text-red-500 mr-2'>⚠️</span>
                        Problemas Detectados
                    </h3>
                    <div class='bg-red-50 border border-red-200 rounded-lg p-4 space-y-2'>";

                foreach ($health['issues'] as $issue) {
                    $content .= "
                        <div class='flex items-start'>
                            <span class='text-red-500 mr-2'>•</span>
                            <span class='text-gray-700'>{$issue}</span>
                        </div>";
                }

                $content .= '
                    </div>';
            } else {
                $content .= "
                    <div class='bg-green-50 border border-green-200 rounded-lg p-4'>
                        <div class='flex items-center text-green-800'>
                            <span class='text-2xl mr-3'>✅</span>
                            <span class='font-medium'>¡Todo en orden! No se detectaron problemas críticos.</span>
                        </div>
                    </div>";
            }

            // Recomendaciones
            $content .= "
                </div>

                <!-- Recomendaciones -->
                <div class='space-y-3'>
                    <h3 class='text-lg font-semibold text-gray-900 flex items-center'>
                        <span class='text-blue-500 mr-2'>💡</span>
                        Recomendaciones para Mejorar
                    </h3>
                    <div class='bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3'>";

            $recommendations = self::getHealthScoreRecommendations($record, $score, $stats, $health);

            foreach ($recommendations as $recommendation) {
                $priority = $recommendation['priority'] ?? 'medium';
                $priorityColor = $priority === 'high' ? 'red' : ($priority === 'medium' ? 'yellow' : 'green');
                $priorityIcon = $priority === 'high' ? '🔴' : ($priority === 'medium' ? '🟡' : '🟢');

                $content .= "
                    <div class='flex items-start space-x-3'>
                        <span class='mt-1'>{$priorityIcon}</span>
                        <div class='flex-1'>
                            <div class='font-medium text-gray-800'>{$recommendation['title']}</div>
                            <div class='text-sm text-gray-600 mt-1'>{$recommendation['description']}</div>";

                if (! empty($recommendation['action'])) {
                    $content .= "
                        <div class='mt-2'>
                            <span class='text-xs bg-{$priorityColor}-100 text-{$priorityColor}-800 px-2 py-1 rounded-full'>
                                {$recommendation['action']}
                            </span>
                        </div>";
                }

                $content .= '
                        </div>
                    </div>';
            }

            $content .= "
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                    <div class='bg-gray-50 rounded-lg p-4'>
                        <h4 class='font-medium text-gray-900 mb-2'>📈 Actividad Reciente</h4>
                        <div class='text-sm text-gray-600 space-y-1'>";

            if (isset($stats['last_activity']) && $stats['last_activity']) {
                $content .= '
                    <div>• Última actividad: '.$stats['last_activity']->diffForHumans().'</div>
                    <div>• Actividad últimos 7 días: '.($stats['activities_last_week'] ?? 0).' eventos</div>';
            } else {
                $content .= '
                    <div>• Sin actividad registrada</div>';
            }

            $content .= "
                        </div>
                    </div>

                    <div class='bg-gray-50 rounded-lg p-4'>
                        <h4 class='font-medium text-gray-900 mb-2'>💾 Uso de Almacenamiento</h4>
                        <div class='text-sm text-gray-600 space-y-1'>
                            <div>• Base de datos: ".round($stats['database_size'] ?? 0, 2).' MB</div>
                            <div>• Archivos: '.round(($stats['storage_usage']['files_size_mb'] ?? 0), 2).' MB</div>
                            <div>• Total: '.round(($stats['storage_usage']['total_size_mb'] ?? 0), 2).' MB</div>
                        </div>
                    </div>
                </div>
            </div>';

            return $content;

        } catch (\Exception $e) {
            return "
            <div class='bg-red-50 border border-red-200 rounded-lg p-6 text-center'>
                <div class='text-red-600 text-xl mb-2'>❌</div>
                <div class='text-red-800 font-medium'>Error al cargar información de salud</div>
                <div class='text-red-600 text-sm mt-2'>Por favor, intenta nuevamente más tarde.</div>
            </div>";
        }
    }

    /**
     * Get health score recommendations based on tenant status
     */
    private static function getHealthScoreRecommendations($record, int $score, array $stats, array $health): array
    {
        $recommendations = [];

        // Critical recommendations (score < 60)
        if ($score < 60) {
            // Subscription issues
            if (! $record->activeSubscription) {
                $recommendations[] = [
                    'title' => 'Configurar Suscripción',
                    'description' => 'Este tenant no tiene una suscripción activa. Esto afecta el acceso al sistema y servicios.',
                    'action' => 'URGENTE',
                    'priority' => 'high',
                ];
            } elseif ($record->activeSubscription->ends_at->isPast()) {
                $recommendations[] = [
                    'title' => 'Renovar Suscripción Expirada',
                    'description' => 'La suscripción ha expirado. El tenant puede perder acceso al sistema.',
                    'action' => 'URGENTE',
                    'priority' => 'high',
                ];
            }

            // Activity issues
            if (($stats['active_users_count'] ?? 0) === 0) {
                $recommendations[] = [
                    'title' => 'Activar Usuarios',
                    'description' => 'No hay usuarios activos. Revisa que los usuarios puedan acceder al sistema.',
                    'action' => 'ALTA PRIORIDAD',
                    'priority' => 'high',
                ];
            }

            // Database connectivity
            if (isset($health['status']) && $health['status'] === 'critical') {
                $recommendations[] = [
                    'title' => 'Problemas de Conexión a Base de Datos',
                    'description' => 'No se puede conectar a la base de datos del tenant. Esto es un problema crítico.',
                    'action' => 'URGENTE',
                    'priority' => 'high',
                ];
            }
        }

        // Warning recommendations (score < 80)
        if ($score < 80) {
            // Low activity
            if (isset($stats['last_activity']) && $stats['last_activity'] && $stats['last_activity']->diffInDays() > 30) {
                $recommendations[] = [
                    'title' => 'Falta de Actividad Reciente',
                    'description' => 'No hay actividad desde hace más de 30 días. Considera contactar al cliente.',
                    'action' => 'REVISAR',
                    'priority' => 'medium',
                ];
            }

            // Low product count
            if (($stats['products_count'] ?? 0) < 10) {
                $recommendations[] = [
                    'title' => 'Pocos Productos Registrados',
                    'description' => 'El tenant tiene menos de 10 productos. Esto podría indicar configuración incompleta.',
                    'action' => 'SUGERENCIA',
                    'priority' => 'medium',
                ];
            }

            // Subscription expiring soon
            if ($record->activeSubscription && $record->activeSubscription->ends_at->diffInDays() < 7 && $record->activeSubscription->ends_at->isFuture()) {
                $recommendations[] = [
                    'title' => 'Suscripción por Expirar',
                    'description' => 'La suscripción expira en menos de 7 días. Notifica al cliente.',
                    'action' => 'PRÓXIMO A EXPIRAR',
                    'priority' => 'medium',
                ];
            }

            // Backup issues
            try {
                $latestBackup = \App\Models\BackupLog::where('tenant_id', $record->id)
                    ->latest('created_at')
                    ->first();

                if (! $latestBackup) {
                    $recommendations[] = [
                        'title' => 'Sin Backups Recientes',
                        'description' => 'No hay backups registrados. Los datos podrían estar en riesgo.',
                        'action' => 'RIESGO DE DATOS',
                        'priority' => 'high',
                    ];
                } elseif ($latestBackup->status === 'failed') {
                    $recommendations[] = [
                        'title' => 'Backups Fallidos',
                        'description' => 'El último backup falló. Revisa la configuración de backups.',
                        'action' => 'ERROR CRÍTICO',
                        'priority' => 'high',
                    ];
                } elseif ($latestBackup->created_at->diffInDays() > 2) {
                    $recommendations[] = [
                        'title' => 'Backups Antiguos',
                        'description' => 'El último backup tiene más de 2 días. Verifica el sistema de backups.',
                        'action' => 'MANTENIMIENTO',
                        'priority' => 'medium',
                    ];
                }
            } catch (\Exception $e) {
                // Skip backup check if there's an error
            }
        }

        // General improvement recommendations
        if ($score >= 80) {
            $recommendations[] = [
                'title' => '¡Excelente Estado!',
                'description' => 'El tenant está en excelente estado. Continúa monitoreando periódicamente.',
                'action' => 'MANTENER',
                'priority' => 'low',
            ];
        }

        // Storage optimization
        if (isset($stats['storage_usage']['total_size_mb']) && $stats['storage_usage']['total_size_mb'] > 1000) {
            $recommendations[] = [
                'title' => 'Optimizar Almacenamiento',
                'description' => 'El tenant usa más de 1GB. Considera limpiar archivos antiguos o contratar más espacio.',
                'action' => 'OPTIMIZACIÓN',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }
}
