<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $connection = 'landlord';
    protected $table = 'blocked_ips';
    
    protected $fillable = [
        'ip_address',
        'reason',
        'violation_count',
        'blocked_at',
        'blocked_until',
        'block_type',
    ];
    
    protected $casts = [
        'blocked_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];
    
    public static function isBlocked(string $ip): bool
    {
        $block = self::where('ip_address', $ip)->first();
        
        if (!$block) {
            return false;
        }
        
        // Permanent block
        if ($block->block_type === 'permanent') {
            return true;
        }
        
        // Temporary block - check if still active
        if ($block->blocked_until && $block->blocked_until->isPast()) {
            $block->delete(); // Remove expired block
            return false;
        }
        
        return true;
    }
    
    public static function blockIp(string $ip, string $reason, string $type = 'temporary', ?int $hours = 1): self
    {
        return self::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'violation_count' => \DB::raw('violation_count + 1'),
                'blocked_at' => now(),
                'blocked_until' => $type === 'temporary' ? now()->addHours($hours) : null,
                'block_type' => $type,
            ]
        );
    }
}
