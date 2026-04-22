# 🚀 Primeros Pasos - Kartenant

¡Felicitaciones! Has instalado Kartenant. Esta guía te ayudará a configurar tu sistema para empezar a vender en minutos.

## 🎯 Objetivos de Esta Guía

Al finalizar tendrás:
- ✅ Tu tienda configurada con branding personalizado
- ✅ Productos básicos cargados
- ✅ Caja abierta y lista para vender
- ✅ Primeras ventas registradas
- ✅ Reportes básicos funcionando

**Tiempo estimado:** 30-45 minutos

---

## 📋 Paso 1: Primer Acceso al Sistema

### 1.1 Crear Super Administrador (Solo Primera Vez)

Si es tu primera instalación, ejecuta:

```bash
php artisan kartenant:make-superadmin
```

Ingresa los datos solicitados:
- **Nombre:** Tu nombre completo
- **Email:** admin@tudominio.com
- **Password:** Elige una contraseña segura (mínimo 8 caracteres)

**Credenciales recomendadas para desarrollo:**
- Email: `admin@kartenant.com`
- Password: `[tu_password]` (cámbialo inmediatamente en producción)

### 1.2 Acceder al Panel de Administración

1. **Ve a la URL de tu instalación**
   ```
   https://tu-dominio.com/admin/login
   ```

2. **Ingresa tus credenciales**
   - **Email:** El email que configuraste con el comando
   - **Contraseña:** La contraseña que elegiste

3. **Verificación 2FA** (si está habilitada)
   - Recibirás un código por email
   - Ingresa el código de 6 dígitos

4. **Cambio de contraseña** (opcional)
   - Puedes cambiar tu contraseña desde el perfil
   - Elige una contraseña segura (8+ caracteres, mayúsculas, números)

### 1.3 Panel de Superadministrador

```
┌─────────────────────────────────────────────────────────┐
│ 🏢 KARTENANT DIGITAL - Panel de Administración             │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 🏪 TENANTS            👥 USUARIOS          📊 SISTEMA   │
│ 📈 ANALYTICS         🔧 CONFIGURACIÓN     📋 LOGS       │
│                                                         │
│ 📊 TENANTS ACTIVOS: 0                                   │
│ 🔄 BACKUPS: ✅                                          │
│ ⚠️  ALERTAS: 0                                          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🏪 Paso 2: Crear tu Primer Tenant (Tienda)

### 2.1 Crear Tenant

1. **Ve a "Tenants" en el menú lateral**
2. **Click "Crear Tenant"**
3. **Completa la información:**

**Información Básica:**
```
Nombre: Mi Ferretería
Dominio: miferreteria
Email de contacto: admin@miferreteria.com
País: Argentina
```

**Configuración:**
```
Plan: Básico ($19/mes)
Idioma: Español
Timezone: America/Argentina/Buenos_Aires
```

4. **Click "Crear"**

### 2.2 Email de Bienvenida

El sistema enviará automáticamente un email con:
- ✅ Credenciales temporales (20 caracteres hex)
- ✅ URL de acceso: `https://miferreteria.tu-dominio.com/app`
- ✅ Instrucciones de configuración inicial
- ✅ Información de seguridad

### 2.3 Acceder al Tenant

1. **Abre el email recibido**
2. **Click en "Acceder a tu tienda"**
3. **Sigue el proceso de verificación 2FA**
4. **Cambia la contraseña temporal**

---

## 🎨 Paso 3: Configurar Branding

### 3.1 Personalizar Logo y Colores

1. **En el panel del tenant, ve a Configuración → Branding**
2. **Sube tu logo:**
   - Formato: PNG o JPG
   - Tamaño máximo: 2MB
   - Resolución recomendada: 200x200px

3. **Elige colores corporativos:**
   - **Color primario:** Azul (#0066CC)
   - **Color secundario:** Gris (#666666)
   - **Texto:** Negro (#000000)

4. **Preview en tiempo real**
5. **Guarda cambios**

### 3.2 Verificar Personalización

- El logo aparecerá en el POS
- Los colores se aplicarán en tickets y PDFs
- Los reportes usarán tu branding

---

## 📦 Paso 4: Configurar Inventario

### 4.1 Crear Categorías

1. **Ve a Inventario → Categorías**
2. **Crea categorías básicas:**
   ```
   - Herramientas
   - Electricidad
   - Pinturas
   - Ferretería General
   ```

### 4.2 Configurar Impuestos

1. **Ve a Configuración → Impuestos**
2. **Crea tasas de IVA:**
   ```
   Nombre: IVA 21%
   Tasa: 21.00%
   País: Argentina
   ```

### 4.3 Agregar Productos

1. **Ve a Inventario → Productos**
2. **Click "Nuevo Producto"**
3. **Ejemplo de producto:**

```
Nombre: Martillo Stanley
Código/SKU: HAM-001
Precio de Venta: $1500.00  ← Precio BASE (sin IVA)
Categoría: Herramientas
Impuesto: IVA 21%
Stock Inicial: 10
Código de Barras: (opcional)
Descripción: Martillo profesional de 16oz
```

4. **Repite para 5-10 productos básicos**

**💡 Tip:** El precio que ingresas es el precio BASE. El sistema calcula automáticamente IVA 21% = $315, total $1815.

### 4.4 Verificar Stock

- Ve al dashboard principal
- Deberías ver "Productos en stock: 5-10"
- Sin alertas de stock bajo

---

## 💰 Paso 5: Abrir Caja Registradora

### 5.1 Acceder al POS

1. **En el menú lateral: "Punto de Venta"**
2. **Se abre en nueva pestaña automáticamente**
3. **Modo fullscreen activado**

### 5.2 Abrir Caja

1. **Ve a POS → Gestión de Caja**
2. **Click "Abrir Caja"**
3. **Completa:**

```
Cajero: [Tu nombre]
Saldo Inicial: $5000.00  ← Efectivo que tienes físicamente
Formato de Comprobante: Térmico 80mm
Observaciones: Apertura de caja - Día inicial
```

4. **Click "Abrir Caja"**
5. **Descarga el comprobante PDF**

### 5.3 Verificar Estado

- En el dashboard: "Estado de Caja: ABIERTA"
- Cajero actual visible
- Saldo inicial registrado

---

## 🛒 Paso 6: Primera Venta

### 6.1 Realizar Venta

1. **En el POS (pestaña fullscreen)**
2. **Busca un producto:** Escribe "martillo" o escanea código
3. **Agrega al carrito:** Click en el producto
4. **Verifica carrito:** Cantidad, precio, subtotal
5. **Procesa pago:** F12 o botón "Procesar Pago"

### 6.2 Completar Pago

```
Método de Pago: Efectivo
Monto Recibido: $2000.00
Cambio: $185.00  ← Calculado automáticamente
Cliente: (opcional)
```

### 6.3 Confirmar Venta

- ✅ **Ticket generado automáticamente**
- ✅ **Stock actualizado** (9 martillos restantes)
- ✅ **Venta registrada** en historial
- ✅ **Caja actualizada** (+$1815 en ventas)

### 6.4 Verificar Resultados

**En Dashboard:**
- 💰 Ventas del día: $1,815
- 📦 Stock actualizado
- 📊 Nueva venta en gráficos

**En Reportes:**
- Venta visible en "Ventas del día"
- Detalle completo disponible

---

## 📊 Paso 7: Cerrar Caja

### 7.1 Cerrar Caja

1. **Ve a POS → Gestión de Caja**
2. **Click "Cerrar Caja"**
3. **Completa el cierre:**

```
Ventas del Día: $1,815.00  ← Del sistema
Efectivo en Caja: $6,815.00  ← Lo que contaste físicamente
  (Saldo inicial $5,000 + Ventas $1,815 - Cambio $0)
```

### 7.2 Verificar Discrepancias

- **Esperado:** $6,815.00
- **Contado:** $6,815.00
- **Diferencia:** $0.00 ✅

### 7.3 Generar Reporte

- ✅ **PDF de cierre generado**
- ✅ **Comprobante verificable con QR**
- ✅ **Historial guardado**

---

## 🎉 Paso 8: Verificar Todo Funciona

### 8.1 Dashboard Principal

Deberías ver:
- ✅ Ventas del día: $1,815
- ✅ Productos en stock: 9
- ✅ Caja cerrada correctamente
- ✅ Sin alertas críticas

### 8.2 Reportes Básicos

1. **Ve a Reportes → Ventas**
2. **Filtra por "Hoy"**
3. **Exporta a Excel** (prueba la funcionalidad)

### 8.3 Verificación de Documentos

1. **Busca el PDF de cierre de caja**
2. **Escanea el código QR**
3. **Verifica autenticidad online**

---

## 🚨 Solución de Problemas Iniciales

### "No puedo acceder al tenant"
- Verifica la URL: `https://tu-tenant.tu-dominio.com/app`
- Revisa el email con credenciales
- Contacta soporte si no llegó el email

### "Productos no aparecen en POS"
- Verifica que tengan stock > 0
- Revisa que estén activos
- Actualiza la página del POS

### "Error al abrir caja"
- Verifica que no haya otra caja abierta
- Revisa permisos de usuario
- Contacta soporte con el error específico

### "Venta no se registra"
- Verifica conexión a internet
- Revisa que la caja esté abierta
- Intenta refrescar la página

---

## 🎯 Próximos Pasos Recomendados

### Día 2: Expansión
1. **Agregar más productos** (20-30 productos)
2. **Crear clientes** desde el POS
3. **Probar diferentes métodos de pago**

### Semana 1: Optimización
1. **Configurar alertas de stock bajo**
2. **Crear usuarios adicionales** (cajeros, supervisores)
3. **Personalizar más el branding**

### Mes 1: Crecimiento
1. **Analizar reportes semanales**
2. **Optimizar productos más vendidos**
3. **Considerar upgrade a plan Profesional**

---

## 📞 ¿Necesitas Ayuda?

**Soporte disponible:**
- 📧 **Email:** soporte@kartenant.com
- 💬 **WhatsApp:** +54 9 11 1234-5678
- 📚 **Documentación completa:** [docs.kartenant.com](https://docs.kartenant.com)

**Recursos adicionales:**
- 🎥 **Videos tutoriales:** [YouTube Channel](https://youtube.com/kartenant)
- 👥 **Comunidad:** [Discord Server](https://discord.gg/kartenant)
- 📖 **Blog:** [blog.kartenant.com](https://blog.kartenant.com)

---

**¡Tu tienda está lista para vender!** 🛒💰

Empieza a registrar ventas y verás cómo Kartenant transforma tu negocio. ¿Preguntas? ¡Estamos aquí para ayudarte!