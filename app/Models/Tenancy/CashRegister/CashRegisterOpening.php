<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models\Tenancy\CashRegister;

use App\Models\Traits\HasInternalVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegisterOpening extends Model
{
    use HasFactory, SoftDeletes, HasInternalVerification;

    protected $fillable = [
        'opening_number',
        'opened_by',
        'opened_at',
        'opening_balance',
        'notes',
        'status',
        'verification_hash',
        'verification_generated_at',
        'pdf_format',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'verification_generated_at' => 'datetime',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Generar número de apertura automáticamente
        static::creating(function ($opening) {
            if (empty($opening->opening_number)) {
                $opening->opening_number = self::generateOpeningNumber();
            }

            // Generar hash de verificación automáticamente
            if (empty($opening->verification_hash)) {
                $opening->generateVerificationHash();
            }
        });
    }

    /**
     * Genera el número de apertura
     */
    public static function generateOpeningNumber(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->count() + 1;
        return "OPEN-{$date}-" . str_pad($last, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relación con el usuario que abrió la caja
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Relación con el cierre de caja
     */
    public function closing(): HasOne
    {
        return $this->hasOne(CashRegisterClosing::class, 'opening_id');
    }

    /**
     * Verifica si la caja está abierta
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Verifica si la caja está cerrada
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Cierra la caja
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Implementación del método abstracto del trait
     * Define el permiso requerido para verificar este documento
     */
    public function getVerificationPermission(): string
    {
        return 'verify_cash_register_opening';
    }

    /**
     * Obtiene el título para el PDF
     */
    public function getPdfTitle(): string
    {
        return "Apertura de Caja #{$this->opening_number}";
    }

    /**
     * Descarga el PDF de la apertura
     */
    public function downloadPdf()
    {
        $format = $this->pdf_format ?? 'thermal';
        
        $viewName = $format === 'thermal' 
            ? 'pdf.cash-register.opening-thermal' 
            : 'pdf.cash-register.opening-a4';

        $currentTenant = tenant();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, [
            'opening' => $this,
            'tenant' => $currentTenant,
            'qrCode' => $this->getInternalVerificationQRCode(),
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ]);

        if ($format === 'thermal') {
            $pdf->setPaper([0, 0, 226.77, 708.66], 'portrait'); // 80mm x 250mm
        }
        
        return $pdf->download("apertura-caja-{$this->opening_number}.pdf");
    }

    /**
     * Vista previa del PDF
     */
    public function streamPdf()
    {
        $format = $this->pdf_format ?? 'thermal';
        
        $viewName = $format === 'thermal' 
            ? 'pdf.cash-register.opening-thermal' 
            : 'pdf.cash-register.opening-a4';

        $currentTenant = tenant();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, [
            'opening' => $this,
            'tenant' => $currentTenant,
            'qrCode' => $this->getInternalVerificationQRCode(),
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ]);

        if ($format === 'thermal') {
            $pdf->setPaper([0, 0, 226.77, 708.66], 'portrait');
        }
        
        return $pdf->stream("apertura-caja-{$this->opening_number}.pdf");
    }
}
