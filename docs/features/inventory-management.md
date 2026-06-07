# 📦 Gestión de Inventario — Kartenant ERP

El módulo de **Inventario** es el núcleo de trazabilidad de Kartenant ERP. Permite controlar el stock en tiempo real, registrar cada movimiento con auditoría completa, y recibir alertas automáticas ante niveles bajos.

---

## 🎯 ¿Qué resuelve este módulo?

Piensa en el inventario como el "libro contable" de tu almacén: cada producto que entra, sale o se mueve queda registrado con fecha, responsable y motivo. Nunca más "¿quién se llevó esas 5 unidades?"

---

## ✨ Funcionalidades Principales

### Control de Stock en Tiempo Real
- Visualización instantánea de unidades disponibles por producto
- Actualización automática al procesar ventas desde el POS
- Soporte para múltiples unidades de medida (unidades, kg, metros, etc.)

### Registro de Movimientos
Cada movimiento de stock genera un registro inmutable con:
- Tipo de movimiento: `entrada`, `salida`, `ajuste`, `devolución`, `transferencia`
- Cantidad y producto afectado
- Usuario responsable
- Timestamp exacto
- Referencia al documento origen (venta, compra, ajuste manual)

### Alertas de Stock Bajo
- Umbral de stock mínimo configurable por producto
- Notificaciones en el dashboard cuando el stock cae por debajo del umbral
- Listado de productos críticos accesible desde el panel principal

### Ajustes Manuales
- Registro de ajustes de inventario con motivo obligatorio
- Historial de ajustes auditado por usuario
- Diferenciación entre ajuste positivo (entrada) y negativo (merma/pérdida)

---

## 🗂️ Estructura del Módulo

```
app/
├── Models/
│   ├── Product.php          # Modelo principal de producto
│   └── StockMovement.php    # Registro de movimientos
├── Services/
│   └── InventoryService.php # Lógica de negocio de stock
Modules/Inventory/
├── Resources/               # Recursos Filament
├── Pages/                   # Páginas del panel
└── Widgets/                 # Widgets de dashboard
```

---

## 🔄 Flujo de un Movimiento de Stock

```
Evento (Venta POS / Ajuste / Compra)
        ↓
  InventoryService::recordMovement()
        ↓
  Validación de stock disponible
        ↓
  Actualización de Product.stock_quantity
        ↓
  Creación de StockMovement (log inmutable)
        ↓
  Verificación de umbral mínimo
        ↓
  [Si bajo mínimo] → Notificación en dashboard
```

---

## 📊 Reportes Disponibles

| Reporte | Descripción |
|---|---|
| Movimientos por período | Historial filtrable por fecha, tipo y producto |
| Stock actual | Snapshot del inventario completo con valorización |
| Productos bajo mínimo | Lista de productos que requieren reposición |
| Historial de ajustes | Auditoría de todos los ajustes manuales |

> Ver también: [Sistema de Verificación de Movimientos de Stock](../stock-movement-verification-system.md)

---

## ⚙️ Configuración

### Definir stock mínimo por producto

Desde el panel admin: **Inventario → Productos → Editar → Stock Mínimo**

```php
// O directamente vía seeder/factory
$product->update(['min_stock' => 10]);
```

### Ajuste manual de stock

Desde el panel: **Inventario → Ajustes → Nuevo Ajuste**

Campos requeridos:
- Producto
- Cantidad (positiva = entrada, negativa = salida/merma)
- Motivo del ajuste

---

## 🔐 Permisos Requeridos

| Acción | Permiso |
|---|---|
| Ver stock | `inventory.view` |
| Registrar ajuste | `inventory.adjust` |
| Ver movimientos | `inventory.movements.view` |
| Exportar reportes | `inventory.export` |

> Ver configuración completa de permisos en [PERMISOS_Y_FUNCIONALIDADES.md](PERMISOS_Y_FUNCIONALIDADES.md)

---

## 🔗 Integraciones

- **POS** — Cada venta descuenta stock automáticamente
- **Devoluciones** — Las devoluciones procesadas desde el POS revierten el movimiento
- **Dashboard** — Widget de "Productos bajo mínimo" en tiempo real
- **Verificación de documentos** — Los ajustes generan un hash SHA-256 para auditoría

> Ver: [Sistema de Devoluciones y Seguridad](../SISTEMA_DEVOLUCIONES_Y_SEGURIDAD.md)
