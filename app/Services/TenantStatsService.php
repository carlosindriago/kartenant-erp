<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class TenantStatsService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    const CACHE_TTL = 300;

    /**
     * Get comprehensive tenant statistics
     */
    public function getTenantStats(Tenant $tenant): array
    {
        $cacheKey = "tenant_stats_{$tenant->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            try {
                // Temporarily switch to tenant database
                $originalConnection = DB::getDefaultConnection();

                if (!$this->canConnectToTenant($tenant->database)) {
                    return $this->getEmptyStats();
                }

                DB::purge('tenant');
                config(['database.connections.tenant.database' => $tenant->database]);
                DB::setDefaultConnection('tenant');

                $stats = [
                    // User Statistics
                    'users_count' => $this->getUsersCount(),
                    'active_users_count' => $this->getActiveUsersCount(),
                    'users_last_week' => $this->getUsersCountSince(Carbon::now()->subWeek()),

                    // Product Statistics
                    'products_count' => $this->getTableCount('products'),
                    'low_stock_products_count' => $this->getLowStockProductsCount(),
                    'products_added_last_month' => $this->getTableCountSince('products', Carbon::now()->subMonth()),

                    // Sales Statistics
                    'sales_count' => $this->getTableCount('sales'),
                    'sales_last_month' => $this->getTableCountSince('sales', Carbon::now()->subMonth()),
                    'total_revenue' => $this->getTotalRevenue(),
                    'revenue_last_month' => $this->getRevenueSince(Carbon::now()->subMonth()),

                    // Category Statistics
                    'categories_count' => $this->getTableCount('categories'),

                    // Customer Statistics
                    'customers_count' => $this->getTableCount('customers'),
                    'customers_last_month' => $this->getTableCountSince('customers', Carbon::now()->subMonth()),

                    // Database Statistics
                    'database_size' => $this->getDatabaseSize(),
                    'storage_usage' => $this->getStorageUsage(),

                    // Activity Statistics
                    'last_activity' => $this->getLastActivity(),
                    'activities_last_week' => $this->getActivityCountSince(Carbon::now()->subWeek()),
                ];

                // Restore original connection
                DB::setDefaultConnection($originalConnection);

                return $stats;

            } catch (Exception $e) {
                Log::error("Error getting tenant stats for {$tenant->database}: " . $e->getMessage());

                // Restore original connection in case of error
                DB::setDefaultConnection(config('database.default'));

                return $this->getEmptyStats();
            }
        });
    }

    /**
     * Get dashboard overview for all tenants
     */
    public function getAllTenantsOverview(): array
    {
        return Cache::remember('all_tenants_overview', self::CACHE_TTL, function () {
            $tenants = Tenant::withTrashed()->get();

            return [
                'total_tenants' => $tenants->count(),
                'active_tenants' => $tenants->active()->count(),
                'trial_tenants' => $tenants->trial()->count(),
                'suspended_tenants' => $tenants->suspended()->count(),
                'archived_tenants' => $tenants->archived()->count(),
                'expired_tenants' => $tenants->expired()->count(),
                'expiring_trials' => $tenants->withExpiringTrials()->count(),
                'expired_trials' => $tenants->withExpiredTrials()->count(),
                'soft_deleted_tenants' => $tenants->onlyTrashed()->count(),
            ];
        });
    }

    /**
     * Get tenant health metrics
     */
    public function getTenantHealth(Tenant $tenant): array
    {
        try {
            $stats = $this->getTenantStats($tenant);

            $health = [
                'status' => 'healthy',
                'issues' => [],
                'score' => 100,
            ];

            // Check database connectivity
            if (!$this->canConnectToTenant($tenant->database)) {
                $health['status'] = 'critical';
                $health['issues'][] = 'Database connection failed';
                $health['score'] = 0;
                return $health;
            }

            // Check user activity
            if ($stats['active_users_count'] === 0) {
                $health['issues'][] = 'No active users';
                $health['score'] -= 20;
            }

            // Check recent activity
            if (!$stats['last_activity'] || $stats['last_activity']->diffInDays() > 30) {
                $health['issues'][] = 'No recent activity (30+ days)';
                $health['score'] -= 15;
            }

            // Check data volume
            if ($stats['products_count'] < 10) {
                $health['issues'][] = 'Low product count';
                $health['score'] -= 10;
            }

            // Determine overall status
            if ($health['score'] >= 80) {
                $health['status'] = 'healthy';
            } elseif ($health['score'] >= 50) {
                $health['status'] = 'warning';
            } else {
                $health['status'] = 'critical';
            }

            return $health;

        } catch (Exception $e) {
            Log::error("Error getting tenant health for {$tenant->database}: " . $e->getMessage());

            return [
                'status' => 'error',
                'issues' => ['Unable to retrieve health metrics'],
                'score' => 0,
            ];
        }
    }

    /**
     * Get top performing tenants by revenue
     */
    public function getTopTenantsByRevenue(int $limit = 10): array
    {
        $tenants = Tenant::canAccess()->get();
        $results = [];

        foreach ($tenants as $tenant) {
            try {
                $stats = $this->getTenantStats($tenant);
                if ($stats['revenue_last_month'] > 0) {
                    $results[] = [
                        'tenant' => $tenant,
                        'revenue' => $stats['revenue_last_month'],
                        'sales_count' => $stats['sales_last_month'],
                    ];
                }
            } catch (Exception $e) {
                // Skip tenants with errors
                continue;
            }
        }

        // Sort by revenue and return top results
        usort($results, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Check if we can connect to tenant database
     */
    private function canConnectToTenant(string $database): bool
    {
        try {
            DB::statement("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user count for tenant
     */
    private function getUsersCount(): int
    {
        try {
            return DB::table('users')->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get active user count (users who logged in within last 30 days)
     */
    private function getActiveUsersCount(): int
    {
        try {
            return DB::table('users')
                ->whereNotNull('last_login_at')
                ->where('last_login_at', '>=', Carbon::now()->subDays(30))
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get users count since given date
     */
    private function getUsersCountSince(Carbon $date): int
    {
        try {
            return DB::table('users')
                ->where('created_at', '>=', $date)
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get table count
     */
    private function getTableCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get table count since given date
     */
    private function getTableCountSince(string $table, Carbon $date): int
    {
        try {
            return DB::table($table)
                ->where('created_at', '>=', $date)
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get low stock products count
     */
    private function getLowStockProductsCount(): int
    {
        try {
            return DB::table('products')
                ->where('stock', '<=', DB::raw('stock_min'))
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get total revenue from sales
     */
    private function getTotalRevenue(): float
    {
        try {
            return DB::table('sales')->sum('total') ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get revenue since given date
     */
    private function getRevenueSince(Carbon $date): float
    {
        try {
            return DB::table('sales')
                ->where('created_at', '>=', $date)
                ->sum('total') ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get database size in MB
     */
    private function getDatabaseSize(): float
    {
        try {
            $dbName = config('database.connections.tenant.database');
            $size = DB::selectOne("
                SELECT pg_size_pretty(pg_database_size(?)) as size,
                       pg_database_size(?) as size_bytes
            ", [$dbName, $dbName]);

            return round($size->size_bytes / 1024 / 1024, 2); // Convert to MB
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get storage usage (file system + database)
     */
    private function getStorageUsage(): array
    {
        try {
            $storagePath = storage_path('app/public');
            $totalSize = 0;

            if (is_dir($storagePath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($storagePath, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    $totalSize += $file->getSize();
                }
            }

            return [
                'database_size_mb' => $this->getDatabaseSize(),
                'files_size_mb' => round($totalSize / 1024 / 1024, 2),
                'total_size_mb' => round($this->getDatabaseSize() + ($totalSize / 1024 / 1024), 2),
            ];
        } catch (Exception $e) {
            return [
                'database_size_mb' => 0,
                'files_size_mb' => 0,
                'total_size_mb' => 0,
            ];
        }
    }

    /**
     * Get last activity timestamp
     */
    private function getLastActivity(): ?Carbon
    {
        try {
            // Try multiple tables to find the most recent activity
            $activities = [];

            // Check sales
            $lastSale = DB::table('sales')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastSale) {
                $activities[] = new Carbon($lastSale->created_at);
            }

            // Check stock movements
            $lastMovement = DB::table('stock_movements')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastMovement) {
                $activities[] = new Carbon($lastMovement->created_at);
            }

            // Check user logins
            $lastLogin = DB::table('users')
                ->whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->first();

            if ($lastLogin && $lastLogin->last_login_at) {
                $activities[] = new Carbon($lastLogin->last_login_at);
            }

            return !empty($activities) ? max($activities) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get activity count since given date
     */
    private function getActivityCountSince(Carbon $date): int
    {
        try {
            $count = 0;

            // Count sales
            $count += DB::table('sales')
                ->where('created_at', '>=', $date)
                ->count();

            // Count stock movements
            $count += DB::table('stock_movements')
                ->where('created_at', '>=', $date)
                ->count();

            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get empty stats structure for error cases
     */
    private function getEmptyStats(): array
    {
        return [
            'users_count' => 0,
            'active_users_count' => 0,
            'users_last_week' => 0,
            'products_count' => 0,
            'low_stock_products_count' => 0,
            'products_added_last_month' => 0,
            'sales_count' => 0,
            'sales_last_month' => 0,
            'total_revenue' => 0,
            'revenue_last_month' => 0,
            'categories_count' => 0,
            'customers_count' => 0,
            'customers_last_month' => 0,
            'database_size' => 0,
            'storage_usage' => [
                'database_size_mb' => 0,
                'files_size_mb' => 0,
                'total_size_mb' => 0,
            ],
            'last_activity' => null,
            'activities_last_week' => 0,
        ];
    }

    /**
     * Clear tenant stats cache
     */
    public function clearTenantCache(Tenant $tenant): void
    {
        Cache::forget("tenant_stats_{$tenant->id}");
    }

    /**
     * Clear all tenant stats cache
     */
    public function clearAllCache(): void
    {
        Cache::forget('all_tenants_overview');

        // Clear individual tenant caches (we don't know all the keys, so we'll skip this)
        // In production, consider using cache tags for this
    }
}