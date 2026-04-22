# 📖 Guía de Usuario - Kartenant

Bienvenido a **Kartenant**, tu sistema completo de gestión empresarial. Esta guía te ayudará a aprovechar al máximo todas las funcionalidades del sistema.

## 🎯 ¿Qué es Kartenant?

Kartenant es un **sistema SaaS multi-tenant** diseñado específicamente para pequeños y medianos comercios de LATAM. Reemplaza cuadernos, Excel y sistemas legacy con una solución moderna, segura y escalable.

### 💡 Diferencias con otros sistemas
- **Simpleza extrema**: Configurado en minutos, no días
- **Precio justo**: Sin costos ocultos ni terminales caras
- **Datos seguros**: Backups automáticos y verificación de documentos
- **Escalable**: Crece con tu negocio sin cambiar de sistema

## 🚀 Inicio Rápido

### Primer Acceso
1. **Recibe email de bienvenida** con tus credenciales temporales
2. **Accede a tu dominio**: `https://tu-tienda.kartenant.test/app`
3. **Ingresa email y contraseña** (20 caracteres hexadecimales)
4. **Verifica tu identidad** con código 2FA enviado por email
5. **Cambia tu contraseña** (obligatorio en primer acceso)
6. **¡Listo!** Ya puedes usar el sistema

### Interfaz Principal
```
┌─────────────────────────────────────────────────────────┐
│ 🏪 KARTENANT DIGITAL - Panel de Control                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 📊 DASHBOARD          🛒 PUNTO DE VENTA    📦 INVENTARIO │
│ 👥 CLIENTES           📊 REPORTES          ⚙️  CONFIG    │
│                                                         │
│ 💰 VENTAS HOY: $1,250    📈 +15% vs ayer                │
│ 📦 PRODUCTOS BAJO STOCK: 3                              │
│ ⚠️  DEVOLUCIONES PENDIENTES: 1                           │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## 🛒 Sistema POS (Punto de Venta)

### Uso Básico
1. **Accede al POS**: Menú → "Punto de Venta" (se abre en nueva pestaña)
2. **Busca productos**: Escribe nombre, SKU o escanea código de barras
3. **Agrega al carrito**: Click en producto o presiona Enter
4. **Modifica cantidades**: Usa + / - o escribe la cantidad
5. **Procesa pago**: F12 o botón "Procesar Pago"
6. **Selecciona método**: Efectivo, Tarjeta, Transferencia
7. **Confirma**: Enter para completar venta

### Atajos de Teclado
- **F1**: Ayuda de teclado
- **F2**: Ver historial del día
- **F3**: Focus en búsqueda
- **F9**: Vaciar carrito (con confirmación)
- **F12**: Abrir modal de pago
- **ESC**: Cerrar modales

### Funciones Avanzadas
- **Devoluciones**: Solo en primeras 5 minutos (requiere contraseña)
- **Descuentos**: Por línea o total
- **Clientes**: Búsqueda y creación rápida
- **Notas**: Información adicional por venta

## 📦 Gestión de Inventario

### Agregar Productos
1. Ve a **Inventario → Productos**
2. Click **"Nuevo Producto"**
3. Completa:
   - **Nombre**: Nombre descriptivo
   - **Código/SKU**: Identificador único
   - **Precio**: Precio base (sin IVA)
   - **Categoría**: Organiza tus productos
   - **Impuesto**: IVA 21%, IVA 10.5%, etc.
   - **Stock inicial**: Cantidad actual
4. **Guardar**

### Movimientos de Stock
- **Entradas**: Compras, devoluciones de clientes, ajustes positivos
- **Salidas**: Ventas, devoluciones a proveedores, ajustes negativos
- **Cada movimiento** genera un comprobante verificable con QR

### Alertas Automáticas
- **Stock bajo**: Configurable por producto
- **Productos estancados**: Sin movimientos en 30+ días
- **Alertas visuales** en dashboard principal

## 👥 Gestión de Clientes

### Crear Cliente
1. **Clientes → Nuevo Cliente**
2. Información básica: nombre, email, teléfono, dirección
3. **Guardar**

### Funcionalidades
- **Historial de compras**: Todas las ventas por cliente
- **Búsqueda rápida** desde POS
- **Creación desde POS**: Sin salir de la venta
- **Información de contacto**: Para marketing y fidelización

## 📊 Dashboard y Reportes

### Dashboard Principal
Muestra en tiempo real:
- **Ventas del día/mes/año**
- **Productos más vendidos**
- **Clientes top**
- **Estado de caja**
- **Alertas críticas**

### Reportes Disponibles
- **Ventas por período**: Diario, semanal, mensual
- **Análisis de productos**: Rentables, estancados, bajo stock
- **Reportes de caja**: Aperturas, cierres, discrepancias
- **Exportación**: Todos los reportes a Excel

## 🔐 Sistema de Seguridad

### Verificación de Documentos
Todos los PDFs importantes incluyen:
- **Hash SHA-256** único
- **Código QR** para verificación
- **Verificación online** en cualquier momento

### Devoluciones Seguras
- **Ventana de 5 minutos** para anulaciones rápidas
- **Autorización requerida** para devoluciones fuera de tiempo
- **Auditoría completa** de cada devolución
- **Trazabilidad** de quién autorizó y cuándo

### Backups Automáticos
- **Diarios a las 3 AM**
- **Retención de 7 días**
- **Restauración point-in-time**
- **Monitoreo** desde panel administrador

## ⚙️ Configuración

### Branding Personalizado
1. **Configuración → Branding**
2. **Sube tu logo** (PNG/JPG, máx 2MB)
3. **Elige colores** principales y secundarios
4. **Preview en tiempo real**
5. **Aplica cambios**

### Configuración de Impuestos
1. **Configuración → Impuestos**
2. **Crear tasas**: IVA 21%, IVA 10.5%, Exento
3. **Asignar a productos** automáticamente

### Usuarios y Permisos
1. **Usuarios → Nuevo Usuario**
2. **Asignar rol**: Admin, Cajero, Supervisor
3. **Permisos granulares** por módulo

## 📱 Uso en Dispositivos Móviles

### POS en Tablets
- **Modo fullscreen** optimizado
- **Touch-friendly** con botones grandes
- **Escáner integrado** en cámaras
- **PWA instalable** en pantalla de inicio

### App Móvil (Próximamente)
- **Sincronización offline**
- **Notificaciones push**
- **Acceso limitado** para vendedores

## ❓ Solución de Problemas

### Problemas Comunes

**¿No puedo acceder al sistema?**
- Verifica tu email y contraseña
- Revisa que no haya espacios extra
- Contacta soporte si olvidaste la contraseña

**¿El POS no responde?**
- Actualiza la página (F5)
- Verifica conexión a internet
- Limpia caché del navegador

**¿Producto no aparece en búsqueda?**
- Verifica ortografía
- Busca por SKU o código de barras
- Revisa que tenga stock disponible

**¿No puedo hacer devolución?**
- Devoluciones solo en primeras 5 minutos
- Para devoluciones posteriores: contacta supervisor
- Requiere autorización especial

### Contacto de Soporte
- **Email**: soporte@kartenant.com
- **WhatsApp**: +54 9 11 1234-5678
- **Horario**: Lunes a Viernes, 9:00 - 18:00 (GMT-3)

## 🎓 Consejos Pro

### Optimización de Inventario
- **Categoriza productos** lógicamente
- **Establece puntos de reorden** realistas
- **Revisa productos estancados** mensualmente
- **Usa códigos de barras** para velocidad

### Gestión de Caja
- **Abre caja** con saldo inicial correcto
- **Cuenta efectivo** al final del día
- **Registra discrepancias** con notas explicativas
- **Revisa reportes** semanalmente

### Atención al Cliente
- **Registra clientes** desde primera venta
- **Ofrece descuentos** a clientes frecuentes
- **Monitorea productos populares**
- **Anticipa necesidades** de reestock

## 🚀 Próximas Funcionalidades

### En Desarrollo
- **Integraciones de pago**: MercadoPago, Stripe, PayPal
- **Facturación electrónica**: AFIP (Argentina), SRI (Ecuador)
- **Multi-sucursal**: Gestiona múltiples locales
- **Empleados y comisiones**: Sistema de vendedores

### Planificación
- **API completa**: Para integraciones con otros sistemas
- **App móvil nativa**: iOS y Android
- **Inteligencia artificial**: Predicciones de demanda
- **E-commerce integrado**: Venta online sincronizada

---

**¿Necesitas ayuda?** No dudes en contactar nuestro soporte. Estamos aquí para ayudarte a hacer crecer tu negocio.

*Última actualización: Octubre 2025*