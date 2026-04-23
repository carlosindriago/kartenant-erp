<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SECURITY CRITICAL: Middleware to validate session encryption
 *
 * This middleware ensures that session encryption is enabled in production
 * environments to prevent sensitive session data exposure.
 */
class ValidateSessionEncryption
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate in production environments
        if (app()->environment('production')) {
            $sessionEncrypt = config('session.encrypt', true);

            if ($sessionEncrypt !== true) {
                // Log critical security violation
                logger()->critical('SECURITY VIOLATION: Session encryption disabled in production', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                ]);

                // Block the request with security error
                return response()->json([
                    'error' => 'Security Configuration Error',
                    'message' => 'Session encryption must be enabled in production for security compliance.',
                ], 500);
            }
        }

        return $next($request);
    }
}
