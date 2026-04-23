<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\BugReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BugReportController extends Controller
{
    public function __construct(
        private BugReportService $bugReportService
    ) {}

    public function submit(Request $request)
    {
        // Verify user is authenticated with tenant guard
        if (! auth('tenant')->check()) {
            Log::warning('[BugReport] Unauthenticated request attempt', [
                'tenant_guard' => auth('tenant')->check(),
                'web_guard' => auth('web')->check(),
                'default_guard' => auth()->check(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado. Por favor recarga la página e intenta nuevamente.',
            ], 401);
        }

        // Log incoming request for debugging
        Log::info('[BugReport] Incoming request', [
            'has_files' => $request->hasFile('screenshots'),
            'file_count' => $request->hasFile('screenshots') ? count($request->file('screenshots')) : 0,
            'user' => auth('tenant')->user()->email,
        ]);

        try {
            $validated = $request->validate([
                'severity' => 'required|in:low,medium,high,critical',
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'steps' => 'nullable|string|max:2000',
                'screenshots.*' => 'nullable|image|max:5120', // 5MB max per image
            ]);

            // Get authenticated user from tenant guard
            $user = auth('tenant')->user();

            // Get tenant name safely
            $tenantName = 'Unknown';
            try {
                // First try to get from user's tenants
                if ($user->tenants && $user->tenants->count() > 0) {
                    $tenantName = $user->tenants->first()->name;
                } elseif (function_exists('tenant') && tenant() && isset(tenant()->name)) {
                    $tenantName = tenant()->name;
                } else {
                    // Try to get from database name
                    $dbName = config('database.connections.tenant.database');
                    if ($dbName && str_starts_with($dbName, 'tenant_')) {
                        $tenantName = str_replace('tenant_', '', $dbName);
                        $tenantName = ucwords(str_replace('_', ' ', $tenantName));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[BugReport] Could not determine tenant name: '.$e->getMessage());
            }

            // Handle file uploads
            $screenshots = [];
            if ($request->hasFile('screenshots')) {
                foreach ($request->file('screenshots') as $file) {
                    $screenshots[] = $file;
                }
            }

            // Get tenant ID if available
            $tenantId = null;
            if ($user->tenants && $user->tenants->count() > 0) {
                $tenantId = $user->tenants->first()->id;
            }

            // Add automatic context
            $data = [
                'severity' => $validated['severity'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'steps' => $validated['steps'] ?? '',
                'screenshots' => $screenshots,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'tenant_id' => $tenantId,
                'tenant_name' => $tenantName,
                'url' => $request->header('Referer') ?? url()->current(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ];

            $result = $this->bugReportService->submitReport($data);

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: '.implode(', ', $e->validator->errors()->all()),
            ], 422);

        } catch (\Throwable $e) {
            Log::error('[BugReport] Error submitting bug report', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el reporte: '.$e->getMessage(),
            ], 500);
        }
    }
}
