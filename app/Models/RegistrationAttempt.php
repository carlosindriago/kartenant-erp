<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationAttempt extends Model
{
    protected $connection = 'landlord';
    protected $table = 'registration_attempts';
    
    protected $fillable = [
        'ip_address',
        'email',
        'domain_attempted',
        'attempt_count',
        'last_attempt_at',
        'user_agent',
        'captcha_passed',
        'captcha_score',
        'blocked_reason',
    ];
    
    protected $casts = [
        'last_attempt_at' => 'datetime',
        'captcha_passed' => 'boolean',
        'captcha_score' => 'decimal:2',
    ];
    
    public static function recordAttempt(
        string $ip,
        ?string $email = null,
        ?string $domain = null,
        bool $captchaPassed = false,
        ?float $captchaScore = null
    ): self {
        $attempt = self::firstOrNew(['ip_address' => $ip]);
        $attempt->email = $email;
        $attempt->domain_attempted = $domain;
        $attempt->attempt_count = ($attempt->attempt_count ?? 0) + 1;
        $attempt->last_attempt_at = now();
        $attempt->user_agent = request()->userAgent();
        $attempt->captcha_passed = $captchaPassed;
        $attempt->captcha_score = $captchaScore;
        $attempt->save();
        
        return $attempt;
    }
    
    public static function getRecentAttempts(string $ip, int $minutes = 60): int
    {
        $record = self::where('ip_address', $ip)
            ->where('last_attempt_at', '>=', now()->subMinutes($minutes))
            ->first();
            
        return $record->attempt_count ?? 0;
    }
}
