<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantLandingController extends Controller
{
    public function show(Request $request)
    {
        try {
            // Get current tenant from the middleware (use alternative method if tenant() helper not available)
            $tenant = null;
            try {
                if (function_exists('tenant')) {
                    $tenant = tenant();
                } else {
                    // Alternative method to get current tenant - this might not work if tenant context is not established
                    $tenant = null;
                }
            } catch (\Exception $tenantException) {
                // Tenant resolution failed - continue with fallback
                $tenant = null;
                \Log::warning('Tenant resolution failed', [
                    'error' => $tenantException->getMessage(),
                    'host' => $request->getHost(),
                ]);
            }

            // Get store settings with automatic creation if needed
            $settings = null;
            try {
                if ($tenant) {
                    // If we have a tenant, try to get StoreSettings in tenant context
                    $settings = StoreSetting::current();
                } else {
                    // No tenant context, use fallback settings
                    $settings = null;
                }
            } catch (\Exception $settingsException) {
                // StoreSettings failed - continue with fallback
                $settings = null;
                \Log::warning('StoreSettings resolution failed', [
                    'error' => $settingsException->getMessage(),
                    'host' => $request->getHost(),
                ]);
            }

            // Return the production-ready welcome view
            return view('tenant.welcome', compact('settings', 'tenant'));

        } catch (\Exception $e) {
            // Fallback for any errors - still show a professional page
            \Log::warning('Error loading tenant landing page', [
                'error' => $e->getMessage(),
                'host' => $request->getHost(),
                'path' => $request->path(),
            ]);

            // Create fallback settings object
            $fallbackSettings = new \stdClass;
            $fallbackSettings->effective_store_name = config('app.name', 'Mi Tienda');
            $fallbackSettings->effective_welcome_message = '¡Bienvenido a tu tienda! Gestiona tu inventario de forma sencilla y eficiente.';
            $fallbackSettings->effective_store_slogan = 'Tu sistema de gestión comercial';
            $fallbackSettings->effective_brand_color = '#2563eb';
            $fallbackSettings->effective_primary_font = 'Inter';
            $fallbackSettings->logo_url = null;
            $fallbackSettings->background_image_url = null;
            $fallbackSettings->show_background_image = false;
            $fallbackSettings->facebook_url = null;
            $fallbackSettings->instagram_url = null;
            $fallbackSettings->whatsapp_number = null;
            $fallbackSettings->whatsapp_url = null;
            $fallbackSettings->contact_email = null;
            $fallbackSettings->hasSocialMedia = false;

            return view('tenant.welcome', [
                'settings' => $fallbackSettings,
                'tenant' => null,
            ]);
        }
    }
}
