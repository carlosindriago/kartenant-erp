# 🚀 Primeros Pasos — Kartenant ERP

¡Felicitaciones! Has instalado Kartenant ERP. Esta guía te ayudará a configurar tu sistema para empezar a vender en minutos.

## 🎯 Objetivos de Esta Guía

Al finalizar tendrás:
- ✅ Tu tienda configurada con branding personalizado
- ✅ Productos básicos cargados
- ✅ Caja abierta y lista para vender
- ✅ Primeras ventas registradas
- ✅ Reportes básicos funcionando

**Tiempo estimado:** 30–45 minutos

> ℹ️ Si aún no instalaste el sistema, empieza por la [Guía de Instalación](installation.md).

---

## 📋 Paso 1: Primer Acceso al Sistema

### 1.1 Crear Super Administrador (Solo Primera Vez)

Si es tu primera instalación, ejecuta:

```bash
# Con Docker (Sail)
./vendor/bin/sail artisan kartenant:make-superadmin

# Sin Docker
php artisan kartenant:make-superadmin
```

El comando pedirá:
- **Nombre:** Tu nombre completo
- **Email:** `admin@tudominio.com`
- **Password:** Mínimo 8 caracteres

### 1.2 Acceder al Panel de Administración

1. Ve a la URL de tu instalación:
   ```
   http://localhost/admin/login        ← desarrollo local
   https://tu-dominio.com/admin/login  ← producción
   ```
2. Ingresa el email y contraseña del paso anterior
3. Si 2FA está habilitado, ingresa el código de 6 dígitos de tu app autenticadora

### 1.3 Panel de Superadministrador

```
┌─────────────────────────────────────────────────────────┐
│ 🏢 KARTENANT ERP — Panel de Administración         │
├─────────────────────────────────────────────────────────┤
│ 🏦 TENANTS      👥 USUARIOS      📊 SISTEMA      │
│ 📈 ANALYTICS   🔧 CONFIGURACIÓN  📋 LOGS         │
│                                                       │
│ 📊 TENANTS ACTIVOS: 0                               │
│ 🔄 BACKUPS: ✅       ⚠️  ALERTAS: 0               │
└─────────────────────────────────────────────────────────┘
```

---

## 🏦 Paso 2: Crear tu Primer Tenant (Tienda)

> ℹ️ Este paso aplica cuando `APP_MODE=saas`. Si usas `APP_MODE=standalone`, salta al **Paso 3** directamente.

### 2.1 Crear Tenant

1. Ve a **"Tenants"** en el menú lateral
2. Click **"Crear Tenant"**
3. Completa la información:

```
Nombre:             Mi Ferretería
Dominio:            miferreteria
Email de contacto:  admin@miferreteria.com
País:              (el tuyo)
Timezone:           America/Bogota  ← o la de tu país
```

4. Click **"Crear"**

### 2.2 Email de Bienvenida

El sistema envía automáticamente al admin del tenant:
- ✅ Credenciales temporales
- ✅ URL de acceso: `https://miferreteria.tu-dominio.com/app`
- ✅ Instrucciones de configuración inicial

### 2.3 Acceder al Tenant

1. Abre el email recibido
2. Click en **"Acceder a tu tienda"**
3. Cambia la contraseña temporal en el primer login

---

## 🎨 Paso 3: Configurar Branding

1. En el panel del tenant ve a **Configuración → Branding**
2. Sube tu logo (PNG/JPG, máx 2 MB, recomendado 200×200 px)
3. Elige colores corporativos con el selector visual
4. Preview en tiempo real → **Guardar**

El logo y colores se aplican automáticamente en el POS, tickets y PDFs.

---

## 📦 Paso 4: Configurar Inventario

### 4.1 Crear Categorías

**Inventario → Categorías → Nueva Categoría**

Ejemplos para una ferretería:
```
Herramientas | Electricidad | Pinturas | Ferretería General
```

### 4.2 Configurar Impuestos

**Configuración → Impuestos → Nueva Tasa**

```
Nombre:  IVA 12%   (ajústar al país: 12%, 16%, 19%, 21%...)
Tasa:    12.00%
```

### 4.3 Agregar Productos

**Inventario → Productos → Nuevo Producto**

```
Nombre:          Martillo Stanley
Código/SKU:     HAM-001
Precio de Venta: 15.00   ← Precio BASE sin impuesto
Categoría:      Herramientas
Impuesto:        IVA 12%
Stock Inicial:   10
Código de Barras: (opcional, para escáner)
```

> 💡 El precio ingresado es el precio **BASE**. El sistema calcula y muestra el precio final con impuesto automáticamente en el POS.

---

## 💰 Paso 5: Abrir Caja Registradora

1. En el menú lateral: **"Punto de Venta"** (se abre en pantalla completa)
2. Ve a **POS → Gestión de Caja → Abrir Caja**
3. Completa:

```
Cajero:        [Tu nombre]
Saldo Inicial: 500.00   ← Efectivo físico que tienes en caja
Observaciones: Apertura de caja - Primer día
```

4. Click **"Abrir Caja"** → descarga el comprobante PDF

Verifica en el dashboard: **"Estado de Caja: ABIERTA"**

---

## 🛒 Paso 6: Primera Venta

1. En el POS, busca un producto: escribe el nombre o escanea el código de barras
2. Click en el producto para agregarlo al carrito
3. Verifica cantidad y precio
4. Presiona **F12** o el botón **"Procesar Pago"**
5. Completa el pago:

```
Método de Pago:  Efectivo
Monto Recibido:  20.00
Cambio:          X.XX  ← calculado automáticamente
```

**Resultado inmediato:**
- ✅ Ticket generado y listo para imprimir o enviar
- ✅ Stock actualizado automáticamente
- ✅ Venta registrada en historial
- ✅ Caja actualizada

---

## 📊 Paso 7: Cerrar Caja

1. **POS → Gestión de Caja → Cerrar Caja**
2. Ingresa el efectivo físico contado en caja
3. El sistema muestra la diferencia entre lo esperado y lo contado
4. Confirma el cierre → se genera un **PDF firmado con QR** verificable

---

## ✓ Paso 8: Verificar que Todo Funciona

### Dashboard Principal
- ✅ Ventas del día con monto correcto
- ✅ Stock de productos actualizado
- ✅ Caja en estado "Cerrada"
- ✅ Sin alertas críticas

### Verificar documento con QR

1. Busca el PDF de cierre de caja
2. Escanea el código QR con tu celular
3. El sistema confirma: **"Documento auténtico ✅"**

> Ver detalles técnicos del sistema de verificación en [PDF_VERIFICATION_SYSTEM.md](../PDF_VERIFICATION_SYSTEM.md)

---

## 🚊 Solución de Problemas Iniciales

| Problema | Solución |
|---|---|
| No puedo acceder al tenant | Verifica la URL y revisa el email con credenciales |
| Productos no aparecen en POS | Verifica que tengan stock > 0 y estén activos |
| Error al abrir caja | Verifica que no haya otra caja ya abierta en el mismo turno |
| Venta no se registra | Verifica que la caja esté abierta y refresca la página |

Ver también: [Guía de Troubleshooting completa](../TROUBLESHOOTING.md)

---

## 🎯 Próximos Pasos Recomendados

**Día 2:** Agrega más productos (20–30), crea clientes desde el POS, prueba diferentes métodos de pago.

**Semana 1:** Configura alertas de stock bajo, crea usuarios adicionales (cajeros, supervisores), revisa reportes diarios.

**Mes 1:** Analiza reportes semanales, identifica los productos más vendidos, evalúa activar módulos adicionales.

---

## 📞 ¿Necesitas Ayuda?

- **🐛 Reportar un bug:** [GitHub Issues](https://github.com/carlosindriago/kartenant-erp/issues)
- **🔒 Vulnerabilidades de seguridad:** Ver [SECURITY.md](../../SECURITY.md) — no abrir Issues públicos
- **🤝 Contribuir:** Ver [CONTRIBUTING.md](../../CONTRIBUTING.md)

---

*¡Tu tienda está lista para vender!* 🛒💰
