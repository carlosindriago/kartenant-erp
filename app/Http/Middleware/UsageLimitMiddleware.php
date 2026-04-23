<?php

namespace App\Http\Middleware;

use App\Services\TenantUsageService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class UsageLimitMiddleware
{
    public function __construct(
        private TenantUsageService $usageService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for these routes (always allowed)
        if ($this->shouldSkipMiddleware($request)) {
            return $next($request);
        }

        $tenant = tenant();
        if (! $tenant) {
            return $next($request);
        }

        $routeName = Route::currentRouteName();
        $action = $this->getActionFromRoute($routeName);

        if (! $action) {
            return $next($request);
        }

        // Check if action is allowed based on current usage
        if (! $this->usageService->canPerformAction($tenant->id, $action)) {
            return $this->handleBlockedAction($request, $action, $tenant->id);
        }

        $response = $next($request);

        // Add usage headers for debugging (only in development)
        if (app()->environment('local', 'testing')) {
            $usageStatus = $this->usageService->getUsageStatus($tenant->id);
            $response->headers->set('X-Usage-Status', $usageStatus['status']);
            $response->headers->set('X-Usage-Zone-'.$action, $usageStatus['metrics'][$this->getMetricFromAction($action)]['zone'] ?? 'unknown');
        }

        return $response;
    }

    /**
     * Determine if middleware should be skipped
     */
    private function shouldSkipMiddleware(Request $request): bool
    {
        $skippedPatterns = [
            'admin.*',      // Superadmin routes
            'billing.*',    // Billing pages (always accessible)
            'health',       // Health check
            'login',        // Authentication
            'logout',       // Logout
            'filament.*',   // Filament AJAX routes
        ];

        $routeName = Route::currentRouteName();

        foreach ($skippedPatterns as $pattern) {
            if ($routeName && fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // Skip for AJAX requests from Filament components
        if ($request->ajax() && $request->header('X-Filament')) {
            return true;
        }

        // Skip for GET requests (only limit creation/modification actions)
        if ($request->isMethod('GET')) {
            return true;
        }

        return false;
    }

    /**
     * Get action type from route name
     */
    private function getActionFromRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $actionMap = [
            // Products
            'filament.app.resources.products.store' => 'create_product',
            'filament.app.resources.products.update' => 'create_product', // Updates count as usage
            'products.store' => 'create_product',
            'products.update' => 'create_product',

            // Users
            'filament.app.resources.users.store' => 'create_user',
            'filament.app.resources.users.update' => 'create_user',
            'users.store' => 'create_user',
            'users.update' => 'create_user',

            // Sales (NEVER blocked, but we track the action)
            'filament.app.resources.sales.store' => 'make_sale',
            'sales.store' => 'make_sale',
            'pos.process-sale' => 'make_sale',

            // File uploads
            'upload-file' => 'storage_update',
            'file.store' => 'storage_update',
        ];

        return $actionMap[$routeName] ?? null;
    }

    /**
     * Get metric type from action
     */
    private function getMetricFromAction(string $action): string
    {
        return match ($action) {
            'create_product' => 'products',
            'create_user' => 'users',
            'make_sale' => 'sales',
            'storage_update' => 'storage',
            default => 'overall',
        };
    }

    /**
     * Handle blocked action
     */
    private function handleBlockedAction(Request $request, string $action, int $tenantId): Response
    {
        $usageStatus = $this->usageService->getUsageStatus($tenantId);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getBlockedMessage($action, $usageStatus),
                'usage_status' => $usageStatus['status'],
                'upgrade_required' => $usageStatus['upgrade_required'],
                'redirect_to_billing' => true,
            ], 422);
        }

        if ($request->session()) {
            $request->session()->flash('usage_limit_error', $this->getBlockedMessage($action, $usageStatus));
        }

        // Redirect to billing page if critical usage
        if ($usageStatus['status'] === 'critical') {
            return redirect()->route('billing.index')
                ->with('usage_limit_error', $this->getBlockedMessage($action, $usageStatus));
        }

        // Redirect back with error message
        return redirect()->back()
            ->with('usage_limit_error', $this->getBlockedMessage($action, $usageStatus));
    }

    /**
     * Get appropriate blocked message
     */
    private function getBlockedMessage(string $action, array $usageStatus): string
    {
        $status = $usageStatus['status'];
        $metricType = $this->getMetricFromAction($action);
        $metricInfo = $usageStatus['metrics'][$metricType] ?? null;

        if (! $metricInfo) {
            return 'Límite de uso alcanzado. Por favor, actualiza tu plan para continuar.';
        }

        $metricName = match ($metricType) {
            'products' => 'productos',
            'users' => 'usuarios',
            'sales' => 'ventas',
            'storage' => 'almacenamiento',
            default => 'recursos',
        };

        return match ($status) {
            'warning' => "Estás cerca del límite de {$metricName} ({$metricInfo['percentage']}%). Considera actualizar tu plan pronto para evitar interrupciones.",
            'overdraft' => "Has excedido el límite de {$metricName}. Las nuevas creaciones serán limitadas. Por favor, actualiza tu plan para restaurar todas las funcionalidades.",
            'critical' => "Límite crítico de {$metricName} excedido. Debes actualizar tu plan para continuar creando {$metricName}.",
            default => "Límite de {$metricName} alcanzado. Actualiza tu plan para continuar.",
        };
    }

    /**
     * Log blocked action attempt
     */
    private function logBlockedAttempt(Request $request, string $action, int $tenantId): void
    {
        logger()->warning('Usage limit blocked action', [
            'tenant_id' => $tenantId,
            'action' => $action,
            'route' => Route::currentRouteName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);
    }
}
