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

class DocumentVerificationLog extends Model
{
    /**
     * Conexión a base de datos landlord
     */
    protected $connection = 'landlord';
    
    protected $fillable = [
        'verification_id',
        'ip_address',
        'user_agent',
        'verified_at',
        'result',
    ];
    
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }
    
    /**
     * Verificación asociada
     */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocumentVerification::class, 'verification_id');
    }
    
    /**
     * Scope: Filtrar por resultado
     */
    public function scopeByResult($query, string $result)
    {
        return $query->where('result', $result);
    }
    
    /**
     * Scope: Solo verificaciones válidas
     */
    public function scopeValid($query)
    {
        return $query->where('result', 'valid');
    }
    
    /**
     * Scope: Solo verificaciones inválidas
     */
    public function scopeInvalid($query)
    {
        return $query->where('result', 'invalid');
    }
    
    /**
     * Scope: Verificaciones recientes (últimas 24 horas)
     */
    public function scopeRecent($query)
    {
        return $query->where('verified_at', '>=', now()->subDay());
    }
}
