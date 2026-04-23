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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityQuestion extends Model
{
    use HasFactory;

    protected $connection = 'landlord';

    protected $fillable = [
        'question',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function userSecurityAnswers(): HasMany
    {
        return $this->hasMany(UserSecurityAnswer::class);
    }

    public static function getActiveQuestions()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
