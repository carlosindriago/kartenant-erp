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

use App\Models\Traits\HasInternalVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatusChange extends Model
{
    use HasInternalVerification;

    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'changed_by',
        'changed_at',
        'verification_hash',
        'verification_generated_at',
        'document_number',
        'additional_notes',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'verification_generated_at' => 'datetime',
        ];
    }

    /**
     * Usuario afectado por el cambio de estado
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuario que realizó el cambio
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope para obtener solo desactivaciones
     */
    public function scopeDeactivations($query)
    {
        return $query->where('action', 'deactivated');
    }

    /**
     * Scope para obtener solo activaciones
     */
    public function scopeActivations($query)
    {
        return $query->where('action', 'activated');
    }

    /**
     * Scope para obtener solo registros (creaciones de usuario)
     */
    public function scopeRegistrations($query)
    {
        return $query->where('action', 'registered');
    }

    /**
     * Generar número de documento único
     */
    public function generateDocumentNumber(): string
    {
        $prefix = match ($this->action) {
            'registered' => 'REG',
            'activated' => 'ACT',
            'deactivated' => 'DES',
            default => 'USR',
        };

        $date = $this->changed_at ? $this->changed_at->format('Ymd') : now()->format('Ymd');
        $sequential = str_pad((string) ($this->id ?? 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequential}";
    }

    /**
     * Obtener el tipo de evento en formato legible
     */
    public function getEventTypeAttribute(): string
    {
        return match ($this->action) {
            'registered' => 'Alta de Empleado',
            'activated' => 'Reactivación de Empleado',
            'deactivated' => 'Desactivación de Empleado',
            default => 'Cambio de Estado',
        };
    }

    /**
     * Generar PDF del evento
     */
    public function generatePdf(): \Barryvdh\DomPDF\PDF
    {
        $tenant = \Spatie\Multitenancy\Models\Tenant::current();

        if (! $tenant) {
            throw new \Exception('No hay tenant actual para generar el PDF');
        }

        $view = match ($this->action) {
            'registered' => 'pdf.employee-events.registration-a4',
            'deactivated' => 'pdf.employee-events.deactivation-a4',
            'activated' => 'pdf.employee-events.activation-a4',
            default => 'pdf.employee-events.generic-a4',
        };

        return \PDF::loadView($view, [
            'event' => $this,
            'user' => $this->user,
            'changedBy' => $this->changedBy,
            'tenant' => $tenant,
            'qrCode' => $this->getQrCodeDataUri(),
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ]);
    }

    /**
     * Implementación del método abstracto del trait HasInternalVerification
     * Retorna el nombre descriptivo del documento para verificación
     */
    public function getDocumentName(): string
    {
        return match ($this->action) {
            'registered' => "Alta de Empleado - {$this->user->name}",
            'deactivated' => "Desactivación de Empleado - {$this->user->name}",
            'activated' => "Reactivación de Empleado - {$this->user->name}",
            default => "Evento de Empleado - {$this->user->name}",
        };
    }

    /**
     * Implementación del método abstracto del trait HasInternalVerification
     * Retorna el permiso necesario para verificar este tipo de documento
     */
    public function getVerificationPermission(): string
    {
        return 'verify_employee_documents';
    }
}
