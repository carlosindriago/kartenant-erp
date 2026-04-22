<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class TenantArchiveOTPController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:superadmin');
    }

    /**
     * Generate OTP for tenant archive operation
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
            ]);

            $tenant = Tenant::findOrFail($request->tenant_id);
            $admin = Auth::guard('superadmin')->user();

            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in cache for 15 minutes
            $key = "tenant_archive_otp_{$admin->id}_{$tenant->id}";
            Cache::put($key, [
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(15),
                'tenant_id' => $tenant->id,
                'admin_id' => $admin->id
            ], now()->addMinutes(15));

            return response()->json([
                'success' => true,
                'message' => 'Código de verificación generado correctamente',
                'otp_code' => app()->environment('local') ? $otp : null, // Only show in development
                'expires_at' => now()->addMinutes(15)->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate OTP for tenant archive operation
     */
    public function validateOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'otp_code' => 'required|string|size:6',
            ]);

            $tenant = Tenant::findOrFail($request->tenant_id);
            $admin = Auth::guard('superadmin')->user();

            $key = "tenant_archive_otp_{$admin->id}_{$tenant->id}";
            $cached = Cache::get($key);

            if (!$cached) {
                return response()->json([
                    'success' => false,
                    'error' => 'Código no válido o ha expirado',
                ], 400);
            }

            // Check expiration
            if (now()->gt($cached['expires_at'])) {
                Cache::forget($key);
                return response()->json([
                    'success' => false,
                    'error' => 'Código ha expirado',
                ], 400);
            }

            // Verify OTP
            if (!Hash::check($request->otp_code, $cached['otp'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Código incorrecto',
                ], 400);
            }

            // Clear OTP after successful validation
            Cache::forget($key);

            return response()->json([
                'success' => true,
                'message' => 'Código validado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check OTP status for current admin
     */
    public function status(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'has_pending' => false,
                'data' => null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}