<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialIpTracking extends Model
{
    protected $connection = 'landlord';
    protected $table = 'trial_ip_tracking';
    
    protected $fillable = [
        'ip_address',
        'tenant_id',
        'trial_started_at',
        'trial_ends_at',
        'status',
    ];
    
    protected $casts = [
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->trial_ends_at->isFuture();
    }
    
    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->trial_ends_at->isPast();
    }
    
    public static function hasUsedTrial(string $ip): bool
    {
        return self::where('ip_address', $ip)->exists();
    }
    
    public static function getByIp(string $ip): ?self
    {
        return self::where('ip_address', $ip)->first();
    }
}
