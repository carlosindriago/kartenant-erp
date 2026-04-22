# 📚 Sistema de Devoluciones y Cámara de Seguridad Digital

## 🎯 Visión General

Este documento describe el sistema completo de **Devoluciones de Ventas** implementado en Kartenant, siguiendo el principio contable del **Libro Inmutable** y equipado con una **Cámara de Seguridad Digital** que registra cada acción sensible con detalle forense.

---

## 🏛️ Principios Fundamentales

### 1. **Libro Contable Inmutable**
> "Un contador nunca usa corrector líquido. Siempre añade una nueva línea que revierte el error."

- ✅ Las ventas originales **NUNCA** se borran
- ✅ Cada devolución crea una **Nota de Crédito** nueva
- ✅ El historial completo es **auditable**
- ✅ Cumplimiento contable y legal garantizado

### 2. **Cámara de Seguridad Digital**
> "Todo lo que sucede en el sistema queda registrado permanentemente"

- 📹 **Logs exhaustivos** de cada acción
- 🔐 **Autenticación obligatoria** con contraseña
- 🕵️ **Trazabilidad completa**: Usuario, IP, Timestamp
- 🚨 **Alertas automáticas** de intentos fallidos
- 💾 **Registros inmutables** en archivos de log

---

## 🏗️ Arquitectura del Sistema

### Base de Datos

```
┌─────────────────┐
│     Sales       │ ← Venta Original (INMUTABLE)
└────────┬────────┘
         │ 1:N
         │
┌────────▼────────────┐
│   SaleReturns       │ ← Nota de Crédito
│  (return_number:    │
│   NCR-YYYYMMDD-XXX) │
└────────┬────────────┘
         │ 1:N
         │
┌────────▼────────────┐
│  SaleReturnItems    │ ← Detalle por producto
└─────────────────────┘
```

### Componentes Principales

```
┌─────────────────────────────────────────────┐
│           PUNTO DE VENTA (POS)              │
│                                             │
│  ┌────────────────────────────────────┐    │
│  │  Botón "Anular Venta" (si < 5 min) │    │
│  └──────────────┬─────────────────────┘    │
│                 │                           │
│                 ▼                           │
│  ┌────────────────────────────────────┐    │
│  │   Modal de Confirmación            │    │
│  │   - Resumen de venta               │    │
│  │   - Advertencias claras            │    │
│  │   - Campo de contraseña            │    │
│  └──────────────┬─────────────────────┘    │
└─────────────────┼──────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────┐
│         ReturnService (Lógica de Negocio)   │
│                                             │
│  1. Verifica contraseña                    │
│  2. Registra en log (INICIO)               │
│  3. Crea SaleReturn                        │
│  4. Crea SaleReturnItems                   │
│  5. Crea StockMovements (ENTRADA)          │
│  6. Actualiza Stock                        │
│  7. Registra en log (ÉXITO)                │
│                                             │
└─────────────────┬───────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────┐
│      📹 CÁMARA DE SEGURIDAD DIGITAL         │
│                                             │
│  storage/logs/laravel.log                  │
│                                             │
│  ✅ Usuario (ID, nombre, email)             │
│  ✅ IP Address                              │
│  ✅ Timestamp preciso                       │
│  ✅ Acción realizada                        │
│  ✅ Datos completos                         │
│  ✅ Resultados                              │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 👤 Guía de Uso para el Cajero

### Escenario: Cometí un error en una venta

#### 1️⃣ **Ventana de Oportunidad: 5 minutos**

Inmediatamente después de completar una venta incorrecta:

1. Verás un **botón rojo pulsante** en la barra superior: **"Anular Venta"**
2. Este botón solo aparece si la última venta tiene menos de 5 minutos
3. Click en el botón

#### 2️⃣ **Modal de Confirmación**

Se abrirá un modal con:

- ⚠️ **Advertencia clara** de lo que va a ocurrir:
  - Se creará una Nota de Crédito
  - Los productos volverán al inventario
  - Tu nombre quedará registrado
  - La acción es permanente

- 📋 **Resumen completo de la venta**:
  - Número de factura
  - Fecha y hora
  - Cliente
  - Lista de productos
  - Total

- 🔐 **Campo de contraseña**:
  - Debes ingresar TU contraseña
  - Es la misma que usas para entrar al sistema
  - Esto confirma tu identidad

#### 3️⃣ **Confirmación**

1. Lee cuidadosamente el resumen
2. Verifica que es la venta correcta
3. Ingresa tu contraseña
4. Presiona "Confirmar Anulación" o Enter

#### 4️⃣ **Resultado**

- ✅ Verás una notificación de éxito con el número de Nota de Crédito
- ✅ Los productos vuelven automáticamente al inventario
- ✅ Se genera un PDF de la Nota de Crédito
- ✅ Todo queda registrado en el sistema

---

## 🔐 Sistema de Seguridad

### Capas de Protección

```
┌──────────────────────────────────────┐
│  1. Ventana Temporal (5 minutos)     │ ← Primera barrera
└──────────────┬───────────────────────┘
               │
┌──────────────▼───────────────────────┐
│  2. Resumen Visual Detallado         │ ← Confirmación consciente
└──────────────┬───────────────────────┘
               │
┌──────────────▼───────────────────────┐
│  3. Verificación de Contraseña       │ ← Autenticación obligatoria
└──────────────┬───────────────────────┘
               │
┌──────────────▼───────────────────────┐
│  4. Registro en Log                  │ ← Cámara de seguridad
└──────────────────────────────────────┘
```

### Intentos Fallidos

Si alguien intenta anular una venta con **contraseña incorrecta**:

```
🚨 LOG DE ALERTA:
{
    "level": "WARNING",
    "severity": "HIGH",
    "message": "INTENTO FALLIDO de anulación - Contraseña incorrecta",
    "user_id": 5,
    "user_name": "Juan Pérez",
    "sale_id": 42,
    "ip": "192.168.1.100",
    "timestamp": "2025-10-11 14:05:23"
}
```

**Acción automática:**
- ⚠️ Log de seguridad registrado
- 🔴 Contraseña borrada del campo
- ❌ Mensaje de error visible
- 🚫 Anulación bloqueada

---

## 📹 Cámara de Seguridad Digital

### Eventos Registrados

#### 1. Apertura del Modal
```json
{
    "emoji": "🔍",
    "message": "Intento de anular venta - Modal abierto",
    "user_id": 5,
    "user_name": "Carlos Admin",
    "ip": "192.168.1.50",
    "timestamp": "2025-10-11 14:05:00"
}
```

#### 2. Intento Fuera de Tiempo
```json
{
    "emoji": "⏰",
    "level": "WARNING",
    "message": "Intento de anular venta fuera de tiempo permitido",
    "sale_id": 42,
    "invoice_number": "FAC-20251011-0042",
    "minutes_elapsed": 8,
    "user": "Carlos Admin"
}
```

#### 3. Contraseña Incorrecta
```json
{
    "emoji": "🚨",
    "level": "WARNING",
    "severity": "HIGH",
    "message": "INTENTO FALLIDO de anulación - Contraseña incorrecta",
    "sale_id": 42,
    "user_id": 5,
    "user_name": "Carlos Admin",
    "ip": "192.168.1.50",
    "timestamp": "2025-10-11 14:05:15"
}
```

#### 4. Inicio de Anulación (Contraseña Correcta)
```json
{
    "emoji": "🎬",
    "level": "INFO",
    "message": "INICIANDO ANULACIÓN DE VENTA",
    "sale_id": 42,
    "invoice_number": "FAC-20251011-0042",
    "total": 1500.00,
    "items_count": 3,
    "authorized_by_user_id": 5,
    "authorized_by_user_name": "Carlos Admin",
    "authorized_by_user_email": "carlos@kartenant.com",
    "ip_address": "192.168.1.50",
    "timestamp": "2025-10-11 14:05:20",
    "customer": "Juan Pérez",
    "original_cashier": "María Gómez"
}
```

#### 5. Procesamiento en ReturnService
```json
{
    "emoji": "🚨",
    "level": "INFO",
    "event_type": "QUICK_CANCEL_SALE",
    "sale_id": 42,
    "sale_number": "FAC-20251011-0042",
    "sale_total": 1500.00,
    "sale_items_count": 3,
    "reason": "Anulación rápida autorizada por Carlos Admin",
    "triggered_by_user_id": 5,
    "triggered_by_user_name": "Carlos Admin",
    "timestamp": "2025-10-11 14:05:21"
}
```

#### 6. Devolución Completada
```json
{
    "emoji": "📦",
    "level": "INFO",
    "event_type": "SALE_RETURN",
    "return_id": 10,
    "return_number": "NCR-20251011-0010",
    "return_type": "full",
    "original_sale_id": 42,
    "original_sale_number": "FAC-20251011-0042",
    "refund_amount": 1500.00,
    "refund_method": "cash",
    "items_count": 3,
    "items_detail": [
        {
            "product": "Martillo Stanley",
            "quantity_returned": 2,
            "reason": "Anulación rápida autorizada por Carlos Admin"
        },
        {
            "product": "Destornillador",
            "quantity_returned": 1,
            "reason": "Anulación rápida autorizada por Carlos Admin"
        }
    ],
    "stock_movements_created": 3,
    "processed_by_user_id": 5,
    "processed_by_user_name": "Carlos Admin",
    "processed_by_user_email": "carlos@kartenant.com",
    "tenant_id": 6,
    "timestamp": "2025-10-11 14:05:22",
    "status": "COMPLETED"
}
```

#### 7. Confirmación Final en POS
```json
{
    "emoji": "✅",
    "level": "INFO",
    "message": "ANULACIÓN COMPLETADA EXITOSAMENTE",
    "sale_id": 42,
    "invoice_number": "FAC-20251011-0042",
    "return_id": 10,
    "return_number": "NCR-20251011-0010",
    "refund_amount": 1500.00,
    "products_returned": [
        {
            "product": "Martillo Stanley",
            "quantity": 2,
            "value": 1000.00
        },
        {
            "product": "Destornillador",
            "quantity": 1,
            "value": 500.00
        }
    ],
    "authorized_by": "Carlos Admin",
    "timestamp": "2025-10-11 14:05:23",
    "status": "SUCCESS"
}
```

---

## 🔍 Búsqueda en Logs

### Comandos Útiles

#### Ver todas las anulaciones del día
```bash
grep "🚨 QUICK CANCEL" storage/logs/laravel-$(date +%Y-%m-%d).log
```

#### Ver intentos fallidos (contraseña incorrecta)
```bash
grep "🚨 INTENTO FALLIDO" storage/logs/laravel.log
```

#### Ver todas las devoluciones procesadas
```bash
grep "📦 DEVOLUCIÓN" storage/logs/laravel.log
```

#### Ver acciones de un usuario específico
```bash
grep "carlos@kartenant.com" storage/logs/laravel.log
```

#### Ver eventos por tipo
```bash
grep "event_type.*QUICK_CANCEL" storage/logs/laravel.log
```

#### Ver eventos de alta severidad
```bash
grep "severity.*HIGH" storage/logs/laravel.log
```

---

## 📊 Reportes y Auditoría

### Información Disponible en los Logs

Para cada anulación, el sistema registra:

1. **Identificación**
   - Usuario que autorizó (ID, nombre, email)
   - IP desde donde se realizó
   - Timestamp exacto con milisegundos

2. **Contexto**
   - Venta original (número, total)
   - Cliente
   - Cajero que hizo la venta original
   - Razón de la anulación

3. **Acción**
   - Productos devueltos (lista completa)
   - Cantidades
   - Valores
   - Método de reembolso

4. **Resultado**
   - Nota de Crédito generada (número)
   - Stock actualizado
   - Status de la operación

### Ejemplo de Auditoría Completa

```
PREGUNTA: ¿Quién anuló la venta FAC-20251011-0042 y por qué?

RESPUESTA (de los logs):
- Usuario: Carlos Admin (ID: 5, carlos@kartenant.com)
- Fecha: 2025-10-11 14:05:23
- IP: 192.168.1.50
- Razón: "Anulación rápida autorizada por Carlos Admin"
- Autenticación: Contraseña verificada exitosamente
- Venta original: $1,500.00 (3 productos)
- Nota de Crédito: NCR-20251011-0010
- Productos devueltos:
  * Martillo Stanley × 2 = $1,000
  * Destornillador × 1 = $500
- Cajero original: María Gómez
- Cliente: Juan Pérez
```

---

## 🛡️ Seguridad y Cumplimiento

### Protecciones Implementadas

✅ **No se puede eludir la verificación de contraseña**
✅ **Los logs son inmutables** (solo append)
✅ **Trazabilidad completa** de cada acción
✅ **IP tracking** automático
✅ **Intentos fallidos registrados** con alta prioridad
✅ **Ventana temporal limitada** (5 minutos)
✅ **Resumen visual** para confirmación consciente
✅ **Principio contable** respetado (libro inmutable)

### Casos de Uso de Seguridad

#### 1. Detección de Fraude
Si un empleado intenta anular ventas sospechosas:
- ✅ Su identidad queda registrada
- ✅ Su contraseña es verificada
- ✅ Su IP queda registrada
- ✅ Timestamp exacto
- ✅ Venta específica identificada

#### 2. Auditoría Externa
Un auditor puede:
- ✅ Ver todas las anulaciones
- ✅ Verificar autorización correcta
- ✅ Confirmar stock corregido
- ✅ Validar Notas de Crédito
- ✅ Rastrear usuario responsable

#### 3. Resolución de Disputas
Si hay conflicto sobre una anulación:
- ✅ Log completo disponible
- ✅ Usuario identificado
- ✅ Timestamp preciso
- ✅ Razón documentada
- ✅ Productos detallados

---

## 📈 Métricas y KPIs

El sistema permite rastrear:

- **Tasa de anulaciones**: ¿Cuántas ventas se anulan?
- **Velocidad de anulación**: ¿En cuánto tiempo después de la venta?
- **Usuarios con más anulaciones**: ¿Quién comete más errores?
- **Productos más devueltos**: ¿Qué productos tienen problemas?
- **Horarios de anulación**: ¿Cuándo ocurren más errores?

---

## 🚀 Próximas Mejoras Sugeridas

1. **Dashboard de Auditoría**
   - Vista gráfica de anulaciones
   - Alertas automáticas
   - Reportes exportables

2. **Notificaciones**
   - Email al gerente en cada anulación
   - SMS en anulaciones grandes (> $X)
   - Webhook a sistema externo

3. **Análisis Predictivo**
   - Detectar patrones sospechosos
   - Sugerir entrenamiento
   - Alertar sobre comportamiento anómalo

4. **Integración Contable**
   - Sincronización con software contable
   - Generación de asientos automáticos
   - Exportación a Excel/PDF

---

## 📞 Soporte

Para cualquier duda sobre el sistema:

1. Revisa esta documentación
2. Consulta los logs en `storage/logs/laravel.log`
3. Contacta al administrador del sistema

---

**Versión:** 1.0  
**Fecha:** 2025-10-11  
**Autor:** Kartenant Dev Team  
**Estado:** ✅ Producción
