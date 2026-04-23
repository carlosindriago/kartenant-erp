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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegisterClosing extends Model
{
    use HasFactory, HasInternalVerification, SoftDeletes;

    protected $fillable = [
        'closing_number',
        'opening_id',
        'closed_by',
        'closed_at',
        'opening_balance',
        'expected_balance',
        'closing_balance',
        'difference',
        'total_sales',
        'total_cash',
        'total_card',
        'total_other',
        'total_transactions',
        'average_ticket',
        'notes',
        'discrepancy_notes',
        'status',
        'verification_hash',
        'verification_generated_at',
        'pdf_format',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_card' => 'decimal:2',
        'total_other' => 'decimal:2',
        'average_ticket' => 'decimal:2',
        'verification_generated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($closing) {
            if (empty($closing->closing_number)) {
                $closing->closing_number = self::generateClosingNumber();
            }

            $closing->difference = $closing->closing_balance - $closing->expected_balance;

            if (empty($closing->verification_hash)) {
                $closing->generateVerificationHash();
            }
        });

        static::updating(function ($closing) {
            $closing->difference = $closing->closing_balance - $closing->expected_balance;
        });

        static::created(function ($closing) {
            $closing->opening->close();
        });
    }

    public static function generateClosingNumber(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->count() + 1;

        return "CLOSE-{$date}-".str_pad($last, 4, '0', STR_PAD_LEFT);
    }

    public function opening(): BelongsTo
    {
        return $this->belongsTo(CashRegisterOpening::class, 'opening_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function hasDiscrepancy(): bool
    {
        return abs($this->difference) > 0.01;
    }

    public function hasPositiveDiscrepancy(): bool
    {
        return $this->difference > 0.01;
    }

    public function hasNegativeDiscrepancy(): bool
    {
        return $this->difference < -0.01;
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'discrepancy_notes' => $reason,
        ]);
    }

    public function getVerificationPermission(): string
    {
        return 'verify_cash_register_closing';
    }

    /**
     * Genera el PDF del cierre de caja
     */
    public function generatePdf(): \Barryvdh\DomPDF\PDF
    {
        $format = $this->pdf_format ?? 'thermal';

        $viewName = $format === 'thermal'
            ? 'pdf.cash-register.closing-thermal'
            : 'pdf.cash-register.closing-a4';

        $currentTenant = tenant();

        $pdf = Pdf::loadView($viewName, [
            'closing' => $this,
            'opening' => $this->opening,
            'tenant' => $currentTenant,
            'qrCode' => $this->getInternalVerificationQRCode(),
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ]);

        if ($format === 'thermal') {
            $pdf->setPaper([0, 0, 226.77, 708.66], 'portrait');
        }

        return $pdf;
    }

    /**
     * Obtiene el nombre del documento para mostrar
     */
    public function getDocumentName(): string
    {
        return "Cierre de Caja #{$this->closing_number}";
    }

    public function getPdfTitle(): string
    {
        return "Cierre de Caja #{$this->closing_number}";
    }

    public function downloadPdf()
    {
        $format = $this->pdf_format ?? 'thermal';

        $viewName = $format === 'thermal'
            ? 'pdf.cash-register.closing-thermal'
            : 'pdf.cash-register.closing-a4';

        $currentTenant = tenant();

        $pdf = Pdf::loadView($viewName, [
            'closing' => $this,
            'opening' => $this->opening,
            'tenant' => $currentTenant,
            'qrCode' => $this->getInternalVerificationQRCode(),
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ]);

        if ($format === 'thermal') {
            $pdf->setPaper([0, 0, 226.77, 708.66], 'portrait');
        }

        return $pdf->download("cierre-caja-{$this->closing_number}.pdf");
    }

    public function streamPdf()
    {
        $format = $this->pdf_format ?? 'thermal';

        $viewName = $format === 'thermal'
            ? 'pdf.cash-register.closing-thermal'
            : 'pdf.cash-register.closing-a4';

        $currentTenant = tenant();

        $pdf = Pdf::loadView($viewName, [
            'closing' => $this,
            'opening' => $this->opening,
            'tenant' => $currentTenant,
            'qrCode' => $this->getInternalVerificationQRCode(),
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ]);

        if ($format === 'thermal') {
            $pdf->setPaper([0, 0, 226.77, 708.66], 'portrait');
        }

        return $pdf->stream("cierre-caja-{$this->closing_number}.pdf");
    }
}
