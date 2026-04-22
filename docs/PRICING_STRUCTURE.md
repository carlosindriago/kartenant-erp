# 📊 Estructura de Precios e Impuestos - Kartenant

## 🎯 Filosofía: "Precio Base + IVA = Precio Final"

### Principio Fundamental

**Ernesto ingresa el PRECIO BASE** (el dinero que es para su negocio).
**El sistema calcula y agrega el IVA** (el dinero que es para la AFIP).

---

## 📐 Estructura de Datos

### Campo `products.price`
```sql
price DECIMAL(10,2) NOT NULL -- ✅ PRECIO BASE (SIN IMPUESTOS)
```

**Ejemplo Real:**
- Ernesto quiere ganar $100 por un producto
- Ingresa en el sistema: `price = 100.00`
- El producto tiene IVA 21% asociado

**Cálculos Automáticos del Sistema:**
```
Precio Base (lo que ingresa Ernesto): $100.00
IVA 21%: $100.00 × 0.21 = $21.00
Precio Final (lo que paga el cliente): $121.00
```

---

## 🔄 Flujo Completo

### 1️⃣ Creación de Producto (Filament)

**Formulario que ve Ernesto:**
```
┌─────────────────────────────────────┐
│ Nombre: Arroz 1Kg                   │
│ Código: PRO-001                     │
│ Precio de Venta: $100.00            │ ← Precio BASE
│ Impuesto: IVA 21%                   │ ← Selección
│ Stock: 50                           │
└─────────────────────────────────────┘
```

**Lo que se guarda en BD:**
```php
[
    'name' => 'Arroz 1Kg',
    'price' => 100.00,      // ✅ Precio BASE
    'tax_id' => 1,          // ✅ Relación con Tax (IVA 21%)
    'stock' => 50
]
```

---

### 2️⃣ Visualización en POS

**Card del Producto:**
```
┌───────────────────────┐
│   [Imagen Producto]   │
│                       │
│   Arroz 1Kg          │
│   Código: PRO-001    │
│                       │
│   $121.00            │ ← Precio FINAL (destacado)
│   Base: $100 + IVA 21% │ ← Info adicional
└───────────────────────┘
```

**Implementación (Modelo Product):**
```php
// Accessors Calculados
public function getTaxAmountAttribute(): float
{
    return $this->price * ($this->tax->rate / 100);
    // $100 × 0.21 = $21.00
}

public function getFinalPriceAttribute(): float
{
    return $this->price + $this->tax_amount;
    // $100 + $21 = $121.00
}
```

---

### 3️⃣ Item en Carrito

**Visualización:**
```
┌─────────────────────────────────────────┐
│ 🖼️  Arroz 1Kg                           │
│    $121.00 c/u (base $100.00)           │
│    [−]  2  [+]  🗑️                      │
│                             $242.00     │
│                        + $42.00 IVA     │
└─────────────────────────────────────────┘
```

**Cálculo por Línea:**
```
Cantidad: 2
Precio Base c/u: $100.00
Subtotal Línea: $100 × 2 = $200.00
IVA Línea: $200 × 0.21 = $42.00
Total Línea: $242.00
```

---

### 4️⃣ Resumen del Carrito

**Panel Derecho del POS:**
```
┌─────────────────────────────────┐
│ 🛒 Carrito (2 items)            │
│                                 │
│ Subtotal (Neto):     $200.00   │
│ IVA:                  $42.00   │
│ ─────────────────────────────  │
│ Total a Cobrar:      $242.00   │ ← En verde, destacado
│                                 │
│ [Procesar Pago F12] 💳         │
└─────────────────────────────────┘
```

**Implementación (PointOfSale.php):**
```php
public function calculateTotal()
{
    // 1. Calcular Subtotal (Neto Gravado)
    $this->subtotal = collect($this->cart)->sum(
        fn($item) => $item['product']->price * $item['qty']
    );
    // Resultado: $200.00
    
    // 2. Calcular IVA Total
    $this->taxAmount = collect($this->cart)->sum(function($item) {
        $lineSubtotal = $item['product']->price * $item['qty'];
        
        if ($item['product']->tax && $item['product']->tax->rate > 0) {
            return $lineSubtotal * ($item['product']->tax->rate / 100);
        }
        
        return 0;
    });
    // Resultado: $42.00
    
    // 3. Calcular Total Final
    $this->total = $this->subtotal + $this->taxAmount;
    // Resultado: $242.00
}
```

---

### 5️⃣ Comprobante de Venta

**Ticket Impreso / PDF:**
```
════════════════════════════════════
    FERRETERÍA ERNESTO S.A.
    CUIT: 20-12345678-9
════════════════════════════════════

Fecha: 10/10/2025 21:45
Ticket: FAC-20251010-0001

────────────────────────────────────
DETALLE
────────────────────────────────────
Arroz 1Kg × 2
Precio Base: $100.00          $200.00
IVA 21%:                       $42.00

────────────────────────────────────
Subtotal (Neto Gravado):      $200.00
IVA 21%:                       $42.00
════════════════════════════════════
TOTAL:                        $242.00
════════════════════════════════════

Método de Pago: Efectivo
Recibido: $250.00
Cambio: $8.00

¡Gracias por su compra!
```

---

## 🗄️ Almacenamiento en Base de Datos

### Tabla `sales`
```sql
INSERT INTO sales (
    subtotal,      -- $200.00 ← Neto Gravado (sin IVA)
    tax_amount,    -- $42.00  ← IVA calculado
    total,         -- $242.00 ← Total Final (subtotal + IVA)
    ...
) VALUES (200.00, 42.00, 242.00, ...);
```

### Tabla `sale_items`
```sql
INSERT INTO sale_items (
    product_id,
    quantity,      -- 2
    unit_price,    -- $100.00 ← Precio BASE del producto
    ...
) VALUES (1, 2, 100.00, ...);
```

**Nota Crítica:** 
`unit_price` guarda el precio BASE en el momento de la venta para mantener historial preciso aunque el producto cambie de precio después.

---

## 📊 Reportes Contables

### Libro IVA Ventas (Requerido por AFIP)
```php
$sales = Sale::whereBetween('created_at', [$start, $end])->get();

$report = [
    'neto_gravado' => $sales->sum('subtotal'),    // $200.00
    'iva_debito'   => $sales->sum('tax_amount'),  // $42.00
    'total'        => $sales->sum('total'),       // $242.00
];
```

Con esta estructura, Ernesto puede exportar a Excel y presentar declaración jurada sin dolor.

---

## ⚠️ Advertencias Importantes

### ❌ NO HACER: Ingresar Precio Final y Calcular Base
```php
// ❌ MAL: División propensa a errores de redondeo
$precioFinal = 121.00;
$precioBase = $precioFinal / 1.21;  // 99.99999... ← Problemas
```

**Problemas:**
1. **Errores de redondeo** acumulativos
2. **Pérdida de precisión** en cálculos
3. **Inconsistencias contables** a largo plazo
4. **Complicado para auditorías**

### ✅ HACER: Ingresar Precio Base y Multiplicar
```php
// ✅ BIEN: Multiplicación precisa
$precioBase = 100.00;
$iva = $precioBase * 0.21;          // 21.00 exacto
$precioFinal = $precioBase + $iva;  // 121.00 exacto
```

**Beneficios:**
1. ✅ **Matemática exacta** sin redondeo
2. ✅ **Claridad contable** total
3. ✅ **Control de márgenes** directo
4. ✅ **Cumplimiento fiscal** perfecto

---

## 🎓 Educando a Ernesto

### Conversación de Onboarding
```
Sistema: "Hola Ernesto, ¿cuánto querés ganar por este producto?"
Ernesto: "$100"

Sistema: "Perfecto. Este producto tiene IVA 21%, así que el cliente pagará $121."
         "Tu ganancia: $100 ✅"
         "Para AFIP: $21 📋"

Ernesto: "¡Ah! Súper claro."
```

### Tooltip en Formulario
```html
<input type="number" name="price" />
<small class="text-gray-500">
    💡 Ingresá tu precio de venta (sin IVA). 
    El sistema calculará automáticamente el precio final que pagarán tus clientes.
</small>
```

---

## 🧮 Casos de Uso Múltiples

### Caso 1: Producto con IVA 21%
```
Precio Base: $1,000.00
IVA 21%: $210.00
Precio Final: $1,210.00
```

### Caso 2: Producto con IVA 10.5%
```
Precio Base: $500.00
IVA 10.5%: $52.50
Precio Final: $552.50
```

### Caso 3: Producto Exento de IVA
```
Precio Base: $200.00
IVA: $0.00
Precio Final: $200.00
```

### Caso 4: Venta Mixta
```
Item A (IVA 21%): $100 × 2 = $200 → IVA $42.00
Item B (Sin IVA): $50 × 1 = $50 → IVA $0.00
Item C (IVA 10.5%): $80 × 3 = $240 → IVA $25.20

───────────────────────────────────────────────
Subtotal (Neto): $490.00
IVA Total: $67.20
TOTAL: $557.20
```

---

## 🚀 Implementación Técnica

### Modelo Product (Accessors)
```php
// app/Modules/Inventory/Models/Product.php

public function getTaxAmountAttribute(): float
{
    if (!$this->tax || $this->tax->rate <= 0) {
        return 0;
    }
    return round($this->price * ($this->tax->rate / 100), 2);
}

public function getFinalPriceAttribute(): float
{
    return round($this->price + $this->tax_amount, 2);
}
```

### Componente POS (Cálculo Total)
```php
// app/Livewire/POS/PointOfSale.php

public function calculateTotal()
{
    // Subtotal (Neto Gravado)
    $this->subtotal = collect($this->cart)->sum(
        fn($item) => $item['product']->price * $item['qty']
    );
    
    // IVA Total
    $this->taxAmount = collect($this->cart)->sum(function($item) {
        $lineSubtotal = $item['product']->price * $item['qty'];
        
        if ($item['product']->tax && $item['product']->tax->rate > 0) {
            return $lineSubtotal * ($item['product']->tax->rate / 100);
        }
        
        return 0;
    });
    
    // Total Final
    $this->total = $this->subtotal + $this->taxAmount;
}
```

---

## ✅ Checklist de Cumplimiento

- [x] `products.price` almacena precio BASE
- [x] Sistema calcula IVA automáticamente
- [x] Precio final visible en POS
- [x] Desglose claro en carrito
- [x] `sales.subtotal` = Neto Gravado
- [x] `sales.tax_amount` = IVA calculado
- [x] `sales.total` = Subtotal + IVA
- [x] Reportes IVA exportables
- [x] Cumplimiento AFIP Argentina

---

## 🎉 Resultado Final

**Para Ernesto:**
- ✅ Ingresa el precio que quiere ganar ($100)
- ✅ El sistema se encarga del IVA automáticamente
- ✅ Ve claramente cuánto es para él y cuánto para AFIP
- ✅ Tiene reportes listos para su contador
- ✅ Duerme tranquilo sabiendo que está todo bien

**Para el Sistema:**
- ✅ Matemática precisa sin errores de redondeo
- ✅ Contabilidad robusta y auditable
- ✅ Cumplimiento fiscal automático
- ✅ Escalable a múltiples tasas de IVA
- ✅ Base sólida para futuras funcionalidades

---

**Kartenant: Simple para Ernesto, Robusto por Dentro** 💪
