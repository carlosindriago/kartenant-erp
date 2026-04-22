# 🛒 Sistema POS (Punto de Venta) - Kartenant

Documentación completa del sistema de Punto de Venta de Kartenant.

## 🎯 Visión General

El POS de Kartenant es un **terminal profesional inmersivo** diseñado específicamente para operaciones de venta minorista. Combina simplicidad de uso con funcionalidades avanzadas, optimizado para tablets y entornos de alta velocidad.

### 💡 Diferencias Competitivas

| Característica | Kartenant | Competidores |
|----------------|-----------------|--------------|
| **Interfaz** | Fullscreen inmersiva | Panel administrativo |
| **Velocidad** | < 30 segundos por venta | 1-2 minutos |
| **Dispositivos** | Tablets optimizadas | PCs tradicionales |
| **Escaneo** | Código de barras nativo | Requiere hardware especial |
| **Devoluciones** | Control antifraude | Básico o ausente |
| **Reportes** | Tiempo real | Batch/diferido |

---

## 🚀 Inicio Rápido

### Acceder al POS

1. **Desde Panel Admin**: Menú → "Punto de Venta"
2. **Se abre automáticamente** en nueva pestaña fullscreen
3. **Login automático** (mantiene sesión del panel)

### Primera Venta

```bash
# Flujo típico:
1. Escanear producto → Beep de confirmación
2. Ver carrito actualizado
3. F12 → Modal de pago
4. Seleccionar método (Efectivo/Tarjeta)
5. Ingresar monto recibido
6. ENTER → Venta completada
```

**Tiempo promedio:** 15-30 segundos

---

## 🎨 Interfaz de Usuario

### Layout Principal

```
┌─────────────────────────────────────────────────────────┐
│ 🏪 KARTENANT DIGITAL POS - Terminal de Venta              │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ┌─────────────┐ ┌─────────────────────────────────────┐ │
│ │ 🛍️ CARRITO  │ │ 🏷️ PRODUCTOS DISPONIBLES             │ │
│ │             │ │                                     │ │
│ │ Item 1      │ │ ┌─────────────────────────────────┐ │ │
│ │ $100.00     │ │ │ 🔍 Buscar producto...          │ │ │
│ │             │ │ └─────────────────────────────────┘ │ │
│ │ Item 2      │ │                                     │ │
│ │ $50.00      │ │ [Producto 1] [Producto 2] [Prod 3] │ │
│ │ ──────────  │ │ [Producto 4] [Producto 5] [Prod 6] │ │
│ │ TOTAL:      │ │                                     │ │
│ │ $150.00     │ │ [Producto 7] [Producto 8] [Prod 9] │ │
│ │             │ │                                     │ │
│ │ [Procesar   │ │ [Producto 10] ...                  │ │
│ │  Pago F12]  │ │                                     │ │
│ └─────────────┘ └─────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│ 💡 F1=Ayuda │ F2=Historial │ ESC=Cerrar │ ENTER=Confirmar │
└─────────────────────────────────────────────────────────┘
```

### Estados Visuales

- **🟢 Verde**: Producto disponible, stock suficiente
- **🟡 Amarillo**: Stock bajo (advertencia)
- **🔴 Rojo**: Sin stock (deshabilitado)
- **🔵 Azul**: Producto seleccionado/focus

### Feedback Sonoro

- **✅ Beep simple**: Producto agregado exitosamente
- **❌ Beep doble**: Error (producto no encontrado)
- **💰 Campanilla**: Venta completada
- **🚨 Alerta**: Error o problema

---

## 🔍 Búsqueda y Selección de Productos

### Métodos de Búsqueda

#### 1. **Búsqueda por Texto**
- **Campo principal** en top bar
- **Búsqueda en tiempo real** (300ms debounce)
- **Campos indexados**: nombre, SKU, código de barras
- **Resultados limitados**: 50 productos máx

#### 2. **Escaneo de Código de Barras**
- **Compatible** con cualquier escáner USB
- **Detección automática** de entrada rápida
- **Formato**: EAN-13, CODE-128, QR codes
- **Feedback inmediato** con sonido

#### 3. **Navegación Visual**
- **Grid responsive** de productos
- **Paginación automática** (24 productos por página)
- **Filtros por categoría**
- **Ordenamiento** por nombre/precio/stock

### Selección de Productos

```javascript
// Lógica de selección
function selectProduct(product) {
    // 1. Validar stock disponible
    if (product.stock <= 0) {
        showError('Producto sin stock');
        playErrorSound();
        return;
    }

    // 2. Agregar al carrito
    addToCart(product, 1);

    // 3. Feedback visual
    highlightProduct(product);
    showToast('Producto agregado', 'success');

    // 4. Feedback sonoro
    playSuccessSound();

    // 5. Actualizar totales
    updateCartTotals();
}
```

---

## 🛒 Gestión del Carrito

### Estructura del Carrito

```javascript
cart = [
    {
        product_id: 1,
        name: "Martillo Stanley",
        sku: "HAM-001",
        quantity: 2,
        unit_price: 1000.00,
        tax_rate: 21.00,
        line_total: 2420.00, // (1000 * 2) * 1.21
        stock_available: 45
    }
]
```

### Operaciones del Carrito

#### Agregar Productos
- **Click en producto** → Agrega 1 unidad
- **Doble click** → Agrega cantidad personalizada
- **Arrastrar y soltar** → Reordenar items

#### Modificar Cantidades
- **Botones + / -** → Cambiar cantidad
- **Input directo** → Escribir cantidad exacta
- **Validación automática** → No permite > stock disponible

#### Eliminar Productos
- **Botón X** por línea
- **Vaciar carrito** (F9 + confirmación)
- **Deshacer** (Ctrl+Z)

### Cálculos Automáticos

```php
// Servicio de cálculo de totales
class CartCalculator
{
    public function calculateTotals(array $cart): array
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($cart as $item) {
            $lineSubtotal = $item['unit_price'] * $item['quantity'];
            $lineTax = $lineSubtotal * ($item['tax_rate'] / 100);

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
            'item_count' => count($cart)
        ];
    }
}
```

---

## 💰 Procesamiento de Pagos

### Modal de Pago

```
┌─────────────────────────────────────┐
│ 💳 PROCESAR PAGO                   │
├─────────────────────────────────────┤
│                                     │
│ Método de Pago: [Efectivo ▼]        │
│                                     │
│ Total a Cobrar: $2,420.00           │
│                                     │
│ Monto Recibido: [ ________ ]        │
│                                     │
│ Cambio: $80.00  ← Calculado auto    │
│                                     │
│ Cliente: [Opcional]                 │
│ Notas: [Opcional]                   │
│                                     │
│ [Cancelar ESC]    [Confirmar ENTER] │
└─────────────────────────────────────┘
```

### Métodos de Pago

#### Efectivo
- **Cálculo automático de cambio**
- **Botones rápidos**: $100, $200, $500, $1000
- **Validación**: Monto recibido ≥ total

#### Tarjeta
- **Campos**: Número, fecha, CVV, nombre
- **Validación básica** de formato
- **Integración futura** con procesadores

#### Transferencia
- **Referencia automática** generada
- **Código QR** para pago móvil
- **Estado**: Pendiente/Confirmado

### Flujo de Confirmación

```php
public function processPayment(array $paymentData): Sale
{
    return DB::transaction(function () use ($paymentData) {
        // 1. Crear venta
        $sale = Sale::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'customer_id' => $paymentData['customer_id'] ?? null,
            'user_id' => auth()->id(),
            'payment_method' => $paymentData['payment_method'],
            'payment_reference' => $paymentData['payment_reference'] ?? null,
            'amount_paid' => $paymentData['amount_paid'] ?? null,
            'change_amount' => $paymentData['change_amount'] ?? 0,
            'subtotal' => $this->cartSubtotal,
            'tax_amount' => $this->cartTaxAmount,
            'total' => $this->cartTotal,
            'status' => 'completed'
        ]);

        // 2. Crear items de venta
        foreach ($this->cart as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'line_total' => $item['line_total']
            ]);

            // 3. Actualizar stock
            $item['product']->decrement('stock', $item['quantity']);
        }

        // 4. Registrar movimiento de caja
        $this->registerCashMovement($sale);

        // 5. Limpiar carrito
        $this->clearCart();

        // 6. Generar comprobante
        $this->generateReceipt($sale);

        return $sale;
    });
}
```

---

## 📱 Atributos de Teclado

### Atajos Globales

| Tecla | Función | Descripción |
|-------|---------|-------------|
| **F1** | Ayuda | Muestra ayuda de teclado |
| **F2** | Historial | Ventas del día |
| **F3** | Buscar | Focus en campo de búsqueda |
| **F9** | Vaciar Carrito | Con confirmación |
| **F12** | Procesar Pago | Abrir modal de pago |
| **ESC** | Cerrar/Cancelar | Cerrar modales |
| **ENTER** | Confirmar | Confirmar acciones |

### Navegación por Teclado

```javascript
// Manejo de eventos de teclado
document.addEventListener('keydown', function(event) {
    switch(event.key) {
        case 'F1':
            event.preventDefault();
            showKeyboardHelp();
            break;

        case 'F2':
            event.preventDefault();
            showTodaySales();
            break;

        case 'F12':
            event.preventDefault();
            if (cart.length > 0) {
                openPaymentModal();
            }
            break;

        case 'Escape':
            closeCurrentModal();
            break;
    }
});
```

---

## 📊 Historial y Reportes

### Historial del Día

**Acceso:** F2 o botón "Historial"

**Información mostrada:**
- Número de factura
- Hora de venta
- Cajero
- Cliente (si aplica)
- Método de pago
- Total
- Estado (Completada/Pendiente/Cancelada)

### Reportes en Tiempo Real

**Dashboard del POS:**
- Ventas del día actual
- Total facturado
- Ticket promedio
- Productos más vendidos
- Método de pago más usado

---

## 🔄 Devoluciones y Cancelaciones

### Cancelación Rápida

**Condiciones:**
- Venta realizada en los últimos 5 minutos
- Mismo cajero que realizó la venta
- Caja registradora aún abierta

**Proceso:**
1. Botón "Anular Venta" (rojo pulsante)
2. Confirmación con contraseña del cajero
3. Ver resumen detallado de la venta
4. Autorización → Devolución automática

### Devolución Formal

**Para ventas > 5 minutos:**
1. Crear "Nota de Crédito" desde panel admin
2. Requiere autorización de supervisor
3. Stock se devuelve después de aprobación
4. Cliente puede usar crédito en futuras compras

---

## 📱 Optimización Móvil

### Diseño Responsive

- **Breakpoints**: 768px, 1024px, 1280px
- **Touch targets**: Mínimo 44px
- **Gestures**: Swipe para navegar productos
- **PWA Ready**: Instalable en pantalla de inicio

### Modo Tablet

```css
/* Estilos específicos para tablets */
@media (min-width: 768px) and (max-width: 1024px) {
    .pos-container {
        font-size: 18px; /* Texto más grande */
    }

    .product-grid {
        grid-template-columns: repeat(4, 1fr); /* Más productos visibles */
    }

    .cart-item {
        padding: 16px; /* Más espacio para touch */
    }
}
```

---

## 🔧 Configuración Avanzada

### Configuración de Caja

```php
// config/pos.php
return [
    'cash_register' => [
        'auto_open' => true,
        'default_balance' => 0,
        'max_difference' => 500.00, // Diferencia máxima permitida
        'require_approval' => true, // Para diferencias > límite
    ],

    'sales' => [
        'max_items' => 50, // Máximo items por venta
        'auto_print' => true,
        'thermal_printer' => 'EPSON TM-T88',
    ],

    'ui' => [
        'theme' => 'dark', // light/dark/auto
        'animations' => true,
        'sounds' => true,
    ]
];
```

### Personalización por Tenant

- **Logo personalizado** en tickets
- **Colores corporativos**
- **Campos adicionales** en ventas
- **Métodos de pago** customizables

---

## 📈 Métricas y Analytics

### KPIs del POS

- **Velocidad de venta**: Tiempo promedio por transacción
- **Tasa de error**: Porcentaje de ventas con problemas
- **Productos por venta**: Promedio de items
- **Método de pago**: Distribución por tipo
- **Horarios pico**: Análisis de demanda

### Reportes Automáticos

```php
// Jobs para reportes diarios
class GenerateDailyPOSReport implements ShouldQueue
{
    public function handle()
    {
        $yesterday = now()->subDay();

        $report = [
            'total_sales' => Sale::whereDate('created_at', $yesterday)->count(),
            'total_revenue' => Sale::whereDate('created_at', $yesterday)->sum('total'),
            'average_ticket' => Sale::whereDate('created_at', $yesterday)->avg('total'),
            'top_products' => // Query compleja
            'payment_methods' => // Agrupado por método
        ];

        // Enviar por email
        // Guardar en base de datos
        // Generar PDF
    }
}
```

---

## 🔌 Integraciones

### Impresoras

#### Tickets Térmicos
```php
// Integración con impresoras ESC/POS
class ThermalPrinter
{
    public function printReceipt(Sale $sale)
    {
        $printer = new EscposPrinter();
        $printer->initialize();

        // Logo
        $printer->graphic($this->getLogoImage());

        // Encabezado
        $printer->text("KARTENANT DIGITAL\n");
        $printer->text("Ticket: {$sale->invoice_number}\n");
        $printer->text(str_repeat("-", 32) . "\n");

        // Items
        foreach ($sale->items as $item) {
            $printer->text(sprintf("%-20s %8.2f\n",
                Str::limit($item->product_name, 20),
                $item->line_total
            ));
        }

        // Totales
        $printer->text(str_repeat("-", 32) . "\n");
        $printer->text(sprintf("TOTAL: $%8.2f\n", $sale->total));

        $printer->cut();
        $printer->close();
    }
}
```

#### Escáneres de Código de Barras

```javascript
// Detección automática de escáner
class BarcodeScanner {
    constructor() {
        this.buffer = '';
        this.lastKeyTime = Date.now();
        this.timeout = 100; // ms entre caracteres
    }

    handleKeypress(event) {
        const currentTime = Date.now();

        if (currentTime - this.lastKeyTime > this.timeout) {
            // Nueva secuencia, limpiar buffer
            this.buffer = '';
        }

        if (event.key === 'Enter') {
            // Fin del código de barras
            this.processBarcode(this.buffer);
            this.buffer = '';
        } else {
            this.buffer += event.key;
        }

        this.lastKeyTime = currentTime;
    }

    processBarcode(barcode) {
        // Buscar producto por código
        fetch(`/api/products/search?barcode=${barcode}`)
            .then(response => response.json())
            .then(product => {
                if (product) {
                    addToCart(product);
                    playSuccessSound();
                } else {
                    showError('Producto no encontrado');
                    playErrorSound();
                }
            });
    }
}
```

---

## 🧪 Testing del POS

### Tests Unitarios

```php
class POSServiceTest extends TestCase
{
    public function test_can_create_sale_with_valid_data()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();

        $saleData = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2]
            ],
            'payment_method' => 'cash'
        ];

        $sale = app(POSService::class)->createSale($saleData, $user);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals(2420.00, $sale->total);
        $this->assertEquals(8, $product->fresh()->stock);
    }
}
```

### Tests de Integración

```php
class POSWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_pos_sale_workflow()
    {
        // Crear tenant y contexto
        $tenant = Tenant::factory()->create();
        $tenant->execute(function () {
            $product = Product::factory()->create(['stock' => 5]);
            $user = User::factory()->create();

            // Simular carrito
            $cart = [
                ['product_id' => $product->id, 'quantity' => 2]
            ];

            // Procesar venta
            $response = $this->actingAs($user)
                ->postJson('/api/pos/sale', [
                    'cart' => $cart,
                    'payment_method' => 'cash',
                    'amount_paid' => 2500
                ]);

            $response->assertStatus(201);

            // Verificar resultados
            $this->assertDatabaseHas('sales', [
                'total' => 2420.00,
                'payment_method' => 'cash'
            ]);

            $this->assertEquals(3, $product->fresh()->stock);
        });
    }
}
```

---

## 🚀 Próximas Mejoras

### Fase 2: Offline Mode
- Sincronización automática al reconectar
- Cola de ventas offline
- Cache inteligente de productos

### Fase 3: Multi-Caja
- Gestión de múltiples terminales
- Sincronización en tiempo real
- Reportes consolidados

### Fase 4: IA y Analytics
- Predicción de demanda
- Sugerencias de productos
- Análisis de comportamiento del cliente

---

## 📞 Soporte

¿Problemas con el POS?

- **📧 Email:** pos@kartenant.com
- **💬 WhatsApp:** +54 9 11 1234-5678
- **📚 Documentación:** [POS Troubleshooting](docs/troubleshooting/pos-issues.md)
- **🎥 Videos:** [Tutoriales POS](https://youtube.com/kartenant)

---

**¿Listo para vender?** El POS de Kartenant está optimizado para velocidad y confiabilidad. ¡Empieza a procesar ventas en segundos! ⚡