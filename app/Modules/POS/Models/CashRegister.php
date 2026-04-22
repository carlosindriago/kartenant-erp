<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Models;

use App\Models\Concerns\HasCrossDatabaseUserRelations;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Traits\HasInternalVerification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CashRegister extends Model
{
    use HasFactory, HasInternalVerification, HasCrossDatabaseUserRelations;
    
    // Use tenant connection in database-per-tenant architecture
    protected $connection = 'tenant';
    
    protected $fillable = [
        'register_number',
        'opened_at',
        'opened_by_user_id',
        'initial_amount',
        'closed_at',
        'closed_by_user_id',
        'expected_amount',
        'actual_amount',
        'difference',
        'cash_breakdown',
        'status',
        'opening_notes',
        'closing_notes',
        'forced_closure',
        'forced_by_user_id',
        'forced_reason',
        'verification_hash',
        'verification_generated_at',
        'pdf_format',
    ];
    
    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'initial_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'cash_breakdown' => 'array',
        'forced_closure' => 'boolean',
        'verification_generated_at' => 'datetime',
    ];
    
    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($cashRegister) {
            if (empty($cashRegister->register_number)) {
                $cashRegister->register_number = self::generateRegisterNumber();
            }
        });
    }
    
    /**
     * Relaciones
     */

    /**
     * DEPRECATED: Use $cashRegister->openedBy attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('openedBy') provided by HasCrossDatabaseUserRelations trait
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /**
     * DEPRECATED: Use $cashRegister->closedBy attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('closedBy') provided by HasCrossDatabaseUserRelations trait
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * DEPRECATED: Use $cashRegister->forcedBy attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('forcedBy') provided by HasCrossDatabaseUserRelations trait
     */
    public function forcedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forced_by_user_id');
    }
    
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
    
    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
    
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
    
    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }
    
    /**
     * Scope para obtener cajas abiertas de un usuario específico
     */
    public function scopeOpenByUser($query, int $userId)
    {
        // Log removido: se llama múltiples veces por request y generaba ruido excesivo

        return $query->where('opened_by_user_id', $userId)
                    ->where('status', 'open');
    }
    
    /**
     * Scope para obtener cajas activas (abiertas)
     */
    public function scopeActiveRegisters($query)
    {
        return $query->where('status', 'open')
                    ->orderBy('opened_at', 'desc');
    }
    
    /**
     * Helpers
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
    
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
    
    /**
     * Calcula el total esperado en caja
     * 
     * IMPORTANTE: Solo se cuentan ventas completadas.
     * Las ventas canceladas NO se restan porque el dinero ya fue devuelto al cliente
     * y no está en caja. Simplemente no se cuentan como ventas.
     */
    public function calculateExpectedAmount(): float
    {
        $cashSales = $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('total');
        
        return $this->initial_amount + $cashSales;
    }
    
    /**
     * Obtiene resumen de ventas del turno
     */
    public function getSalesSummary(): array
    {
        // Obtener estadísticas de ventas completadas
        $totalSales = $this->sales()->where('status', 'completed')->count();
        $totalAmount = $this->sales()->where('status', 'completed')->sum('total');
        
        // Desglose por método de pago (crear query nueva cada vez)
        $cashSales = $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('total');
            
        $cardSales = $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'card')
            ->sum('total');
            
        $transferSales = $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'transfer')
            ->sum('total');
        
        // Ventas canceladas
        $cancelledSales = $this->sales()->where('status', 'cancelled')->count();
        $cashReturns = $this->sales()
            ->where('status', 'cancelled')
            ->where('payment_method', 'cash')
            ->sum('total');
        
        return [
            'total_sales' => $totalSales,
            'total_amount' => $totalAmount,
            'cash_sales' => $cashSales,
            'card_sales' => $cardSales,
            'transfer_sales' => $transferSales,
            'cancelled_sales' => $cancelledSales,
            'cash_returns' => $cashReturns,
        ];
    }
    
    /**
     * Obtiene caja abierta actual del tenant (cualquier usuario)
     */
    public static function getCurrentOpen(): ?self
    {
        // En database-per-tenant, cada BD ya es del tenant actual
        return self::where('status', 'open')
            ->latest('opened_at')
            ->first();
    }
    
    /**
     * Obtiene la caja abierta de un usuario específico
     */
    public static function getUserOpenRegister(?int $userId): ?self
    {
        if (!$userId) {
            return null;
        }
        
        return self::openByUser($userId)->first();
    }
    
    /**
     * Verifica si un usuario tiene una caja abierta
     */
    public static function userHasOpenRegister(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        return self::openByUser($userId)->exists();
    }
    
    /**
     * Verifica si hay alguna caja abierta (cualquier usuario)
     */
    public static function hasOpenRegister(): bool
    {
        return self::getCurrentOpen() !== null;
    }
    
    /**
     * Obtiene todas las cajas actualmente abiertas
     */
    public static function getAllOpenRegisters()
    {
        return self::activeRegisters()->with('openedBy')->get();
    }
    
    /**
     * Cuenta cuántas cajas están abiertas actualmente
     */
    public static function countOpenRegisters(): int
    {
        return self::where('status', 'open')->count();
    }
    
    /**
     * Verifica si este registro pertenece al usuario
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->opened_by_user_id === $userId;
    }
    
    /**
     * Genera número único de registro
     */
    protected static function generateRegisterNumber(): string
    {
        $date = now()->format('Ymd');
        $lastRegister = self::where('register_number', 'like', "REG-{$date}-%")
            ->orderBy('register_number', 'desc')
            ->first();
        
        if ($lastRegister) {
            $lastNumber = (int) substr($lastRegister->register_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('REG-%s-%04d', $date, $newNumber);
    }
    
    /**
     * Métodos requeridos por HasInternalVerification trait
     */
    
    /**
     * Obtiene el permiso requerido para verificar este documento
     */
    public function getVerificationPermission(): string
    {
        // Si está abierta, requiere permiso de apertura
        // Si está cerrada, requiere permiso de cierre
        return $this->status === 'open' 
            ? 'verify_cash_register_opening' 
            : 'verify_cash_register_closing';
    }
    
    /**
     * Genera el PDF del documento
     */
    public function generatePdf(): \Barryvdh\DomPDF\PDF
    {
        $tenant = \Spatie\Multitenancy\Models\Tenant::current();
        
        // Determinar qué vista usar según el estado
        if ($this->status === 'open') {
            $view = $this->pdf_format === 'a4' 
                ? 'pdf.cash-register.opening-a4' 
                : 'pdf.cash-register.opening-thermal';
            
            $pdf = \PDF::loadView($view, [
                'opening' => $this,
                'tenant' => $tenant,
                'qrCode' => $this->getQrCodeDataUri(),
                'verificationUrl' => $this->getInternalVerificationRoute(),
            ]);
            
            // Para formato térmico, usar alto fijo moderado (aperturas son más cortas)
            if ($this->pdf_format === 'thermal') {
                // Apertura típica: header + datos básicos + QR ≈ 180mm con margen de seguridad
                $heightInPoints = 180 * 2.83465; // 510 puntos aprox
                $pdf->setPaper([0, 0, 226.77, $heightInPoints], 'portrait');
            }
            
            return $pdf;
        } else {
            // Para cierres, necesitamos datos adicionales
            $salesSummary = $this->getSalesSummary();
            
            // Obtener todas las transacciones (ventas completadas y canceladas)
            $transactions = $this->sales()
                ->with(['customer', 'user'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($sale) {
                    return [
                        'invoice_number' => $sale->invoice_number,
                        'time' => $sale->created_at->format('H:i'),
                        'customer' => $sale->customer ? $sale->customer->name : 'Público General',
                        'user' => $sale->user ? $sale->user->name : 'N/A',
                        'payment_method' => $sale->payment_method,
                        'status' => $sale->status,
                        'total' => $sale->total,
                        'is_cancelled' => $sale->status === 'cancelled',
                    ];
                })
                ->toArray(); // Convertir a array para las vistas PDF
            
            // Calcular contadores de transacciones (no se pueden usar closures en Blade/PDF)
            $completedCount = 0;
            $cancelledCount = 0;
            foreach ($transactions as $t) {
                if ($t['is_cancelled']) {
                    $cancelledCount++;
                } else {
                    $completedCount++;
                }
            }
            
            $view = $this->pdf_format === 'a4'
                ? 'pdf.cash-register.closing-a4'
                : 'pdf.cash-register.closing-thermal';
            
            // Pasar el modelo CashRegister directamente como $closing
            // ya que tiene todos los métodos y propiedades necesarios
            $pdf = \PDF::loadView($view, [
                'closing' => $this, // El modelo CashRegister actúa como closing
                'opening' => $this, // El modelo también tiene datos de opening
                'tenant' => $tenant,
                'qrCode' => $this->getQrCodeDataUri(),
                'verificationUrl' => $this->getInternalVerificationRoute(),
                // Datos adicionales calculados
                'salesSummary' => $salesSummary,
                'transactions' => $transactions, // Lista de transacciones
                'transactionsCompletedCount' => $completedCount,
                'transactionsCancelledCount' => $cancelledCount,
            ]);
            
            // Para formato térmico, calcular alto dinámicamente según cantidad de contenido
            if ($this->pdf_format === 'thermal') {
                // Alto base (header + totales + footer + QR) ≈ 200mm
                $baseHeight = 200;
                
                // Cada transacción ocupa aproximadamente 15mm
                $transactionHeight = 15;
                $transactionsHeight = count($transactions) * $transactionHeight;
                
                // Agregar margen de seguridad del 25% (evita saltos de página)
                $totalHeight = ($baseHeight + $transactionsHeight) * 1.25;
                
                // Convertir mm a puntos (1mm = 2.83465 puntos)
                $heightInPoints = $totalHeight * 2.83465;
                
                // Aplicar tamaño calculado (80mm ancho, alto dinámico)
                $pdf->setPaper([0, 0, 226.77, $heightInPoints], 'portrait');
            }
            
            return $pdf;
        }
    }
    
    /**
     * Obtiene el nombre del documento para mostrar
     */
    public function getDocumentName(): string
    {
        return $this->status === 'open' 
            ? "Apertura de Caja {$this->register_number}" 
            : "Cierre de Caja {$this->register_number}";
    }
    
    /**
     * Verifica si el documento tiene discrepancia
     */
    public function hasDiscrepancy(): bool
    {
        return abs($this->difference ?? 0) > 0.01;
    }
    
    /**
     * Cache para sales summary (evita múltiples llamadas a la BD)
     */
    protected $_salesSummaryCache = null;
    
    protected function getCachedSalesSummary()
    {
        if ($this->_salesSummaryCache === null) {
            $this->_salesSummaryCache = $this->getSalesSummary();
        }
        return $this->_salesSummaryCache;
    }
    
    /**
     * Accessors para compatibilidad con vistas PDF
     * Estos permiten que las vistas usen nombres alternativos para los campos
     */
    
    public function getClosingNumberAttribute()
    {
        return $this->getAttributeFromArray('register_number');
    }
    
    public function getOpeningBalanceAttribute()
    {
        return $this->getAttributeFromArray('initial_amount') ?? 0;
    }
    
    public function getClosingBalanceAttribute()
    {
        return $this->getAttributeFromArray('actual_amount') ?? 0;
    }
    
    public function getTotalTransactionsAttribute()
    {
        $summary = $this->getCachedSalesSummary();
        return $summary['total_sales'];
    }
    
    public function getAverageTicketAttribute()
    {
        $summary = $this->getCachedSalesSummary();
        return $summary['total_sales'] > 0 
            ? $summary['total_amount'] / $summary['total_sales'] 
            : 0;
    }
    
    public function getTotalSalesAttribute()
    {
        $summary = $this->getCachedSalesSummary();
        return $summary['total_amount'];
    }
    
    public function getTotalCashAttribute()
    {
        $summary = $this->getCachedSalesSummary();
        return $summary['cash_sales'];
    }
    
    public function getTotalCardAttribute()
    {
        $summary = $this->getCachedSalesSummary();
        return $summary['card_sales'];
    }
    
    public function getTotalOtherAttribute()
    {
        $summary = $this->getCachedSalesSummary();
        return $summary['transfer_sales'];
    }
    
    public function getNotesAttribute()
    {
        return $this->getAttributeFromArray('closing_notes');
    }
    
    public function getDiscrepancyNotesAttribute()
    {
        $difference = $this->getAttributeFromArray('difference') ?? 0;
        return abs($difference) > 0.01 
            ? 'Diferencia detectada en cierre de caja' 
            : null;
    }
    
    /**
     * Obtiene el estado de revisión para PDFs
     * En el POS no hay flujo de aprobación, un cierre es simplemente completado
     */
    public function getReviewStatusAttribute()
    {
        $actualStatus = $this->getAttributeFromArray('status');
        
        // Si está cerrado, considerarlo completado
        // Si está abierto, considerarlo en proceso
        return $actualStatus === 'closed' ? 'completed' : 'in_progress';
    }
}
