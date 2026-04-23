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
use Illuminate\Support\Facades\Cache;

class VerificationIpBlacklist extends Model
{
    protected $connection = 'landlord';

    protected $table = 'verification_ip_blacklist';

    protected $fillable = [
        'ip_address',
        'tenant_id',
        'reason',
        'offense_count',
        'blocked_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if an IP is blacklisted
     */
    public static function isBlacklisted(string $ipAddress, ?int $tenantId = null): bool
    {
        $cacheKey = "ip_blacklist_{$ipAddress}_".($tenantId ?? 'global');

        return Cache::remember($cacheKey, 300, function () use ($ipAddress, $tenantId) {
            return static::where('ip_address', $ipAddress)
                ->where('is_active', true)
                ->where(function ($query) use ($tenantId) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenantId);
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();
        });
    }

    /**
     * Add IP to blacklist
     */
    public static function addToBlacklist(
        string $ipAddress,
        string $reason,
        ?int $tenantId = null,
        ?\DateTime $expiresAt = null,
        int $offenseCount = 1
    ): void {
        $existing = static::where('ip_address', $ipAddress)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existing) {
            $existing->update([
                'offense_count' => $existing->offense_count + 1,
                'reason' => $reason,
                'is_active' => true,
                'expires_at' => $expiresAt,
            ]);
        } else {
            static::create([
                'ip_address' => $ipAddress,
                'tenant_id' => $tenantId,
                'reason' => $reason,
                'offense_count' => $offenseCount,
                'blocked_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);
        }

        // Clear cache
        $cacheKey = "ip_blacklist_{$ipAddress}_".($tenantId ?? 'global');
        Cache::forget($cacheKey);
    }

    /**
     * Remove IP from blacklist
     */
    public static function removeFromBlacklist(string $ipAddress, ?int $tenantId = null): void
    {
        static::where('ip_address', $ipAddress)
            ->where('tenant_id', $tenantId)
            ->update(['is_active' => false]);

        // Clear cache
        $cacheKey = "ip_blacklist_{$ipAddress}_".($tenantId ?? 'global');
        Cache::forget($cacheKey);
    }
}
