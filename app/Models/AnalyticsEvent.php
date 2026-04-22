<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AnalyticsEvent extends Model
{
    protected $connection = 'landlord';
    protected $table = 'analytics_events';

    public $timestamps = false; // Only using created_at

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'event_category',
        'event_name',
        'event_description',
        'properties',
        'ip_address',
        'user_agent',
        'session_id',
        'duration_ms',
        'status',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes for time periods
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeYesterday($query)
    {
        return $query->whereDate('created_at', Carbon::yesterday());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeLastWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    public function scopeLastMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', Carbon::now()->subMonth()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', Carbon::now()->year);
    }

    public function scopeLast30Days($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays(30));
    }

    public function scopeLast7Days($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays(7));
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    // Scopes for event types
    public function scopeLogins($query)
    {
        return $query->where('event_type', 'login');
    }

    public function scopeFeatureUsage($query)
    {
        return $query->where('event_type', 'feature_used');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods for statistics
    public static function getActiveUsersCount(string $period = 'today'): int
    {
        $query = self::distinct('user_id')->whereNotNull('user_id');

        return match($period) {
            'today' => $query->today()->count('user_id'),
            'week' => $query->thisWeek()->count('user_id'),
            'month' => $query->thisMonth()->count('user_id'),
            'year' => $query->thisYear()->count('user_id'),
            'last_7_days' => $query->last7Days()->count('user_id'),
            'last_30_days' => $query->last30Days()->count('user_id'),
            default => $query->today()->count('user_id'),
        };
    }

    public static function getActiveTenantsCount(string $period = 'today'): int
    {
        $query = self::distinct('tenant_id')->whereNotNull('tenant_id');

        return match($period) {
            'today' => $query->today()->count('tenant_id'),
            'week' => $query->thisWeek()->count('tenant_id'),
            'month' => $query->thisMonth()->count('tenant_id'),
            'year' => $query->thisYear()->count('tenant_id'),
            'last_7_days' => $query->last7Days()->count('tenant_id'),
            'last_30_days' => $query->last30Days()->count('tenant_id'),
            default => $query->today()->count('tenant_id'),
        };
    }

    public static function getMostUsedFeatures(int $limit = 10, string $period = 'month'): array
    {
        $query = self::featureUsage();

        $query = match($period) {
            'today' => $query->today(),
            'week' => $query->thisWeek(),
            'month' => $query->thisMonth(),
            'year' => $query->thisYear(),
            'last_7_days' => $query->last7Days(),
            'last_30_days' => $query->last30Days(),
            default => $query->thisMonth(),
        };

        return $query->selectRaw('event_name, COUNT(*) as usage_count')
            ->groupBy('event_name')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public static function getUsersGrowth(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        return self::selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as users')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public static function getTenantsGrowth(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        return self::selectRaw('DATE(created_at) as date, COUNT(DISTINCT tenant_id) as tenants')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public static function getEventsByCategory(string $period = 'month'): array
    {
        $query = self::query();

        $query = match($period) {
            'today' => $query->today(),
            'week' => $query->thisWeek(),
            'month' => $query->thisMonth(),
            'year' => $query->thisYear(),
            default => $query->thisMonth(),
        };

        return $query->selectRaw('event_category, COUNT(*) as count')
            ->groupBy('event_category')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'event_category')
            ->toArray();
    }
}
