<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\VerificationIpBlacklist;
use App\Models\VerificationSecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class VerificationSecurityMiddleware
{
    // Límites configurables
    private const MAX_REQUESTS_PER_MINUTE = 10;

    private const MAX_REQUESTS_PER_HOUR = 100;

    private const MAX_INVALID_ATTEMPTS = 5;

    private const BLACKLIST_DURATION_MINUTES = 60;

    private const SUSPICIOUS_PATTERNS_THRESHOLD = 3;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ipAddress = $this->getClientIp($request);
        $tenant = $this->getTenantFromRequest($request);
        $tenantId = $tenant?->id;

        // 1. Verificar blacklist
        if ($this->isIpBlacklisted($ipAddress, $tenantId)) {
            VerificationSecurityLog::logEvent(
                $ipAddress,
                VerificationSecurityLog::EVENT_BLACKLIST_HIT,
                $tenantId,
                'IP bloqueada intentó acceder',
                VerificationSecurityLog::SEVERITY_WARNING
            );

            abort(403, 'Tu dirección IP ha sido bloqueada temporalmente por actividad sospechosa.');
        }

        // 2. Detectar bots simples (honeypot)
        if ($this->detectSimpleBot($request)) {
            $this->handleBotDetection($ipAddress, $tenantId);
            abort(403, 'Acceso denegado.');
        }

        // 3. Rate limiting por minuto
        $keyMinute = "verify_rate_limit_{$ipAddress}_minute";
        if (RateLimiter::tooManyAttempts($keyMinute, self::MAX_REQUESTS_PER_MINUTE)) {
            $this->handleRateLimitExceeded($ipAddress, $tenantId, 'minute');

            return response()->view('errors.429', [
                'message' => 'Demasiadas solicitudes. Por favor, espera un momento.',
                'retryAfter' => RateLimiter::availableIn($keyMinute),
            ], 429);
        }

        // 4. Rate limiting por hora
        $keyHour = "verify_rate_limit_{$ipAddress}_hour";
        if (RateLimiter::tooManyAttempts($keyHour, self::MAX_REQUESTS_PER_HOUR)) {
            $this->handleRateLimitExceeded($ipAddress, $tenantId, 'hour');

            // Blacklist temporal por 1 hora
            VerificationIpBlacklist::addToBlacklist(
                $ipAddress,
                'Exceso de solicitudes por hora',
                $tenantId,
                now()->addHour()
            );

            abort(429, 'Has excedido el límite de solicitudes por hora. Intenta más tarde.');
        }

        // 5. Detectar patrones sospechosos
        if ($this->detectSuspiciousPatterns($request, $ipAddress, $tenantId)) {
            $this->handleSuspiciousActivity($ipAddress, $tenantId);
        }

        // Incrementar contadores
        RateLimiter::hit($keyMinute, 60); // 1 minuto
        RateLimiter::hit($keyHour, 3600); // 1 hora

        $response = $next($request);

        // 6. Analizar respuesta para detectar intentos de fuerza bruta
        if ($response->status() === 404 || $request->get('notfound') === '1') {
            $this->handleInvalidHash($ipAddress, $tenantId);
        }

        return $response;
    }

    /**
     * Obtener IP real del cliente (incluso detrás de proxies)
     */
    private function getClientIp(Request $request): string
    {
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP'); // Cloudflare
        }

        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        if ($request->header('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));

            return trim($ips[0]);
        }

        return $request->ip();
    }

    /**
     * Verificar si IP está en blacklist
     */
    private function isIpBlacklisted(string $ipAddress, ?int $tenantId): bool
    {
        return VerificationIpBlacklist::isBlacklisted($ipAddress, $tenantId);
    }

    /**
     * Detectar bots simples con honeypot
     */
    private function detectSimpleBot(Request $request): bool
    {
        // Honeypot field - si está lleno, es un bot
        if ($request->filled('website') || $request->filled('email_confirm')) {
            return true;
        }

        // User agents sospechosos
        $userAgent = strtolower($request->userAgent() ?? '');
        $suspiciousAgents = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python'];

        foreach ($suspiciousAgents as $agent) {
            if (str_contains($userAgent, $agent)) {
                return true;
            }
        }

        // Sin user agent
        if (empty($userAgent)) {
            return true;
        }

        return false;
    }

    /**
     * Detectar patrones sospechosos
     */
    private function detectSuspiciousPatterns(Request $request, string $ipAddress, ?int $tenantId): bool
    {
        $cacheKey = "suspicious_patterns_{$ipAddress}_{$tenantId}";
        $patterns = Cache::get($cacheKey, []);

        // Patrón 1: Múltiples hashes inválidos seguidos
        if (count($patterns) >= self::SUSPICIOUS_PATTERNS_THRESHOLD) {
            return true;
        }

        // Patrón 2: Velocidad anormal (más de 3 requests en 5 segundos)
        $recentRequests = array_filter($patterns, function ($time) {
            return $time > now()->subSeconds(5)->timestamp;
        });

        if (count($recentRequests) >= 3) {
            return true;
        }

        return false;
    }

    /**
     * Manejar detección de bot
     */
    private function handleBotDetection(string $ipAddress, ?int $tenantId): void
    {
        VerificationSecurityLog::logEvent(
            $ipAddress,
            VerificationSecurityLog::EVENT_BOT_DETECTED,
            $tenantId,
            'Bot detectado vía honeypot o user agent',
            VerificationSecurityLog::SEVERITY_ALERT
        );

        // Blacklist inmediato por 24 horas
        VerificationIpBlacklist::addToBlacklist(
            $ipAddress,
            'Bot detectado',
            $tenantId,
            now()->addDay()
        );
    }

    /**
     * Manejar exceso de rate limit
     */
    private function handleRateLimitExceeded(string $ipAddress, ?int $tenantId, string $period): void
    {
        VerificationSecurityLog::logEvent(
            $ipAddress,
            VerificationSecurityLog::EVENT_RATE_LIMIT,
            $tenantId,
            "Exceso de solicitudes por {$period}",
            VerificationSecurityLog::SEVERITY_WARNING
        );
    }

    /**
     * Manejar actividad sospechosa
     */
    private function handleSuspiciousActivity(string $ipAddress, ?int $tenantId): void
    {
        VerificationSecurityLog::logEvent(
            $ipAddress,
            VerificationSecurityLog::EVENT_SUSPICIOUS_PATTERN,
            $tenantId,
            'Patrón de comportamiento sospechoso detectado',
            VerificationSecurityLog::SEVERITY_ALERT
        );

        // Blacklist temporal por 1 hora
        VerificationIpBlacklist::addToBlacklist(
            $ipAddress,
            'Patrón sospechoso detectado',
            $tenantId,
            now()->addHour()
        );
    }

    /**
     * Manejar intentos con hash inválido
     */
    private function handleInvalidHash(string $ipAddress, ?int $tenantId): void
    {
        $cacheKey = "invalid_hash_attempts_{$ipAddress}_{$tenantId}";
        $attempts = Cache::increment($cacheKey);

        if ($attempts === 1) {
            Cache::put($cacheKey, 1, 3600); // 1 hora
        }

        VerificationSecurityLog::logEvent(
            $ipAddress,
            VerificationSecurityLog::EVENT_INVALID_HASH,
            $tenantId,
            "Intento de hash inválido (total: {$attempts})",
            $attempts >= self::MAX_INVALID_ATTEMPTS
                ? VerificationSecurityLog::SEVERITY_CRITICAL
                : VerificationSecurityLog::SEVERITY_INFO
        );

        // Si supera el límite, blacklist temporal
        if ($attempts >= self::MAX_INVALID_ATTEMPTS) {
            VerificationIpBlacklist::addToBlacklist(
                $ipAddress,
                'Demasiados intentos con hashes inválidos',
                $tenantId,
                now()->addHours(self::BLACKLIST_DURATION_MINUTES / 60)
            );

            Cache::forget($cacheKey);
        }

        // Actualizar patrón sospechoso
        $patternKey = "suspicious_patterns_{$ipAddress}_{$tenantId}";
        $patterns = Cache::get($patternKey, []);
        $patterns[] = now()->timestamp;
        Cache::put($patternKey, $patterns, 600); // 10 minutos
    }

    /**
     * Obtener tenant desde request
     */
    private function getTenantFromRequest(Request $request): ?Tenant
    {
        $currentTenant = app()->has('currentTenant') ? app('currentTenant') : null;

        if ($currentTenant) {
            return $currentTenant;
        }

        $domain = $request->getHost();

        return Tenant::where('domain', $domain)
            ->orWhere('domain', explode('.', $domain)[0])
            ->first();
    }
}
