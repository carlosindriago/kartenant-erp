<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\StoreSetting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Check if this is a tenant subdomain
        $host = request()->getHost();

        // Initialize tenant variables
        $storeName = null;
        $storeSlogan = null;
        $brandColor = null;
        $logoUrl = null;
        $isTenant = false;

        // Simple detection: if host contains a dot and looks like a subdomain
        if (str_contains($host, '.')) {
            // Extract the first part as potential subdomain
            $parts = explode('.', $host);
            $subdomain = $parts[0] ?? null;

            // Check if this looks like a tenant subdomain (has at least 3 parts: subdomain.domain.tld)
            if (count($parts) >= 3 && $subdomain && $subdomain !== 'emporiodigital' && $subdomain !== 'www') {
                try {
                    // Check if tenant exists
                    $tenant = Tenant::where('domain', $subdomain)->first();

                    if ($tenant) {
                        $isTenant = true;

                        // Initialize tenant context to get store settings
                        try {
                            $tenantManager = app(SwitchTenantDatabaseTask::class);
                            $tenant->makeCurrent();

                            $settings = StoreSetting::current();
                            $storeName = $settings->effective_store_name ?? $tenant->display_name ?? $tenant->name ?? 'Emporio Digital';
                            $storeSlogan = $settings->effective_store_slogan ?? 'Inicia sesión para gestionar tu negocio';
                            $brandColor = $settings->effective_brand_color ?? '#2563eb';
                            $logoUrl = $settings->logo_url ?? $tenant->logo_url ?? null;
                        } catch (\Exception $e) {
                            // Fallback to tenant basic info if store settings fail
                            $storeName = $tenant->display_name ?? $tenant->name ?? 'Emporio Digital';
                            $storeSlogan = 'Inicia sesión para gestionar tu negocio';
                            $brandColor = '#2563eb';
                            $logoUrl = $tenant->logo_url ?? null;
                        }
                    }
                } catch (\Exception $e) {
                    // If there's any error, continue with default login
                }
            }
        }

        return view('auth.login', compact('isTenant', 'storeName', 'storeSlogan', 'brandColor', 'logoUrl'));
    }

    /**
     * Handle an incoming authentication request.
     *
     * SECURITY WARNING: This method is BLOCKED by BlockAuthenticationBypass middleware
     * to prevent 2FA bypass vulnerability. All authentication must go through
     * proper tenant or admin channels with 2FA enforcement.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // CRITICAL SECURITY: This method should never be reachable
        // BlockAuthenticationBypass middleware prevents access to this route
        abort(403, 'Authentication bypass attempt detected and blocked');

        // The following code is unreachable but kept for reference:
        /*
        $request->authenticate();
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard', absolute: false));
        */
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
