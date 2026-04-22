# ❓ Preguntas Frecuentes - Kartenant

Encuentra respuestas a las preguntas más comunes sobre Kartenant.

## 📋 Índice

- [General](#general)
- [Instalación](#instalación)
- [Uso del Sistema](#uso-del-sistema)
- [POS y Ventas](#pos-y-ventas)
- [Inventario](#inventario)
- [Clientes](#clientes)
- [Reportes](#reportes)
- [Seguridad](#seguridad)
- [Facturación y Precios](#facturación-y-precios)
- [Soporte](#soporte)

---

## 🤔 General

### ¿Qué es Kartenant?

Kartenant es un **sistema SaaS completo** para gestión de comercios retail. Reemplaza Excel, cuadernos y sistemas legacy con una solución moderna que incluye:

- Punto de venta (POS) profesional
- Gestión de inventario en tiempo real
- Sistema de clientes con historial
- Dashboard con métricas inteligentes
- Verificación de documentos con QR
- Backups automáticos y seguridad avanzada

### ¿Para qué tipo de negocios está diseñado?

Ideal para:
- **Ferreterías y comercios pequeños** (1-5 empleados)
- **Negocios en crecimiento** (5-20 empleados)
- **Cadenas y franquicias** (multi-sucursal)

**No recomendado para:** Grandes corporaciones con ERPs complejos.

### ¿Es fácil de usar?

✅ **Sí, extremadamente fácil.** Diseñado con el principio "Ernesto el ferretero" - si Ernesto puede usarlo en 10 minutos sin capacitación, entonces es simple.

### ¿Funciona offline?

Actualmente **requiere conexión a internet**. Versión offline en desarrollo para 2026.

---

## 🛠️ Instalación

### ¿Qué necesito para instalarlo?

**Requisitos mínimos:**
- PHP 8.2+
- PostgreSQL 15+
- 2GB RAM
- 10GB disco

**Recomendado:**
- VPS con 4GB RAM
- SSL configurado
- Backup automático

### ¿Puedo instalarlo en mi servidor?

Sí, completamente. Proporcionamos guías detalladas para instalación en cualquier servidor Linux.

### ¿Ofrecen hosting?

Próximamente ofreceremos hosting gestionado. Por ahora, instalación self-hosted únicamente.

### ¿Cómo creo el primer usuario administrador?

Ejecuta el comando artisan dedicado:

```bash
php artisan kartenant:make-superadmin
```

Este comando:
- ✅ Crea un usuario con permisos de Super Admin
- ✅ Asigna el rol `admin_manager` con todos los permisos
- ✅ Marca el usuario como `is_superadmin: true`
- ✅ Verifica automáticamente el email
- ✅ Activa el usuario

**Acceso al panel:** `http://tu-dominio.com/admin/login`

**Troubleshooting:**
- Si el comando no existe, verifica que estés usando la rama correcta
- Si hay errores de permisos, ejecuta primero: `php artisan db:seed --class=LandlordAdminSeeder`

### ¿Cuánto cuesta?

**Planes mensuales:**
- Básico: $19 (1 sucursal, 500 productos)
- Profesional: $49 (3 sucursales, productos ilimitados)
- Empresarial: $149 (sucursales ilimitadas, integraciones)

**Sin costos ocultos:** Precio fijo, sin comisiones por transacción.

---

## 💻 Uso del Sistema

### ¿Cómo accedo al sistema?

1. Recibes email con credenciales temporales
2. Accedes a `https://tu-tienda.kartenant.test/app`
3. Verificación 2FA por email
4. Cambio obligatorio de contraseña
5. ¡Listo para usar!

### ¿Puedo usar en móvil/tablet?

✅ **Sí, optimizado para tablets.** El POS funciona perfectamente en tablets Android/iOS con pantalla táctil.

### ¿Cuántos usuarios puedo crear?

Depende del plan:
- **Básico:** 2 usuarios
- **Profesional:** 10 usuarios
- **Empresarial:** Usuarios ilimitados

### ¿Puedo personalizar colores y logo?

✅ **Sí, completamente.** Sube tu logo y elige colores corporativos. Se aplica en POS, tickets y reportes.

---

## 🛒 POS y Ventas

### ¿Cómo funciona el POS?

**Flujo típico:**
1. Busca producto (nombre, SKU, código de barras)
2. Agrega al carrito
3. Procesa pago (F12)
4. Selecciona método (efectivo/tarjeta)
5. Confirma (Enter)

**Tiempo promedio:** 30 segundos por venta.

### ¿Soporta códigos de barras?

✅ **Sí, completamente.** Compatible con cualquier escáner USB. Detecta automáticamente entrada rápida de teclado.

### ¿Puedo hacer descuentos?

✅ **Sí:**
- Descuentos por línea de producto
- Descuentos totales
- Descuentos por cliente frecuente

### ¿Qué pasa si cometo un error en una venta?

**Dos opciones:**

1. **Anulación rápida** (primeros 5 minutos):
   - Botón "Anular Venta"
   - Verificación con contraseña
   - Stock se devuelve automáticamente

2. **Devolución formal** (después de 5 minutos):
   - Requiere autorización de supervisor
   - Crea nota de crédito
   - Stock se devuelve después de aprobación

### ¿Puedo vender sin stock?

❌ **No.** El sistema valida stock disponible antes de agregar al carrito. Evita ventas de productos agotados.

### ¿Cómo manejo pagos mixtos?

Próximamente. Actualmente un método de pago por venta.

---

## 📦 Inventario

### ¿Cómo agrego productos?

1. Inventario → Productos → Nuevo
2. Nombre, código, precio base, categoría, impuesto, stock inicial
3. Guardar

**Importante:** El precio que ingresas es el precio BASE. El sistema calcula IVA automáticamente.

### ¿Cómo funciona el cálculo de IVA?

```
Precio Base: $1000 (lo que ingresas)
IVA 21%: $210 (calculado automáticamente)
Precio Final: $1210 (lo que paga el cliente)
```

### ¿Puedo importar productos desde Excel?

Próximamente en plan Profesional. Actualmente carga manual o API.

### ¿Cómo hago inventario físico?

1. Inventario → Movimientos → Registrar Salida
2. Motivo: "Ajuste de Inventario (Disminución)"
3. Cantidad: Diferencia encontrada
4. Se genera comprobante verificable

### ¿Alertas de stock bajo?

✅ **Sí, automáticas.** Configurables por producto. Aparecen en dashboard y widgets.

---

## 👥 Clientes

### ¿Cómo agrego clientes?

**Tres formas:**
1. Manual: Clientes → Nuevo Cliente
2. Desde POS: Durante venta, opción "Cliente"
3. Automático: Primera venta crea cliente básico

### ¿Puedo ver historial de compras?

✅ **Sí, completo.** Por cliente:
- Todas las compras
- Total gastado
- Productos favoritos
- Frecuencia de compra

### ¿Se puede dar crédito a clientes?

Próximamente. Actualmente solo contado.

---

## 📊 Reportes

### ¿Qué reportes incluye?

**Básico:**
- Ventas por período
- Productos más vendidos
- Top clientes
- Estado de caja

**Profesional (próximamente):**
- Análisis ABC de productos
- Rentabilidad por producto
- Rotación de inventario
- Comparativas mensuales

### ¿Puedo exportar a Excel?

✅ **Plan Profesional.** Todos los reportes exportables a Excel con formato profesional.

### ¿Los reportes son en tiempo real?

✅ **Sí.** Dashboard actualiza automáticamente. Reportes históricos disponibles al instante.

---

## 🔐 Seguridad

### ¿Es seguro mi datos?

✅ **Máxima seguridad:**
- Base de datos encriptada
- Backups diarios automáticos
- Verificación de documentos con hash SHA-256
- Acceso con 2FA obligatorio
- Auditoría completa de todas las acciones

### ¿Qué pasa si pierdo mis datos?

**Triple protección:**
1. Backups diarios automáticos
2. Restauración point-in-time
3. Datos en la nube (georedundante)

### ¿Puedo verificar autenticidad de documentos?

✅ **Sí.** Todos los PDFs incluyen código QR. Escanea y verifica online que no han sido modificados.

### ¿Quién puede acceder a mis datos?

**Solo tú y tu equipo.** Arquitectura multi-tenant garantiza aislamiento completo entre empresas.

---

## 💰 Facturación y Precios

### ¿Cómo funciona la facturación?

**Mensual, automática:**
- Cargo el día 1 de cada mes
- Recordatorio 3 días antes
- Suspensión automática si no paga
- Reactivación inmediata al pagar

### ¿Puedo cambiar de plan?

✅ **Sí, en cualquier momento.** Upgrade inmediato, downgrade al final del período.

### ¿Hay período de prueba?

✅ **14 días gratis** para probar todas las funcionalidades.

### ¿Qué métodos de pago aceptan?

- Tarjeta de crédito/débito
- Transferencia bancaria
- MercadoPago (LATAM)
- PayPal (internacional)

### ¿Puedo cancelar?

✅ **Sí, en cualquier momento.** Sin penalizaciones. Datos disponibles para descarga 30 días.

---

## 🆘 Soporte

### ¿Qué tipo de soporte ofrecen?

**Básico (incluido):**
- Email support (24-48h)
- Centro de ayuda online
- Comunidad Discord

**Profesional ($49/mes):**
- WhatsApp directo (4-8h respuesta)
- Soporte prioritario
- Capacitación incluida

**Empresarial ($149/mes):**
- Soporte 24/7
- Gerente de cuenta dedicado
- Capacitación presencial
- SLA garantizado

### ¿Dónde encuentro documentación?

- 📚 **Documentación completa:** [docs.kartenant.com](https://docs.kartenant.com)
- 🎥 **Videos tutoriales:** [YouTube](https://youtube.com/kartenant)
- 👥 **Comunidad:** [Discord](https://discord.gg/kartenant)

### ¿Cómo reporto un bug?

1. Revisa si ya está reportado en [GitHub Issues](https://github.com/kartenant/issues)
2. Si no, crea nuevo issue con:
   - Descripción detallada
   - Pasos para reproducir
   - Capturas de pantalla
   - Información del navegador/sistema

### ¿Ofrecen capacitación?

✅ **Sí:**
- **Básico:** Videos y guías autoaprendizaje
- **Profesional:** Sesión de 2 horas por WhatsApp
- **Empresarial:** Capacitación presencial + material personalizado

---

## 🚀 Próximas Funcionalidades

### 2025 Q4
- Integraciones de pago (MercadoPago, Stripe)
- Facturación electrónica (AFIP, SRI)
- App móvil básica

### 2026 Q1
- Multi-sucursal
- Sistema de empleados y comisiones
- API completa

### 2026 Q2
- Inteligencia artificial para predicciones
- E-commerce integrado
- App móvil avanzada

---

**¿No encontraste tu pregunta?**

📧 **Contáctanos:** soporte@kartenant.com  
💬 **WhatsApp:** +54 9 11 1234-5678  
📚 **Documentación completa:** [docs.kartenant.com](https://docs.kartenant.com)