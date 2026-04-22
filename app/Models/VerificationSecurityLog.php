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

class VerificationSecurityLog extends Model
{
    protected $connection = 'landlord';
    
    public $timestamps = false;
    
    protected $fillable = [
        'ip_address',
        'tenant_id',
        'event_type',
        'details',
        'user_agent',
        'referer',
        'severity',
        'created_at',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'severity' => 'integer',
    ];
    
    // Event types
    const EVENT_RATE_LIMIT = 'rate_limit';
    const EVENT_INVALID_HASH = 'invalid_hash';
    const EVENT_BRUTE_FORCE = 'brute_force';
    const EVENT_BOT_DETECTED = 'bot_detected';
    const EVENT_BLACKLIST_HIT = 'blacklist_hit';
    const EVENT_SUSPICIOUS_PATTERN = 'suspicious_pattern';
    
    // Severity levels
    const SEVERITY_INFO = 1;
    const SEVERITY_WARNING = 2;
    const SEVERITY_ALERT = 3;
    const SEVERITY_CRITICAL = 4;
    const SEVERITY_EMERGENCY = 5;
    
    /**
     * Log a security event
     */
    public static function logEvent(
        string $ipAddress,
        string $eventType,
        ?int $tenantId = null,
        ?string $details = null,
        int $severity = self::SEVERITY_INFO
    ): void {
        static::create([
            'ip_address' => $ipAddress,
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'details' => $details,
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'severity' => $severity,
            'created_at' => now(),
        ]);
    }
}
