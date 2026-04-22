<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Models;

use App\Models\Concerns\HasCrossDatabaseUserRelations;
use App\Models\Traits\HasInternalVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockMovement extends Model
{
    use HasFactory;
    use LogsActivity;
    use HasInternalVerification;
    use HasCrossDatabaseUserRelations;

    // Use tenant connection in database-per-tenant architecture
    protected $connection = 'tenant';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'movement_reason_id',
        'type',
        'quantity',
        'reason',
        'reference',
        'user_name',
        'previous_stock',
        'new_stock',
        'document_number',
        'verification_hash',
        'verification_generated_at',
        'supplier',
        'invoice_reference',
        'batch_number',
        'expiry_date',
        'additional_notes',
        'authorized_by',
        'authorized_at',
        'pdf_format',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'verification_generated_at' => 'datetime',
        'expiry_date' => 'date',
        'authorized_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movementReason(): BelongsTo
    {
        return $this->belongsTo(MovementReason::class);
    }

    // Tipos de movimiento simplificados
    public const TYPES = [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
    ];
    
    // Razones predefinidas para entradas
    public const ENTRY_REASONS = [
        'compra' => 'Compra a Proveedor',
        'devolucion_cliente' => 'Devolución de Cliente',
        'ajuste_inventario' => 'Ajuste de Inventario (Aumento)',
        'produccion' => 'Producción Interna',
        'otro' => 'Otro',
    ];
    
    // Razones predefinidas para salidas
    public const EXIT_REASONS = [
        'venta' => 'Venta',
        'producto_danado' => 'Producto Dañado',
        'uso_interno' => 'Uso Interno',
        'devolucion_proveedor' => 'Devolución a Proveedor',
        'ajuste_inventario' => 'Ajuste de Inventario (Disminución)',
        'otro' => 'Otro',
    ];

    /**
     * DEPRECATED: Use $stockMovement->authorizedBy attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('authorizedBy') provided by HasCrossDatabaseUserRelations trait
     */
    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
    
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'movement_reason_id', 'type', 'quantity', 'reason', 'reference', 'previous_stock', 'new_stock', 'document_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Generar número de documento único
     */
    public function generateDocumentNumber(): string
    {
        $prefix = $this->type === 'entrada' ? 'ENT' : 'SAL';
        $date = now()->format('Ymd');

        // Buscar el último número usado para este tipo y fecha
        $lastMovement = self::where('type', $this->type)
            ->where('document_number', 'LIKE', "{$prefix}-{$date}-%")
            ->orderBy('document_number', 'desc')
            ->first();

        if ($lastMovement) {
            // Extraer el número secuencial del último documento
            $lastNumber = (int) substr($lastMovement->document_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // Primer documento del día para este tipo
            $nextNumber = 1;
        }

        return "{$prefix}-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Implementar método abstracto del trait
     */
    public function getDocumentName(): string
    {
        $typeName = $this->type === 'entrada' ? 'Entrada' : 'Salida';
        return "Comprobante de {$typeName} de Mercadería - {$this->document_number}";
    }
    
    /**
     * Implementar método abstracto del trait
     */
    public function getVerificationPermission(): string
    {
        return 'inventory.verify_movements';
    }
    
    /**
     * Generar PDF del movimiento
     */
    public function generatePdf(): \Barryvdh\DomPDF\PDF
    {
        $format = $this->pdf_format ?? 'a4';
        $type = $this->type;
        
        // Determinar vista según tipo y formato
        $viewName = "pdf.stock-movements.{$type}-{$format}";
        
        $tenant = \Spatie\Multitenancy\Models\Tenant::current();
        
        // Generar QR code con manejo de errores
        try {
            $qrCode = $this->getInternalVerificationQRCode();
        } catch (\Exception $e) {
            \Log::warning('Error generando QR code para PDF', [
                'movement_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            $qrCode = null;
        }
        
        // Sanitizar datos para evitar problemas de UTF-8
        $movement = clone $this;
        $movement->reason = mb_convert_encoding($movement->reason ?? '', 'UTF-8', 'UTF-8');
        $movement->supplier = mb_convert_encoding($movement->supplier ?? '', 'UTF-8', 'UTF-8');
        $movement->invoice_reference = mb_convert_encoding($movement->invoice_reference ?? '', 'UTF-8', 'UTF-8');
        $movement->additional_notes = mb_convert_encoding($movement->additional_notes ?? '', 'UTF-8', 'UTF-8');
        $movement->user_name = mb_convert_encoding($movement->user_name ?? '', 'UTF-8', 'UTF-8');
        
        $product = $this->product;
        if ($product) {
            $product->name = mb_convert_encoding($product->name ?? '', 'UTF-8', 'UTF-8');
            $product->description = mb_convert_encoding($product->description ?? '', 'UTF-8', 'UTF-8');
        }
        
        $data = [
            'movement' => $movement,
            'product' => $product,
            'authorizedBy' => $this->authorizedBy,
            'tenant' => $tenant,
            'qrCode' => $qrCode,
            'verificationUrl' => $this->getInternalVerificationRoute(),
        ];
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, $data);
        
        // Configurar tamaño de papel según formato
        if ($format === 'thermal') {
            $pdf->setPaper([0, 0, 226.77, 708.66], 'portrait'); // 80mm x 250mm
        } else {
            $pdf->setPaper('a4', 'portrait');
        }
        
        return $pdf;
    }
    
    /**
     * Descargar PDF del movimiento
     */
    public function downloadPdf()
    {
        $pdf = $this->generatePdf();
        $fileName = "movimiento-{$this->type}-{$this->document_number}.pdf";
        
        return $pdf->download($fileName);
    }
    
    /**
     * Streamear PDF del movimiento
     */
    public function streamPdf()
    {
        $pdf = $this->generatePdf();
        $fileName = "movimiento-{$this->type}-{$this->document_number}.pdf";
        
        return $pdf->stream($fileName);
    }
    
    /**
     * Scopes para filtrar por tipo
     */
    public function scopeEntries($query)
    {
        return $query->where('type', 'entrada');
    }
    
    public function scopeExits($query)
    {
        return $query->where('type', 'salida');
    }
    
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
