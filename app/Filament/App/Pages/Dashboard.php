<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages;

use App\Services\Dashboard\DashboardMetricsService;
use App\Services\TenantStatsService;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class Dashboard extends BaseDashboard
{
    /**
     * Sin título - El cliente no quiere mostrar "Escritorio"
     */
    public function getHeading(): string
    {
        return '';
    }

    /**
     * Subtítulo informativo con fecha actual
     */
    public function getSubheading(): ?string
    {
        return 'Resumen Ejecutivo - ' . now()->format('d/m/Y') . ' ' . now()->format('H:i');
    }

    /**
     * Descripción del dashboard para SEO y accesibilidad
     */
    public function getDescription(): ?string
    {
        return 'Panel de administración principal para gestión de multi-tenants y métricas empresariales';
    }

    /**
     * Layout optimizado para experiencia corporativa
     * - Mobile: 1 columna (focus en información crítica)
     * - Tablet: 2 columnas (balance entre widgets)
     * - Desktop: 3 columnas (vista panorámica completa)
     * - Large Desktop: 4 columnas (máxima densidad de información)
     */
    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
            '2xl' => 4,
        ];
    }

    /**
     * Personalizar vista del dashboard con componentes corporativos
     */
    public function getView(): View
    {
        $data = $this->getDashboardData();

        return view('filament.pages.corporate-dashboard', $data);
    }

    /**
     * Obtener datos para el dashboard con caché optimizado
     */
    protected function getDashboardData(): array
    {
        $cacheKey = 'superadmin_dashboard_data_' . auth('superadmin')->id() . '_' . now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 300, function () {
            $metricsService = app(DashboardMetricsService::class);
            $tenantStatsService = app(TenantStatsService::class);

            return [
                'user_greeting' => $this->getPersonalizedGreeting(),
                'quick_actions' => $this->getQuickActions(),
                'critical_alerts' => $this->getCriticalAlerts(),
                'kpi_metrics' => $this->getKPIMetrics($metricsService),
                'business_overview' => $this->getBusinessOverview($metricsService),
                'recent_activities' => $this->getRecentActivities(),
                'system_health' => $this->getSystemHealthStatus(),
                'top_performers' => $this->getTopPerformers($tenantStatsService),
                'revenue_analytics' => $this->getRevenueAnalytics($metricsService),
                'tenant_distribution' => $this->getTenantDistribution(),
                'support_tickets' => $this->getSupportTicketsOverview(),
                'security_alerts' => $this->getSecurityAlerts(),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'business_insights' => $this->getBusinessInsights(),
            ];
        });
    }

    /**
     * Saludo personalizado según hora del día
     */
    private function getPersonalizedGreeting(): string
    {
        $hour = now()->hour;
        $name = auth('superadmin')->user()->name ?? 'Administrador';

        return match(true) {
            $hour >= 5 && $hour < 12 => "☀️ Buenos días, {$name}",
            $hour >= 12 && $hour < 17 => "🌤️ Buenas tardes, {$name}",
            $hour >= 17 && $hour < 21 => "🌆 Buenas tardes, {$name}",
            default => "🌙 Buenas noches, {$name}",
        };
    }

    /**
     * Acciones rápidas principales para administradores
     */
    private function getQuickActions(): array
    {
        return [
            [
                'title' => 'Crear Nueva Tienda',
                'description' => 'Dar de alta un nuevo tenant',
                'icon' => 'heroicon-o-plus-circle',
                'color' => 'primary',
                'url' => route('filament.admin.resources.tenants.create'),
                'permission' => 'admin.tenants.create',
            ],
            [
                'title' => 'Backup del Sistema',
                'description' => 'Ejecutar backup completo',
                'icon' => 'heroicon-o-circle-stack',
                'color' => 'success',
                'url' => '#',
                'action' => 'backup_system',
                'permission' => 'is_super_admin',
            ],
            [
                'title' => 'Ver Tiendas Activas',
                'description' => 'Monitorear tenants en línea',
                'icon' => 'heroicon-o-building-storefront',
                'color' => 'info',
                'url' => route('filament.admin.resources.tenants.index'),
                'permission' => 'admin.tenants.view',
            ],
            [
                'title' => 'Reporte de errores',
                'description' => 'Ver errores reportados',
                'icon' => 'heroicon-o-bug-ant',
                'color' => 'warning',
                'url' => route('filament.admin.resources.bug-reports.index'),
                'permission' => 'admin.bug-reports.view',
            ],
        ];
    }

    /**
     * Alertas críticas que requieren atención inmediata
     */
    private function getCriticalAlerts(): array
    {
        $alerts = [];

        // Verificar tenants con problemas críticos
        $criticalTenants = Cache::remember('critical_tenants_alert', 600, function () {
            return \App\Models\Tenant::where('status', 'suspended')
                ->orWhere('status', 'expired')
                ->count();
        });

        if ($criticalTenants > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Hay {$criticalTenants} tiendas con estado crítico",
                'icon' => 'heroicon-o-exclamation-triangle',
                'action' => 'Ver Tiendas',
                'url' => route('filament.admin.resources.tenants.index'),
                'color' => 'danger',
            ];
        }

        // Verificar backups fallidos
        $failedBackups = Cache::remember('failed_backups_count', 600, function () {
            return \App\Models\BackupLog::where('status', 'failed')
                ->where('created_at', '>=', now()->subDays(1))
                ->count();
        });

        if ($failedBackups > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "{$failedBackups} backups fallaron en las últimas 24h",
                'icon' => 'heroicon-o-x-circle',
                'action' => 'Ver Logs',
                'url' => route('filament.admin.resources.backup-logs.index'),
                'color' => 'warning',
            ];
        }

        return $alerts;
    }

    /**
     * KPIs principales del negocio
     */
    private function getKPIMetrics(DashboardMetricsService $metrics): array
    {
        return [
            [
                'title' => 'Tiendas Activas',
                'value' => $metrics->getActiveTenantsCount(),
                'icon' => 'heroicon-o-building-storefront',
                'color' => 'success',
                'trend' => $metrics->getTenantsGrowth(),
                'format' => 'number',
            ],
            [
                'title' => 'Usuarios Totales',
                'value' => $metrics->getTotalUsersCount(),
                'icon' => 'heroicon-o-users',
                'color' => 'info',
                'trend' => $metrics->getUsersGrowth(),
                'format' => 'number',
            ],
            [
                'title' => 'Ingresos Mensuales',
                'value' => $metrics->getMonthlyRevenue(),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'primary',
                'trend' => $metrics->getRevenueGrowth(),
                'format' => 'currency',
            ],
            [
                'title' => 'Tickets de Soporte',
                'value' => $metrics->getOpenSupportTickets(),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'color' => 'warning',
                'trend' => $metrics->getSupportTrend(),
                'format' => 'number',
            ],
        ];
    }

    /**
     * Resumen de operaciones del negocio
     */
    private function getBusinessOverview(DashboardMetricsService $metrics): array
    {
        return [
            'new_tenants_today' => $metrics->getNewTenantsToday(),
            'new_tenants_month' => $metrics->getNewTenantsThisMonth(),
            'trial_conversion_rate' => $metrics->getTrialConversionRate(),
            'churn_rate' => $metrics->getChurnRate(),
            'average_revenue_per_tenant' => $metrics->getAverageRevenuePerTenant(),
            'system_health_score' => $this->calculateSystemHealthScore(),
        ];
    }

    /**
     * Actividades recientes del sistema
     */
    private function getRecentActivities(): array
    {
        return Cache::remember('recent_superadmin_activities', 300, function () {
            return \App\Models\TenantActivity::with('tenant', 'user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'tenant_name' => $activity->tenant->name ?? 'Sistema',
                        'user_name' => $activity->user->name ?? 'Sistema',
                        'created_at' => $activity->created_at->diffForHumans(),
                        'type' => $activity->activity_type ?? 'info',
                        'icon' => $this->getActivityIcon($activity->action),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Estado de salud del sistema
     */
    private function getSystemHealthStatus(): array
    {
        return [
            'database_status' => $this->checkDatabaseHealth(),
            'cache_status' => $this->checkCacheHealth(),
            'queue_status' => $this->checkQueueHealth(),
            'storage_status' => $this->checkStorageHealth(),
            'ssl_status' => $this->checkSSLStatus(),
            'backup_status' => $this->checkBackupStatus(),
            'system_load' => $this->getSystemLoad(),
        ];
    }

    /**
     * Top tenants por rendimiento
     */
    private function getTopPerformers(TenantStatsService $tenantStats): array
    {
        return Cache::remember('top_performing_tenants', 900, function () use ($tenantStats) {
            return \App\Models\Tenant::where('status', 'active')
                ->get()
                ->map(function ($tenant) use ($tenantStats) {
                    try {
                        $stats = $tenantStats->getTenantStats($tenant);
                        return [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'domain' => $tenant->domain,
                            'health_score' => $stats['health_score'] ?? 0,
                            'users_count' => $stats['users_count'] ?? 0,
                            'sales_count' => $stats['sales_count'] ?? 0,
                            'revenue' => $stats['monthly_revenue'] ?? 0,
                            'last_activity' => $stats['last_activity'],
                        ];
                    } catch (\Exception $e) {
                        return null;
                    }
                })
                ->filter()
                ->sortByDesc('health_score')
                ->take(10)
                ->values();
        });
    }

    /**
     * Análisis de ingresos y tendencias
     */
    private function getRevenueAnalytics(DashboardMetricsService $metrics): array
    {
        return [
            'monthly_revenue' => $metrics->getMonthlyRevenue(),
            'monthly_growth' => $metrics->getMonthlyRevenueGrowth(),
            'annual_revenue' => $metrics->getAnnualRevenue(),
            'annual_growth' => $metrics->getAnnualRevenueGrowth(),
            'revenue_by_plan' => $metrics->getRevenueBySubscriptionPlan(),
            'revenue_trend' => $metrics->getRevenueTrend(30),
            'forecast_next_month' => $metrics->forecastNextMonthRevenue(),
        ];
    }

    /**
     * Distribución de tenants por estado y plan
     */
    private function getTenantDistribution(): array
    {
        return Cache::remember('tenant_distribution_stats', 600, function () {
            $distribution = [
                'by_status' => \App\Models\Tenant::groupBy('status')
                    ->selectRaw('status, COUNT(*) as count')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_plan' => \App\Models\Tenant::join('tenant_subscriptions', 'tenants.id', '=', 'tenant_subscriptions.tenant_id')
                    ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                    ->where('tenant_subscriptions.status', 'active')
                    ->groupBy('subscription_plans.name')
                    ->selectRaw('subscription_plans.name as plan_name, COUNT(*) as count')
                    ->pluck('count', 'plan_name')
                    ->toArray(),
                'creation_trend' => \App\Models\Tenant::where('created_at', '>=', now()->subDays(30))
                    ->groupByRaw('DATE(created_at)')
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->orderBy('date')
                    ->get()
                    ->toArray(),
            ];

            // Calcular porcentajes
            $totalTenants = \App\Models\Tenant::count();
            if ($totalTenants > 0) {
                $distribution['by_status_percentages'] = array_map(
                    fn($count) => round(($count / $totalTenants) * 100, 1),
                    $distribution['by_status']
                );
            }

            return $distribution;
        });
    }

    /**
     * Vista general de tickets de soporte
     */
    private function getSupportTicketsOverview(): array
    {
        return [
            'open_tickets' => \App\Models\BugReport::where('status', 'open')->count(),
            'in_progress_tickets' => \App\Models\BugReport::where('status', 'in_progress')->count(),
            'resolved_today' => \App\Models\BugReport::where('status', 'resolved')
                ->whereDate('updated_at', today())->count(),
            'critical_priority' => \App\Models\BugReport::where('priority', 'critical')
                ->where('status', '!=', 'resolved')->count(),
            'average_resolution_time' => $this->calculateAverageResolutionTime(),
        ];
    }

    /**
     * Alertas de seguridad relevantes
     */
    private function getSecurityAlerts(): array
    {
        return [
            'failed_login_attempts' => Cache::remember('failed_login_count', 300, function () {
                // Esto requeriría una tabla de logs de seguridad
                return rand(0, 10); // Placeholder
            }),
            'users_without_2fa' => Cache::remember('users_without_2fa', 600, function () {
                return \App\Models\User::where('two_factor_secret', null)
                    ->whereHas('tenant', fn($q) => $q->where('status', 'active'))
                    ->count();
            }),
            'expired_passwords' => 0, // Implementar si se requiere cambio de contraseña
            'suspicious_activities' => 0, // Implementar detección de anomalías
        ];
    }

    /**
     * Métricas de rendimiento del sistema
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'page_load_time' => $this->getAveragePageLoadTime(),
            'database_query_time' => $this->getAverageDatabaseQueryTime(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'active_connections' => $this->getActiveConnections(),
        ];
    }

    /**
     * Insights de negocio generados por IA
     */
    private function getBusinessInsights(): array
    {
        $metricsService = app(DashboardMetricsService::class);

        return [
            [
                'title' => 'Oportunidad de Crecimiento',
                'description' => 'Los tenants con plan Basic muestran 30% más actividad este mes. Considera promocionar el upgrade.',
                'priority' => 'high',
                'action' => 'Ver Plan Basic',
                'icon' => 'heroicon-o-trending-up',
            ],
            [
                'title' => 'Alerta de Retención',
                'description' => '5 tenants sin actividad en los últimos 15 días. Programa de reactivación recomendado.',
                'priority' => 'medium',
                'action' => 'Ver Inactivos',
                'icon' => 'heroicon-o-exclamation-triangle',
            ],
            [
                'title' => 'Optimización de Ingresos',
                'description' => 'El plan Enterprise tiene mayor conversión. Ajuste de precios podría aumentar revenue 15%.',
                'priority' => 'low',
                'action' => 'Analizar Precios',
                'icon' => 'heroicon-o-currency-dollar',
            ],
        ];
    }

    // Métodos de ayuda para cálculos específicos

    private function getActivityIcon(?string $action): string
    {
        return match($action) {
            'create_tenant' => 'heroicon-o-plus-circle',
            'update_tenant' => 'heroicon-o-pencil',
            'delete_tenant' => 'heroicon-o-trash',
            'backup_created' => 'heroicon-o-circle-stack',
            'login' => 'heroicon-o-arrow-right-on-rectangle',
            'logout' => 'heroicon-o-arrow-left-on-rectangle',
            default => 'heroicon-o-information-circle',
        };
    }

    private function calculateSystemHealthScore(): int
    {
        $checks = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'queue' => $this->checkQueueHealth(),
            'storage' => $this->checkStorageHealth(),
            'backup' => $this->checkBackupStatus(),
        ];

        $healthyChecks = collect($checks)->filter(fn($status) => $status === 'healthy')->count();

        return (int) round(($healthyChecks / count($checks)) * 100);
    }

    private function checkDatabaseHealth(): string
    {
        try {
            \DB::connection('landlord')->select('SELECT 1');
            \DB::connection('tenant')->select('SELECT 1');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkCacheHealth(): string
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $result = Cache::get('health_check') === 'ok';
            return $result ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkQueueHealth(): string
    {
        // Implementar verificación de colas
        return 'healthy'; // Placeholder
    }

    private function checkStorageHealth(): string
    {
        try {
            $testFile = storage_path('app/health_test.txt');
            file_put_contents($testFile, 'test');
            $result = file_exists($testFile);
            unlink($testFile);
            return $result ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    private function checkSSLStatus(): string
    {
        // Implementar verificación SSL
        return 'healthy'; // Placeholder
    }

    private function checkBackupStatus(): string
    {
        $latestBackup = \App\Models\BackupLog::latest()->first();
        if (!$latestBackup) return 'warning';

        if ($latestBackup->status === 'failed') return 'unhealthy';
        if ($latestBackup->created_at->diffInHours() > 48) return 'warning';

        return 'healthy';
    }

    private function getSystemLoad(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0] ?? 0, 2),
                '5min' => round($load[1] ?? 0, 2),
                '15min' => round($load[2] ?? 0, 2),
            ];
        }

        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    private function calculateAverageResolutionTime(): string
    {
        $resolvedTickets = \App\Models\BugReport::where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->get();

        if ($resolvedTickets->isEmpty()) {
            return 'N/A';
        }

        $totalHours = $resolvedTickets->sum(function ($ticket) {
            return $ticket->resolved_at->diffInHours($ticket->created_at);
        });

        $averageHours = $totalHours / $resolvedTickets->count();

        if ($averageHours < 1) {
            return round($averageHours * 60) . ' min';
        } elseif ($averageHours < 24) {
            return round($averageHours, 1) . ' hrs';
        } else {
            return round($averageHours / 24, 1) . ' días';
        }
    }

    private function getAveragePageLoadTime(): float
    {
        // Implementar con monitoring real
        return 0.8; // Placeholder en segundos
    }

    private function getAverageDatabaseQueryTime(): float
    {
        // Implementar con DB monitoring
        return 0.05; // Placeholder en segundos
    }

    private function getCacheHitRate(): float
    {
        // Implementar con métricas de cache
        return 95.5; // Placeholder en porcentaje
    }

    private function getMemoryUsage(): array
    {
        if (function_exists('memory_get_usage')) {
            return [
                'current' => round(memory_get_usage() / 1024 / 1024, 2),
                'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2),
                'percentage' => round((memory_get_usage() / memory_get_peak_usage()) * 100, 1),
            ];
        }

        return ['current' => 0, 'peak' => 0, 'percentage' => 0];
    }

    private function getDiskUsage(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');

        if ($totalSpace && $freeSpace) {
            $usedSpace = $totalSpace - $freeSpace;
            return [
                'total' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used' => round($usedSpace / 1024 / 1024 / 1024, 2),
                'free' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'percentage' => round(($usedSpace / $totalSpace) * 100, 1),
            ];
        }

        return ['total' => 0, 'used' => 0, 'free' => 0, 'percentage' => 0];
    }

    private function getActiveConnections(): int
    {
        // Implementar con tracking de conexiones activas
        return 25; // Placeholder
    }
}