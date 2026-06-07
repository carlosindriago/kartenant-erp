# ❓ Preguntas Frecuentes — Kartenant ERP

Encuentra respuestas a las preguntas más comunes sobre Kartenant ERP.

## 📋 Índice

- [General](#general)
- [Instalación](#instalación)
- [Uso del Sistema](#uso-del-sistema)
- [POS y Ventas](#pos-y-ventas)
- [Inventario](#inventario)
- [Clientes](#clientes)
- [Reportes](#reportes)
- [Seguridad](#seguridad)
- [Licencia y Precios](#licencia-y-precios)
- [Soporte](#soporte)

---

## 🤔 General

### ¿Qué es Kartenant ERP?

Kartenant ERP es una **plataforma SaaS multi-tenant de código abierto** para gestión de comercios retail, ferreterías y PYMEs. Opera bajo un modelo **Open-Core**: el núcleo es libre (AGPL-3.0), y la infraestructura SaaS se ofrece como servicio premium.

Incluye:
- Punto de venta (POS) profesional con soporte de escáner
- Gestión de inventario en tiempo real con trazabilidad
- CRM básico con historial de compras por cliente
- Dashboard con KPIs en tiempo real
- Verificación de documentos con SHA-256 y QR
- Backups automáticos y seguridad avanzada

### ¿Para qué tipo de negocios está diseñado?

Ideal para:
- **Ferreterías y comercios pequeños** (1–5 empleados)
- **Negocios en crecimiento** (5–20 empleados)
- **Cadenas y franquicias** (multi-sucursal, próximamente)

### ¿Es fácil de usar?

✅ Sí. Diseñado con el principio de que si alguien sin experiencia técnica puede usarlo en minutos sin capacitación, entonces cumple su objetivo.

### ¿Funciona offline?

Actualmente requiere conexión a internet. Modo offline en el roadmap.

---

## 🛠️ Instalación

### ¿Qué necesito para instalarlo?

**Requisitos mínimos:**
- PHP 8.2+, PostgreSQL 15+, Docker (recomendado)
- 2 GB RAM, 10 GB disco

### ¿Puedo instalarlo en mi propio servidor?

Sí. El repositorio incluye guías completas para instalación self-hosted con Docker (Sail) o instalación nativa.

Ver: [Guía de Instalación](installation.md)

### ¿Cómo creo el primer usuario administrador?

```bash
# Con Docker (Sail)
./vendor/bin/sail artisan kartenant:make-superadmin

# Sin Docker
php artisan kartenant:make-superadmin
```

Este comando:
- ✅ Crea el superadmin con todos los permisos
- ✅ Verifica automáticamente el email
- ✅ Activa el usuario

Acceso al panel: `http://localhost/admin/login`

### ¿Cuánto cuesta?

El código fuente es **gratuito y de código abierto** (AGPL-3.0). Puedes instalarlo y usarlo libremente en modo standalone.

Para la versión SaaS gestionada con infraestructura multi-tenant, backups automáticos y soporte, ver el modelo de precios en [PRICING_STRUCTURE.md](../PRICING_STRUCTURE.md).

---

## 💻 Uso del Sistema

### ¿Cómo accedo al sistema?

- **Modo standalone / desarrollo:** `http://localhost/admin/login`
- **Modo SaaS (tenant):** `https://tu-tienda.tu-dominio.com/app`

### ¿Puedo usar en móvil/tablet?

✅ Sí, optimizado para tablets. El POS funciona en tablets Android/iOS con pantalla táctil.

### ¿Cuántos usuarios puedo crear?

En modo standalone: sin límite técnico, según tu plan de hosting.
En modo SaaS: según el plan contratado (ver [PRICING_STRUCTURE.md](../PRICING_STRUCTURE.md)).

### ¿Puedo personalizar colores y logo?

✅ Sí. Sube tu logo y elige colores corporativos desde **Configuración → Branding**. Se aplica en POS, tickets y PDFs.

---

## 🛒 POS y Ventas

### ¿Cómo funciona el POS?

**Flujo típico de una venta:**
1. Busca producto (nombre, SKU o código de barras)
2. Agrega al carrito con clic o Enter
3. Presiona F12 para procesar pago
4. Selecciona método de pago (efectivo / tarjeta)
5. Confirma — ticket generado automáticamente

**Tiempo promedio:** ~30 segundos por venta.

### ¿Soporta códigos de barras?

✅ Sí. Compatible con cualquier escáner USB. Detecta automáticamente la entrada rápida de teclado del escáner.

### ¿Puedo hacer descuentos?

✅ Sí: por línea de producto, sobre el total de la venta, y por cliente frecuente.

### ¿Qué pasa si cometo un error en una venta?

**Anulación rápida** (primeros 5 minutos): botón "Anular Venta" + verificación con contraseña → stock se revierte automáticamente.

**Devolución formal** (después de 5 minutos): requiere autorización de supervisor → genera nota de crédito → stock se devuelve tras aprobación.

Ver: [Sistema de Devoluciones y Seguridad](../SISTEMA_DEVOLUCIONES_Y_SEGURIDAD.md)

### ¿Puedo vender sin stock?

❌ No. El sistema valida el stock disponible antes de agregar al carrito.

---

## 📦 Inventario

### ¿Cómo agrego productos?

**Inventario → Productos → Nuevo** — Completa: nombre, código/SKU, precio base, categoría, impuesto y stock inicial.

> El precio ingresado es el precio BASE. El sistema calcula el impuesto automáticamente.

### ¿Cómo funciona el cálculo de impuestos?

```
Precio Base:  $100.00  (lo que ingresas)
IVA 12%:       $12.00  (calculado automáticamente)
Precio Final: $112.00  (lo que paga el cliente)
```

El porcentaje de IVA se configura por producto según el país.

### ¿Alertas de stock bajo?

✅ Sí, automáticas y configurables por producto. Aparecen en el dashboard y en el widget de productos críticos.

### ¿Puedo importar productos desde Excel?

En el roadmap. Actualmente: carga manual o vía API.

---

## 👥 Clientes

### ¿Cómo agrego clientes?

Tres formas:
1. **Manual:** Clientes → Nuevo Cliente
2. **Desde el POS:** opción "Cliente" durante una venta
3. **Automático:** la primera venta puede crear un cliente básico

### ¿Puedo ver el historial de compras?

✅ Sí, completo: todas las compras, total gastado, productos frecuentes y fechas.

---

## 📊 Reportes

### ¿Qué reportes incluye?

- Ventas por período, cajero y método de pago
- Productos más vendidos
- Top clientes por volumen
- Movimientos y estado de inventario
- Cierres de caja con verificación QR

Ver detalle completo en [reports-analytics.md](../features/reports-analytics.md).

### ¿Puedo exportar a Excel o PDF?

✅ Sí. Todos los reportes son exportables. Los PDFs de documentos críticos incluyen hash SHA-256 y QR de verificación.

### ¿Los reportes son en tiempo real?

✅ Sí. El dashboard se actualiza automáticamente. Los reportes históricos están disponibles al instante.

---

## 🔐 Seguridad

### ¿Es seguro el sistema?

✅ Implementa seguridad en capas:
- Autenticación con bcrypt + 2FA (TOTP)
- Roles y permisos granulares
- Aislamiento de datos por tenant (base de datos separada)
- Verificación de documentos con SHA-256 y QR
- Auditoría completa de todas las acciones
- Backups automáticos diarios

Ver: [Sistema de Seguridad](../features/security-system.md)

### ¿Puedo verificar la autenticidad de documentos?

✅ Sí. Todos los PDFs críticos incluyen un código QR. Escanéalo y el sistema confirma si el documento fue alterado o no.

### ¿Quién puede acceder a mis datos?

Solo tú y tu equipo. La arquitectura Database-per-Tenant garantiza aislamiento completo entre empresas.

---

## 💰 Licencia y Precios

### ¿Bajo qué licencia está publicado?

**AGPL-3.0** (GNU Affero General Public License v3). Puedes usar, estudiar, modificar y distribuir el código libremente. Si lo modificas y ofreces como servicio de red, debes liberar tus modificaciones bajo la misma licencia.

Ver: [LICENSE](../../LICENSE) y [CLA.md](../../CLA.md)

### ¿Puedo usarlo para mi negocio sin pagar?

Sí, en modo standalone (self-hosted). El código es libre.

### ¿Cuándo hay período de prueba para el SaaS?

Consulta el modelo de precios actualizado en [PRICING_STRUCTURE.md](../PRICING_STRUCTURE.md).

---

## 🆘 Soporte

### ¿Cómo reporto un bug?

1. Revisa si ya está reportado en [GitHub Issues](https://github.com/carlosindriago/kartenant-erp/issues)
2. Si no, crea un nuevo issue con:
   - Descripción detallada del problema
   - Pasos para reproducirlo
   - Capturas de pantalla si aplica
   - Versión del sistema y navegador

### ¿Cómo reporto una vulnerabilidad de seguridad?

**No abras un Issue público.** Sigue el proceso responsable descrito en [SECURITY.md](../../SECURITY.md).

### ¿Cómo contribuyo al proyecto?

Lee [CONTRIBUTING.md](../../CONTRIBUTING.md) y el [CLA.md](../../CLA.md). Los PRs son bienvenidos.

---

## 🚀 Próximas Funcionalidades

### En desarrollo
- Integraciones de pago (MercadoPago, Stripe)
- Facturación electrónica oficial (AFIP, SRI, SUNAT)
- Sistema multi-sucursal
- Gestión de empleados y comisiones
- API REST completa para integraciones externas
- Import masivo de productos desde Excel

---

**¿No encontraste tu pregunta?**

Abre un [Issue en GitHub](https://github.com/carlosindriago/kartenant-erp/issues) con la etiqueta `question` y te respondemos a la brevedad.
