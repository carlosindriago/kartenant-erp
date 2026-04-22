# 🔧 Resumen Técnico - Sistema de Devoluciones

## Stack Tecnológico

- **Framework:** Laravel 12.x
- **Frontend:** Livewire 3.x + Alpine.js
- **Admin Panel:** FilamentPHP 3.x
- **PDF Generation:** DomPDF
- **Database:** PostgreSQL (Tenant-aware)
- **Logs:** Laravel Log (Monolog)

---

## Estructura de Archivos

```
app/
├── Livewire/POS/
│   └── PointOfSale.php              # Componente principal POS
│
├── Modules/POS/
│   ├── Models/
│   │   ├── Sale.php                 # Modelo venta (actualizado)
│   │   ├── SaleReturn.php           # Modelo nota de crédito
│   │   └── SaleReturnItem.php       # Items devueltos
│   │
│   ├── Services/
│   │   └── ReturnService.php        # Lógica de devoluciones
│   │
│   └── Resources/
│       ├── SaleResource.php         # CRUD ventas
│       └── SaleReturnResource.php   # CRUD devoluciones
│
├── Http/Controllers/Tenant/
│   └── CreditNoteController.php     # PDFs de NCR
│
database/migrations/tenant/
├── 2025_10_11_173703_create_sale_returns_table.php
└── 2025_10_11_173714_create_sale_return_items_table.php

resources/views/
├── livewire/p-o-s/partials/
│   └── cancel-confirmation-modal.blade.php  # Modal confirmación
│
└── pdf/
    └── credit-note.blade.php               # Template PDF NCR

routes/
└── web.php                                  # Rutas NCR
```

---

## Base de Datos

### Tabla: `sale_returns`

```sql
CREATE TABLE sale_returns (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    original_sale_id BIGINT REFERENCES sales(id) ON DELETE RESTRICT,
    return_number VARCHAR UNIQUE,         -- NCR-YYYYMMDD-XXXX
    status VARCHAR CHECK (status IN ('pending', 'completed', 'cancelled')),
    return_type VARCHAR CHECK (return_type IN ('full', 'partial')),
    reason TEXT,
    subtotal DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    total DECIMAL(10,2),
    refund_method VARCHAR CHECK (refund_method IN ('cash', 'card', 'transfer', 'credit_note')),
    processed_by_user_id BIGINT,
    processed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_tenant_status_date (tenant_id, status, created_at),
    INDEX idx_original_sale (original_sale_id)
);
```

### Tabla: `sale_return_items`

```sql
CREATE TABLE sale_return_items (
    id BIGSERIAL PRIMARY KEY,
    sale_return_id BIGINT REFERENCES sale_returns(id) ON DELETE CASCADE,
    original_sale_item_id BIGINT REFERENCES sale_items(id) ON DELETE RESTRICT,
    product_id BIGINT REFERENCES products(id) ON DELETE RESTRICT,
    product_name VARCHAR,
    quantity INTEGER,
    unit_price DECIMAL(10,2),
    tax_rate DECIMAL(5,2),
    line_total DECIMAL(10,2),
    return_reason TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_sale_return (sale_return_id),
    INDEX idx_product (product_id)
);
```

---

## API Endpoints

### Rutas POS (Web)

```php
// Anulación rápida desde POS
POST /livewire/update
Body: {
    "calls": [
        {
            "method": "openCancelConfirmation"  // Abrir modal
        },
        {
            "method": "confirmCancelSale"        // Confirmar con password
        }
    ]
}

// Descargar Nota de Crédito
GET /pos/credit-note/{saleReturn}/pdf
GET /pos/credit-note/{saleReturn}/view
```

### Rutas Filament (Admin)

```php
// Gestionar devolución desde SaleResource
POST /app/pos/sales/{sale}/manage-return
Body: {
    "general_reason": "...",
    "refund_method": "cash",
    "items": {
        "1": {"quantity": 2, "reason": "..."},
        "2": {"quantity": 1, "reason": "..."}
    }
}
```

---

## Flujo de Datos

### Anulación desde POS

```
Usuario presiona "Anular Venta"
    ↓
openCancelConfirmation()
    ├─ Valida tiempo (< 5 min)
    ├─ Carga venta con relaciones
    ├─ Log: "Modal abierto"
    └─ Abre modal
    ↓
Usuario ingresa contraseña
    ↓
confirmCancelSale()
    ├─ Valida contraseña con Hash::check()
    ├─ Log: "Inicio anulación" (si OK) / "Intento fallido" (si error)
    ├─ Llama ReturnService::quickCancelLastSale()
    │   ├─ Log: "Quick cancel iniciado"
    │   ├─ Llama recordReturn()
    │   │   ├─ DB Transaction START
    │   │   ├─ Crea SaleReturn
    │   │   ├─ Crea SaleReturnItems
    │   │   ├─ Crea StockMovements (type: entrada)
    │   │   ├─ Actualiza Product.stock
    │   │   ├─ Log: "Devolución procesada"
    │   │   └─ DB Transaction COMMIT
    │   └─ Return SaleReturn
    ├─ Log: "Anulación completada exitosamente"
    └─ Notificación al usuario
```

---

## Logs de Auditoría

### Estructura de Log

```php
\Log::info('🎬 INICIANDO ANULACIÓN DE VENTA', [
    'sale_id' => 42,
    'invoice_number' => 'FAC-20251011-0042',
    'total' => 1500.00,
    'items_count' => 3,
    'authorized_by_user_id' => 5,
    'authorized_by_user_name' => 'Carlos Admin',
    'authorized_by_user_email' => 'carlos@kartenant.com',
    'ip_address' => '192.168.1.50',
    'timestamp' => '2025-10-11 14:05:20',
    'customer' => 'Juan Pérez',
    'original_cashier' => 'María Gómez',
]);
```

### Niveles de Log

| Emoji | Nivel | Severidad | Descripción |
|-------|-------|-----------|-------------|
| 🔍 | INFO | LOW | Apertura de modal |
| ⏰ | WARNING | MEDIUM | Intento fuera de tiempo |
| ⚠️ | WARNING | MEDIUM | Sin contraseña |
| 🚨 | WARNING | HIGH | Contraseña incorrecta |
| 🎬 | INFO | MEDIUM | Inicio autorizado |
| 📦 | INFO | MEDIUM | Devolución procesada |
| ✅ | INFO | LOW | Completado exitosamente |
| ❌ | ERROR | CRITICAL | Error en proceso |

---

## Seguridad

### Autenticación

```php
// Verificación de contraseña
if (!\Hash::check($this->cancelPassword, $user->password)) {
    // Log de intento fallido
    \Log::warning('🚨 INTENTO FALLIDO', [
        'severity' => 'HIGH',
        'user_id' => $user->id,
        'ip' => request()->ip(),
    ]);
    return;
}
```

### Validaciones

1. **Temporal:** Solo < 5 minutos
   ```php
   public function isEligibleForQuickCancel(Sale $sale): bool
   {
       return $sale->created_at->diffInMinutes(now()) < 5;
   }
   ```

2. **Estado:** Solo ventas completadas
   ```php
   ->where('status', 'completed')
   ```

3. **Contraseña:** Hash verificado
   ```php
   \Hash::check($password, $user->password)
   ```

4. **Tenant:** Solo ventas del tenant actual
   ```php
   ->where('tenant_id', Tenant::current()->id)
   ```

---

## Testing

### Unit Tests

```php
// tests/Unit/ReturnServiceTest.php

public function test_can_create_full_return()
{
    $sale = Sale::factory()->create();
    $returnService = app(ReturnService::class);
    
    $saleReturn = $returnService->quickCancelLastSale($sale, 'Test');
    
    $this->assertEquals('full', $saleReturn->return_type);
    $this->assertEquals($sale->total, $saleReturn->total);
}

public function test_password_verification_required()
{
    $this->actingAs($user);
    
    Livewire::test(PointOfSale::class)
        ->set('cancelPassword', 'wrong-password')
        ->call('confirmCancelSale')
        ->assertSet('cancelPasswordError', '❌ Contraseña incorrecta');
}
```

### Integration Tests

```php
// tests/Feature/SaleCancellationTest.php

public function test_complete_cancellation_flow()
{
    $sale = $this->createSaleWithItems();
    
    // Abrir modal
    Livewire::test(PointOfSale::class)
        ->call('openCancelConfirmation')
        ->assertSet('showCancelConfirmationModal', true)
        ->assertSet('saleToCancel', $sale->id);
    
    // Confirmar con contraseña
    Livewire::test(PointOfSale::class)
        ->set('cancelPassword', 'correct-password')
        ->call('confirmCancelSale');
    
    // Verificar resultados
    $this->assertDatabaseHas('sale_returns', [
        'original_sale_id' => $sale->id,
        'status' => 'completed',
    ]);
    
    // Verificar stock actualizado
    foreach ($sale->items as $item) {
        $this->assertEquals(
            $item->product->original_stock + $item->quantity,
            $item->product->fresh()->stock
        );
    }
}
```

---

## Performance

### Optimizaciones

1. **Eager Loading**
   ```php
   $sale = Sale::with(['items.product', 'customer', 'user'])->find($id);
   ```

2. **Database Transaction**
   ```php
   return DB::transaction(function () use ($sale, $items) {
       // Todas las operaciones aquí
   });
   ```

3. **Computed Properties (Livewire)**
   ```php
   #[Computed]
   public function saleToBeCancel()
   {
       // Cache automático
   }
   ```

4. **Indexes**
   ```sql
   INDEX idx_tenant_status_date (tenant_id, status, created_at)
   ```

---

## Deployment

### Migraciones

```bash
# En producción
./vendor/bin/sail artisan migrate --path=database/migrations/tenant

# Rollback si es necesario
./vendor/bin/sail artisan migrate:rollback --step=2
```

### Cache Clear

```bash
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
```

### Logs

```bash
# Rotación de logs
./vendor/bin/sail artisan log:clear

# Monitoreo en tiempo real
tail -f storage/logs/laravel.log | grep "🚨\|📦\|✅"
```

---

## Troubleshooting

### Problema: Modal no abre

**Causa:** JavaScript no cargado  
**Solución:**
```bash
npm run build
php artisan optimize:clear
```

### Problema: Contraseña siempre incorrecta

**Causa:** Hash algorithm mismatch  
**Solución:**
```php
// Verificar config/hashing.php
'driver' => 'bcrypt',
```

### Problema: Stock no se actualiza

**Causa:** Transaction rollback  
**Solución:**
```bash
# Revisar logs
tail -f storage/logs/laravel.log | grep ERROR
```

---

## Monitoring

### Queries a Monitorear

```sql
-- Anulaciones del día
SELECT COUNT(*) FROM sale_returns 
WHERE DATE(created_at) = CURRENT_DATE;

-- Intentos fallidos (revisar logs)
grep "INTENTO FALLIDO" storage/logs/laravel.log | wc -l

-- Tiempo promedio de anulación
SELECT AVG(EXTRACT(EPOCH FROM (processed_at - created_at))) as avg_seconds
FROM sale_returns;
```

---

## Referencias

- [Laravel Logs](https://laravel.com/docs/logging)
- [Livewire](https://livewire.laravel.com)
- [FilamentPHP](https://filamentphp.com)
- [DomPDF](https://github.com/dompdf/dompdf)

---

**Última Actualización:** 2025-10-11  
**Versión:** 1.0  
**Mantenedor:** Carlos Indriago
