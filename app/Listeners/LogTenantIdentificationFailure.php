<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Spatie\Multitenancy\Events\TenantNotFoundForRequestEvent;

class LogTenantIdentificationFailure
{
    public function handle(TenantNotFoundForRequestEvent $event): void
    {
        $request = $event->request;

        // Extract security-relevant information
        $host = $request->getHost();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $url = $request->fullUrl();

        // Attempt to extract potential tenant identifier from subdomain
        $parts = explode('.', $host);
        $potentialTenantSlug = count($parts) >= 3 ? $parts[0] : null;

        // Log the security event
        AuditLogger::log(
            subject: null,
            causer: null, // No authenticated user for tenant identification
            description: 'Fallo en identificación de tenant - Acceso no autorizado a subdominio',
            event: 'tenant_identification_failed',
            logName: 'security',
            properties: [
                'host' => $host,
                'potential_tenant_slug' => $potentialTenantSlug,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'request_url' => $url,
                'request_method' => $request->method(),
                'is_ajax' => $request->ajax(),
                'expects_json' => $request->expectsJson(),
            ],
        );

        // Additional security logging for potential attacks
        if ($potentialTenantSlug) {
            \Log::warning('Potential tenant enumeration attack detected', [
                'ip' => $ip,
                'host' => $host,
                'attempted_tenant' => $potentialTenantSlug,
                'url' => $url,
                'user_agent' => $userAgent,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
}