<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AnalyticsService
{
    /**
     * Track a generic event
     */
    public function track(
        string $eventType,
        string $eventCategory,
        string $eventName,
        ?string $description = null,
        ?array $properties = null,
        ?int $tenantId = null,
        ?int $userId = null
    ): void {
        try {
            AnalyticsEvent::create([
                'tenant_id' => $tenantId ?? $this->getCurrentTenantId(),
                'user_id' => $userId ?? $this->getCurrentUserId(),
                'event_type' => $eventType,
                'event_category' => $eventCategory,
                'event_name' => $eventName,
                'event_description' => $description,
                'properties' => $properties,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'session_id' => session()->getId(),
                'status' => 'success',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the app
            logger()->error('Analytics tracking failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track user login
     */
    public function trackLogin(?int $userId = null, ?int $tenantId = null): void
    {
        $this->track(
            eventType: 'login',
            eventCategory: 'user',
            eventName: 'user.login',
            description: 'User logged in',
            tenantId: $tenantId,
            userId: $userId ?? $this->getCurrentUserId()
        );
    }

    /**
     * Track user logout
     */
    public function trackLogout(?int $userId = null, ?int $tenantId = null): void
    {
        $this->track(
            eventType: 'logout',
            eventCategory: 'user',
            eventName: 'user.logout',
            description: 'User logged out',
            tenantId: $tenantId,
            userId: $userId ?? $this->getCurrentUserId()
        );
    }

    /**
     * Track feature usage
     */
    public function trackFeatureUse(
        string $featureName,
        string $action,
        ?array $properties = null,
        ?int $tenantId = null
    ): void {
        $this->track(
            eventType: 'feature_used',
            eventCategory: 'feature',
            eventName: "{$featureName}.{$action}",
            description: "Feature used: {$featureName} - {$action}",
            properties: $properties,
            tenantId: $tenantId
        );
    }

    /**
     * Track page view
     */
    public function trackPageView(string $page, ?string $url = null): void
    {
        $this->track(
            eventType: 'page_view',
            eventCategory: 'navigation',
            eventName: "page.{$page}",
            description: "Page viewed: {$page}",
            properties: ['url' => $url ?? Request::fullUrl()]
        );
    }

    /**
     * Track resource creation (product, sale, etc.)
     */
    public function trackResourceCreated(
        string $resourceType,
        int $resourceId,
        ?array $properties = null
    ): void {
        $this->track(
            eventType: 'resource_created',
            eventCategory: 'resource',
            eventName: "{$resourceType}.created",
            description: "{$resourceType} created",
            properties: array_merge(['resource_id' => $resourceId], $properties ?? [])
        );
    }

    /**
     * Track resource update
     */
    public function trackResourceUpdated(
        string $resourceType,
        int $resourceId,
        ?array $properties = null
    ): void {
        $this->track(
            eventType: 'resource_updated',
            eventCategory: 'resource',
            eventName: "{$resourceType}.updated",
            description: "{$resourceType} updated",
            properties: array_merge(['resource_id' => $resourceId], $properties ?? [])
        );
    }

    /**
     * Track resource deletion
     */
    public function trackResourceDeleted(
        string $resourceType,
        int $resourceId,
        ?array $properties = null
    ): void {
        $this->track(
            eventType: 'resource_deleted',
            eventCategory: 'resource',
            eventName: "{$resourceType}.deleted",
            description: "{$resourceType} deleted",
            properties: array_merge(['resource_id' => $resourceId], $properties ?? [])
        );
    }

    /**
     * Track API call
     */
    public function trackApiCall(
        string $endpoint,
        string $method,
        int $statusCode,
        ?int $durationMs = null
    ): void {
        $this->track(
            eventType: 'api_call',
            eventCategory: 'api',
            eventName: "api.{$method}.{$endpoint}",
            description: "API call: {$method} {$endpoint}",
            properties: [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
            ]
        );
    }

    /**
     * Track error
     */
    public function trackError(
        string $errorType,
        string $errorMessage,
        ?array $context = null
    ): void {
        $this->track(
            eventType: 'error',
            eventCategory: 'system',
            eventName: "error.{$errorType}",
            description: $errorMessage,
            properties: $context
        );
    }

    /**
     * Get current tenant ID
     */
    protected function getCurrentTenantId(): ?int
    {
        if (Auth::guard('tenant')->check()) {
            $user = Auth::guard('tenant')->user();

            return $user->tenants()->first()?->id;
        }

        return null;
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?int
    {
        if (Auth::guard('tenant')->check()) {
            return Auth::guard('tenant')->id();
        }

        if (Auth::guard('superadmin')->check()) {
            return Auth::guard('superadmin')->id();
        }

        if (Auth::check()) {
            return Auth::id();
        }

        return null;
    }

    /**
     * Get statistics for dashboard
     */
    public function getDashboardStats(): array
    {
        return [
            'active_users' => [
                'today' => AnalyticsEvent::getActiveUsersCount('today'),
                'week' => AnalyticsEvent::getActiveUsersCount('week'),
                'month' => AnalyticsEvent::getActiveUsersCount('month'),
                'year' => AnalyticsEvent::getActiveUsersCount('year'),
            ],
            'active_tenants' => [
                'today' => AnalyticsEvent::getActiveTenantsCount('today'),
                'week' => AnalyticsEvent::getActiveTenantsCount('week'),
                'month' => AnalyticsEvent::getActiveTenantsCount('month'),
                'year' => AnalyticsEvent::getActiveTenantsCount('year'),
            ],
            'most_used_features' => AnalyticsEvent::getMostUsedFeatures(10, 'month'),
            'events_by_category' => AnalyticsEvent::getEventsByCategory('month'),
            'trial_vs_paid' => $this->getTrialVsPaidStats(),
        ];
    }

    /**
     * Get trial vs paid tenants statistics
     */
    protected function getTrialVsPaidStats(): array
    {
        $totalTenants = Tenant::count();
        $trialTenants = Tenant::where('is_trial', true)
            ->where('status', 'active')
            ->count();
        $paidTenants = Tenant::where('is_trial', false)
            ->where('status', 'active')
            ->count();
        $inactiveTenants = Tenant::where('status', 'inactive')->count();

        return [
            'total' => $totalTenants,
            'trial' => $trialTenants,
            'paid' => $paidTenants,
            'inactive' => $inactiveTenants,
            'trial_percentage' => $totalTenants > 0 ? round(($trialTenants / $totalTenants) * 100, 2) : 0,
            'paid_percentage' => $totalTenants > 0 ? round(($paidTenants / $totalTenants) * 100, 2) : 0,
        ];
    }
}
