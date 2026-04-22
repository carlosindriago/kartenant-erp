<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\TrialIpTracking;
use App\Models\RegistrationAttempt;
use Closure;
use Illuminate\Http\Request;

class PreventRegistrationAbuse
{
    private const MAX_ATTEMPTS_PER_HOUR = 5;
    
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        
        // 1. Check if IP is blocked
        if (BlockedIp::isBlocked($ip)) {
            return response()->json([
                'message' => 'Acceso bloqueado. Contacta soporte si crees que es un error.'
            ], 403);
        }
        
        // 2. Rate limiting check
        $recentAttempts = RegistrationAttempt::getRecentAttempts($ip, 60);
        
        if ($recentAttempts >= self::MAX_ATTEMPTS_PER_HOUR) {
            // Block IP temporarily (1 hour)
            BlockedIp::blockIp(
                $ip,
                'Demasiados intentos de registro',
                'temporary',
                1
            );
            
            return response()->json([
                'message' => 'Demasiados intentos. Por favor intenta más tarde.'
            ], 429);
        }
        
        // 3. Check honeypot field (invisible field that bots fill)
        if (!empty($request->input('website'))) {
            \App\Models\HoneypotSubmission::recordSubmission(
                'website',
                $request->input('website')
            );
            
            // Return generic error (don't reveal honeypot)
            return response()->json([
                'message' => 'Error en el formulario. Por favor intenta nuevamente.'
            ], 422);
        }
        
        return $next($request);
    }
}
