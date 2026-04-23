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
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentVerification extends Model
{
    /**
     * Conexión a base de datos landlord
     */
    protected $connection = 'landlord';

    protected $fillable = [
        'hash',
        'document_type',
        'tenant_id',
        'generated_by',
        'generated_at',
        'metadata',
        'verification_count',
        'last_verified_at',
        'expires_at',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
            'is_valid' => 'boolean',
            'verification_count' => 'integer',
        ];
    }

    /**
     * Tenant que generó el documento
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Usuario que generó el documento
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'generated_by');
    }

    /**
     * Logs de verificación del documento
     */
    public function verificationLogs(): HasMany
    {
        return $this->hasMany(\App\Models\DocumentVerificationLog::class, 'verification_id');
    }

    /**
     * Scope: Solo documentos válidos
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope: Filtrar por tipo de documento
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope: Documentos no expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Verifica el documento y registra la verificación
     */
    public function verify(?string $ipAddress = null, ?string $userAgent = null): array
    {
        // Verificar si es válido
        if (! $this->is_valid) {
            $result = 'invalid';
            $message = 'Documento invalidado manualmente';
        } elseif ($this->expires_at && $this->expires_at->isPast()) {
            $result = 'expired';
            $message = 'Documento expirado';
        } else {
            $result = 'valid';
            $message = 'Documento legítimo';
        }

        // Incrementar contador
        $this->increment('verification_count');
        $this->update(['last_verified_at' => now()]);

        // Registrar en log
        $this->verificationLogs()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'verified_at' => now(),
            'result' => $result,
        ]);

        return [
            'result' => $result,
            'message' => $message,
            'verification' => $this,
        ];
    }

    /**
     * Invalida el documento manualmente
     */
    public function invalidate(?string $reason = null): void
    {
        $this->update([
            'is_valid' => false,
            'metadata' => array_merge($this->metadata ?? [], [
                'invalidated_at' => now()->toDateTimeString(),
                'invalidation_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Obtiene metadata sanitizada (sin datos sensibles)
     */
    public function getSanitizedMetadata(): array
    {
        $metadata = $this->metadata ?? [];

        // Remover datos sensibles
        $sensitive_keys = [
            'client_name',
            'client_email',
            'client_phone',
            'client_address',
            'amounts',
            'totals',
            'prices',
            'user_email',
        ];

        foreach ($sensitive_keys as $key) {
            unset($metadata[$key]);
        }

        return $metadata;
    }

    /**
     * Verifica si el documento está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica si el documento es válido (no expirado y no invalidado)
     */
    public function isCurrentlyValid(): bool
    {
        return $this->is_valid && ! $this->isExpired();
    }
}
