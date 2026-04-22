<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\API\V1\UserResource;
use App\Http\Resources\API\V1\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Authentication Controller
 *
 * Handles authentication for API v1.
 * Endpoints:
 * - POST /api/v1/auth/login
 * - POST /api/v1/auth/logout
 * - POST /api/v1/auth/refresh
 * - GET  /api/v1/auth/me
 * - POST /api/v1/auth/password/forgot
 * - POST /api/v1/auth/password/reset
 */
class AuthController extends BaseApiController
{
    /**
     * Login user and return token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'tenant_domain' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Find tenant by domain
        $tenant = Tenant::where('domain', $request->tenant_domain)->first();

        if (!$tenant) {
            Log::warning('[API Auth] Tenant not found during login', [
                'tenant_domain' => $request->tenant_domain,
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse(
                message: 'Tenant no encontrado',
                code: 'TENANT_NOT_FOUND',
                statusCode: 404
            );
        }

        // Check if tenant is active
        if ($tenant->status !== 'active') {
            Log::warning('[API Auth] Attempted login to inactive tenant', [
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'tenant_status' => $tenant->status,
                'email' => $request->email,
            ]);

            return $this->errorResponse(
                message: 'Este tenant está inactivo. Contacte al administrador.',
                code: 'TENANT_INACTIVE',
                statusCode: 403
            );
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Verify user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('[API Auth] Failed login attempt', [
                'email' => $request->email,
                'tenant_domain' => $request->tenant_domain,
                'ip' => $request->ip(),
            ]);

            return $this->errorResponse(
                message: 'Credenciales incorrectas',
                code: 'INVALID_CREDENTIALS',
                statusCode: 401
            );
        }

        // Check if user belongs to the tenant
        $belongsToTenant = $user->tenants()->where('tenants.id', $tenant->id)->exists();

        if (!$belongsToTenant) {
            Log::warning('[API Auth] User does not belong to tenant', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
            ]);

            return $this->errorResponse(
                message: 'No tienes acceso a este tenant',
                code: 'FORBIDDEN',
                statusCode: 403
            );
        }

        // Make tenant current
        $tenant->makeCurrent();

        // Load user roles and permissions for this tenant
        $user->load(['roles', 'permissions']);

        // Create token
        $deviceName = $request->device_name ?? 'API Client';
        $token = $user->createToken($deviceName);

        // Get token expiration (default 30 days from config)
        $expiresAt = now()->addDays(config('sanctum.expiration', 30));

        Log::info('[API Auth] Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'tenant_id' => $tenant->id,
            'tenant_domain' => $tenant->domain,
            'device_name' => $deviceName,
        ]);

        // Return success response with token and user data
        return $this->successResponse([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => new UserResource($user),
            'tenant' => new TenantResource($tenant),
        ], 'Login exitoso');
    }

    /**
     * Logout user (revoke current token)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Get current user
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse('No autenticado');
        }

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        Log::info('[API Auth] User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $this->successResponse(null, 'Logout exitoso');
    }

    /**
     * Refresh token (create new token and revoke old one)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse('No autenticado');
        }

        // Get device name from old token
        $oldToken = $request->user()->currentAccessToken();
        $deviceName = $oldToken->name ?? 'API Client';

        // Revoke old token
        $oldToken->delete();

        // Create new token
        $token = $user->createToken($deviceName);
        $expiresAt = now()->addDays(config('sanctum.expiration', 30));

        Log::info('[API Auth] Token refreshed', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
        ], 'Token actualizado');
    }

    /**
     * Get current authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse('No autenticado');
        }

        // Load roles and permissions
        $user->load(['roles', 'permissions']);

        // Get current tenant
        $tenant = Tenant::current();

        return $this->successResponse([
            'user' => new UserResource($user),
            'tenant' => $tenant ? new TenantResource($tenant) : null,
        ]);
    }

    /**
     * Forgot password (send reset link)
     *
     * TODO: Implement email sending
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // TODO: Implement password reset logic
        // For now, just return success
        return $this->successResponse(
            null,
            'Si el correo existe, recibirás un enlace para restablecer tu contraseña'
        );
    }

    /**
     * Reset password
     *
     * TODO: Implement password reset logic
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // TODO: Implement password reset logic
        return $this->successResponse(null, 'Contraseña actualizada exitosamente');
    }
}
