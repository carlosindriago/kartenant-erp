<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Comprehensive health check for monitoring
     *
     * Returns JSON with status of all critical services
     * Useful for uptime monitoring tools (UptimeRobot, Pingdom, etc.)
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'landlord_database' => $this->checkLandlordDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'tenants' => $this->checkTenants(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connection (default)
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check landlord database connection
     */
    protected function checkLandlordDatabase(): array
    {
        try {
            DB::connection('landlord')->getPdo();
            $tenantCount = Tenant::count();

            return [
                'status' => 'ok',
                'message' => 'Landlord database connected',
                'tenants_count' => $tenantCount,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Landlord database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system
     */
    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test';

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache is working',
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cache write/read mismatch',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage system
     */
    protected function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check';

            Storage::put($testFile, $testContent);
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);

            if ($exists) {
                return [
                    'status' => 'ok',
                    'message' => 'Storage is writable',
                    'disk' => config('filesystems.default'),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Storage write failed',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Storage system error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check tenant databases
     */
    protected function checkTenants(): array
    {
        try {
            $tenants = Tenant::all();
            $healthyCount = 0;
            $unhealthyCount = 0;

            foreach ($tenants as $tenant) {
                try {
                    // Try to connect to tenant database
                    DB::connection('tenant')
                        ->table('information_schema.tables')
                        ->where('table_schema', $tenant->database)
                        ->count();

                    $healthyCount++;
                } catch (Throwable $e) {
                    $unhealthyCount++;
                }
            }

            if ($unhealthyCount === 0) {
                return [
                    'status' => 'ok',
                    'message' => 'All tenant databases accessible',
                    'total' => $tenants->count(),
                    'healthy' => $healthyCount,
                ];
            }

            return [
                'status' => 'warning',
                'message' => 'Some tenant databases are inaccessible',
                'total' => $tenants->count(),
                'healthy' => $healthyCount,
                'unhealthy' => $unhealthyCount,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Tenant check failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
