# 👥 Gestión de Clientes (CRM) — Kartenant ERP

El módulo de **Clientes** de Kartenant ERP integra las funciones básicas de un CRM directamente en el flujo de trabajo del negocio, sin complejidad innecesaria. Piensa en él como una libreta de clientes inteligente que recuerda todo: qué compró, cuánto gastó y cuándo fue la última vez que vino.

---

## 🎯 ¿Qué resuelve este módulo?

El comerciante típico de una ferretería o tienda conoce a sus clientes frecuentes de memoria. Kartenant digitaliza ese conocimiento: historial de compras, datos de contacto y comportamiento de compra, todo en un lugar accesible desde el POS.

---

## ✨ Funcionalidades Principales

### Ficha de Cliente
- Nombre, RUC/DNI, teléfono, dirección y email
- Fecha de registro y última compra
- Total acumulado en compras (lifetime value)
- Notas internas del comerciante

### Historial de Compras
- Lista completa de transacciones con fecha, monto y productos
- Acceso rápido a cada comprobante/factura desde el historial
- Filtros por período, monto y tipo de documento

### Búsqueda Rápida desde el POS
- Búsqueda por nombre, DNI/RUC o teléfono directamente en la pantalla de venta
- Asociación de la venta al cliente con un solo clic
- Cliente "consumidor final" por defecto para ventas sin identificación

### Segmentación Básica
- Clientes frecuentes (más de N compras en el período)
- Clientes inactivos (sin compras en X días)
- Top clientes por volumen de compra

---

## 🗂️ Estructura del Módulo

```
app/
├── Models/
│   └── Client.php              # Modelo principal de cliente
Modules/Clients/
├── Resources/
│   └── ClientResource.php      # CRUD Filament
├── Pages/
│   └── ClientHistory.php       # Historial de compras
└── Widgets/
    └── TopClientsWidget.php    # Widget dashboard
```

---

## 📊 Reportes Disponibles

| Reporte | Descripción |
|---|---|
| Clientes frecuentes | Ranking por número de compras en período |
| Top clientes por monto | Ranking por volumen total de compras |
| Clientes inactivos | Sin compras en los últimos N días |
| Historial de compras | Detalle completo de un cliente |

---

## ⚙️ Configuración

### Crear un cliente desde el panel

**Clientes → Nuevo Cliente**

Campos obligatorios:
- Nombre o razón social
- Tipo de documento (DNI / RUC / Pasaporte)
- Número de documento

Campos opcionales:
- Teléfono, email, dirección
- Notas internas

### Asociar cliente en el POS

Durante una venta, usar el buscador de cliente en la pantalla POS. Si no se selecciona cliente, la venta se registra como "Consumidor Final".

---

## 🔐 Permisos Requeridos

| Acción | Permiso |
|---|---|
| Ver listado de clientes | `clients.view` |
| Crear / editar cliente | `clients.manage` |
| Ver historial de compras | `clients.history.view` |
| Exportar datos | `clients.export` |

> Ver configuración completa en [PERMISOS_Y_FUNCIONALIDADES.md](PERMISOS_Y_FUNCIONALIDADES.md)

---

## 🔗 Integraciones

- **POS** — Selección de cliente durante la venta
- **Facturación** — El RUC/DNI del cliente se usa para emitir comprobantes
- **Dashboard** — Widget de top clientes del período
- **Devoluciones** — Las devoluciones quedan asociadas al cliente original

---

## 🚧 Próximas Features

- Sistema de crédito y cuentas por cobrar
- Historial de precios especiales por cliente
- Envío de comprobantes por email automático
- Integración con WhatsApp Business API
