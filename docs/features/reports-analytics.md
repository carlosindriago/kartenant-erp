# 📊 Reportes y Analytics — Kartenant ERP

El módulo de **Reportes y Analytics** convierte los datos operativos del sistema en información accionable: ventas, inventario, clientes y rendimiento de caja, todo disponible en tiempo real y exportable a Excel/PDF.

---

## 🎯 ¿Qué resuelve este módulo?

Como comerciante, necesitas saber al cierre del día: ¿cuánto vendí?, ¿qué productos se están acabando?, ¿qué cajero cerró con diferencia? Kartenant responde esas preguntas sin que tengas que hacer ni una suma manual.

---

## 📈 Dashboard Principal

El dashboard es el primer pantallazo al abrir el sistema. Muestra KPIs en tiempo real:

| Widget | Descripción |
|---|---|
| Ventas del día | Total facturado en el turno actual |
| Ventas del mes | Acumulado mensual con comparativa al mes anterior |
| Productos bajo mínimo | Alerta de stock crítico con acceso directo |
| Estado de caja | Abierta / Cerrada, cajero activo y saldo |
| Últimas ventas | Feed en tiempo real de las últimas transacciones |
| Top productos | Ranking de los más vendidos en el período |

> Ver configuración detallada del dashboard en [dashboard_plan.md](dashboard_plan.md)

---

## 📋 Reportes Disponibles

### Reportes de Ventas

| Reporte | Filtros disponibles | Exporta |
|---|---|---|
| Ventas por período | Fecha desde/hasta, cajero, método de pago | Excel, PDF |
| Productos más vendidos | Período, categoría | Excel, PDF |
| Ventas por cajero | Período, usuario | Excel |
| Detalle de venta | Número de comprobante | PDF con QR |
| Anulaciones y devoluciones | Período, motivo | Excel |

### Reportes de Inventario

| Reporte | Filtros disponibles | Exporta |
|---|---|---|
| Stock actual | Categoría, estado (bajo/normal/exceso) | Excel, PDF |
| Movimientos de stock | Período, tipo, producto | Excel |
| Productos bajo mínimo | Categoría | Excel |
| Historial de ajustes | Período, usuario | Excel |
| Valorización del inventario | Categoría | PDF |

### Reportes de Caja

| Reporte | Filtros disponibles | Exporta |
|---|---|---|
| Cierre de caja | Fecha, cajero | PDF con QR |
| Historial de aperturas/cierres | Período, cajero | Excel |
| Diferencias de caja | Período | Excel |

### Reportes de Clientes

| Reporte | Filtros disponibles | Exporta |
|---|---|---|
| Top clientes por monto | Período | Excel |
| Clientes frecuentes | Período, mínimo de compras | Excel |
| Clientes inactivos | Días sin compra | Excel |
| Historial de un cliente | Cliente específico | PDF |

---

## 🔒 Permisos Requeridos

| Acción | Permiso |
|---|---|
| Ver dashboard | `dashboard.view` |
| Ver reportes de ventas | `reports.sales.view` |
| Ver reportes de inventario | `reports.inventory.view` |
| Exportar a Excel/PDF | `reports.export` |
| Ver reportes de caja | `reports.cash.view` |

> Ver configuración completa en [PERMISOS_Y_FUNCIONALIDADES.md](PERMISOS_Y_FUNCIONALIDADES.md)

---

## 📤 Exportación

### Excel
- Generado con **Maatwebsite/Excel** + **pxlrbt/filament-excel**
- Formato profesional con encabezados, totales y filtros
- Descarga directa desde el panel sin carga adicional al servidor

### PDF
- Generado con **DomPDF** (barryvdh/laravel-dompdf)
- Incluye branding del tenant (logo y colores)
- Documentos críticos (cierres de caja, facturas) llevan **hash SHA-256 + QR** de verificación

> Ver sistema de verificación en [PDF_VERIFICATION_SYSTEM.md](../PDF_VERIFICATION_SYSTEM.md)

---

## ⚙️ Configuración

### Rango de fechas por defecto

Desde **Configuración → Reportes → Rango por defecto**: `hoy`, `semana`, `mes`.

### Zona horaria

Todos los reportes usan la zona horaria configurada por tenant. Ver [ZONA_HORARIA_TENANTS.md](ZONA_HORARIA_TENANTS.md).

### Formato de hora en dashboard

Ver mejoras recientes en [dashboard-time-format-improvement.md](../dashboard-time-format-improvement.md).

---

## 🔗 Documentos Relacionados

- [Dashboard Plan](dashboard_plan.md)
- [Sistema de Verificación PDF](../PDF_VERIFICATION_SYSTEM.md)
- [Movimientos de Stock](../stock-movement-verification-system.md)
- [Precios y Planes](../PRICING_STRUCTURE.md)
