<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.system-health';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /**
     * Solo mostrar en panel admin
     */
    public static function canView(): bool
    {
        // Use filament() helper for proper panel context with null checks
        $panel = filament()->getCurrentPanel();

        return $panel && $panel->getId() === 'admin' && filament()->auth()->check();
    }

    public function getSystemStatus(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return [
            'overall_status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'last_check' => now()->format('Y-m-d H:i:s'),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection('landlord')->getPdo();

            return ['status' => 'ok', 'message' => 'Conectado'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Error de conexión'];
        }
    }

    protected function checkCache(): array
    {
        try {
            $key = 'health_test_'.time();
            Cache::put($key, 'test', 5);
            $result = Cache::get($key);
            Cache::forget($key);

            return ['status' => $result === 'test' ? 'ok' : 'error', 'message' => $result === 'test' ? 'Funcionando' : 'Error'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Error'];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $writable = is_writable(storage_path('logs'));

            return ['status' => $writable ? 'ok' : 'error', 'message' => $writable ? 'Escribible' : 'No escribible'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Error'];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');

            return ['status' => 'ok', 'message' => 'Configurado ('.$connection.')'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Error'];
        }
    }

    public function getSlackStatus(): array
    {
        $webhookUrl = config('logging.channels.slack.url');

        return [
            'configured' => ! empty($webhookUrl),
            'url' => $webhookUrl ? '✅ Configurado' : '❌ No configurado',
            'environment' => app()->environment(),
        ];
    }

    public function getEnvironmentInfo(): array
    {
        return [
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug') ? 'Activado' : 'Desactivado',
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
        ];
    }
}
