<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoneypotSubmission extends Model
{
    protected $connection = 'landlord';

    protected $table = 'honeypot_submissions';

    protected $fillable = [
        'ip_address',
        'honeypot_field',
        'submitted_value',
        'user_agent',
    ];

    public static function recordSubmission(string $field, string $value): self
    {
        $submission = self::create([
            'ip_address' => request()->ip(),
            'honeypot_field' => $field,
            'submitted_value' => $value,
            'user_agent' => request()->userAgent(),
        ]);

        // Auto-block IP that filled honeypot
        BlockedIp::blockIp(
            request()->ip(),
            "Honeypot triggered: filled field '{$field}'",
            'permanent'
        );

        return $submission;
    }
}
