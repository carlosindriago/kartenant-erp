<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    /**
     * Display the tenant welcome page with store branding.
     */
    public function index(Request $request): View
    {
        // Get store settings for current tenant
        $storeSettings = StoreSetting::current();

        // Pass settings data to the view
        return view('tenant.welcome', [
            'storeSettings' => $storeSettings,
            'isCustomized' => $storeSettings->is_active,
            'cssVariables' => $storeSettings->css_variables,
        ]);
    }

    /**
     * Display the styled login page with store branding.
     */
    public function login(Request $request): View
    {
        // Get store settings for current tenant
        $storeSettings = StoreSetting::current();

        return view('tenant.auth.login', [
            'storeSettings' => $storeSettings,
            'isCustomized' => $storeSettings->is_active,
            'cssVariables' => $storeSettings->css_variables,
        ]);
    }

    /**
     * Get store settings as JSON for dynamic loading.
     */
    public function settings(Request $request)
    {
        $storeSettings = StoreSetting::current();

        return response()->json([
            'store_name' => $storeSettings->effective_store_name,
            'store_slogan' => $storeSettings->effective_store_slogan,
            'brand_color' => $storeSettings->effective_brand_color,
            'logo_url' => $storeSettings->logo_url,
            'background_url' => $storeSettings->show_background_image ? $storeSettings->background_image_url : null,
            'primary_font' => $storeSettings->effective_primary_font,
            'is_active' => $storeSettings->is_active,
            'social_links' => $storeSettings->social_media_links,
            'css_variables' => $storeSettings->css_variables,
        ]);
    }

    /**
     * Serve store assets (logos, backgrounds) with proper headers.
     */
    public function asset(Request $request, string $type, string $filename)
    {
        $storeSettings = StoreSetting::current();

        $path = match ($type) {
            'logo' => $storeSettings->logo_path,
            'background' => $storeSettings->background_image_path,
            default => null,
        };

        if (! $path || ! \Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = \Storage::disk('public')->get($path);
        $mimeType = \Storage::disk('public')->mimeType($path);

        return response($file)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=86400'); // Cache for 1 day
    }
}
