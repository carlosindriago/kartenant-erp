# 🌍 GESTIÓN DE ZONA HORARIA POR TENANT

## ✅ **IMPLEMENTACIÓN COMPLETA**

Cada tenant puede tener su propia zona horaria configurada, que se aplica automáticamente cuando acceden a su panel.

---

## 🎯 **¿CÓMO FUNCIONA?**

### **1. Configuración Automática**

Cuando un usuario accede al panel de un tenant específico:

```
Usuario → tornillostore.kartenant.test
         ↓
Middleware detecta tenant
         ↓
TenantTimezoneBootstrapper se ejecuta
         ↓
Configura timezone del tenant automáticamente
         ↓
Todas las fechas/horas usan timezone del tenant
```

**Zona horaria por defecto:** `America/Argentina/Buenos_Aires` (GMT-3)

---

## 📋 **ZONAS HORARIAS DISPONIBLES**

### **América Latina:**
```
🇦🇷 Argentina - Buenos Aires (GMT-3)
🇦🇷 Argentina - Córdoba (GMT-3)
🇦🇷 Argentina - Mendoza (GMT-3)
🇦🇷 Argentina - Salta (GMT-3)
🇺🇾 Uruguay - Montevideo (GMT-3)
🇨🇱 Chile - Santiago (GMT-3/GMT-4)
🇧🇷 Brasil - São Paulo (GMT-3)
🇵🇪 Perú - Lima (GMT-5)
🇨🇴 Colombia - Bogotá (GMT-5)
🇲🇽 México - Ciudad de México (GMT-6)
```

### **Otros:**
```
🇺🇸 Estados Unidos - Nueva York (GMT-5/GMT-4)
🇺🇸 Estados Unidos - Los Ángeles (GMT-8/GMT-7)
🇪🇸 España - Madrid (GMT+1/GMT+2)
🌐 UTC (GMT+0)
```

---

## 🔧 **CONFIGURACIÓN DESDE EL PANEL ADMIN**

### **Al crear un nuevo tenant:**

1. Ve a **Panel Admin** → **Tenants** → **Crear**
2. Completa los datos de la empresa
3. En la sección **"Suscripción"**:
   - Selecciona el **Plan**
   - **Selecciona la Zona Horaria** (campo con búsqueda)
   - Opcionalmente: Fecha de fin de prueba
4. Completa datos del administrador
5. Haz clic en **"Crear"**

**El tenant usará la zona horaria seleccionada automáticamente.**

---

### **Para editar la zona horaria de un tenant existente:**

1. Ve a **Panel Admin** → **Tenants**
2. Haz clic en el botón **"Editar"** (✏️) del tenant
3. Cambia la **Zona Horaria**
4. Guarda los cambios

**Los cambios se aplican inmediatamente** (el usuario debe refrescar su sesión).

---

## 👁️ **VISUALIZACIÓN EN LA TABLA**

En el listado de tenants verás:

| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| **Nombre** | Nombre de la empresa | Kiwi Store |
| **Dominio** | Subdominio del tenant | kiwistore |
| **Plan** | Badge con el plan (trial/básico/pro) | 🟡 Trial |
| **Zona Horaria** | Zona horaria configurada | Argentina/Buenos Aires |
| **Creado** | Fecha de creación | 13/10/2025 04:28 |

**🔍 Tooltip en Zona Horaria:**
Al pasar el mouse sobre la zona horaria, verás la **hora actual del tenant**.

---

## 💻 **USO EN EL CÓDIGO**

### **Opción 1: Usar el helper now() (recomendado)**

```php
// Esto automáticamente usa la zona horaria del tenant
$fechaActual = now();
$fechaFormateada = now()->format('d/m/Y H:i:s');

// Crear fecha en el pasado/futuro
$ayer = now()->subDay();
$mañana = now()->addDay();
```

### **Opción 2: Usar el helper personalizado**

```php
use App\Helpers\TenantTimeHelper;

// Obtener hora actual del tenant
$ahora = TenantTimeHelper::now();

// Obtener zona horaria del tenant
$timezone = TenantTimeHelper::getTimezone(); // "America/Argentina/Buenos_Aires"

// Convertir fecha UTC a zona horaria del tenant
$fechaUTC = '2025-10-13 05:00:00'; // UTC
$fechaTenant = TenantTimeHelper::toTenantTime($fechaUTC);

// Formatear fecha con zona horaria del tenant
$formatted = TenantTimeHelper::format($fecha, 'd/m/Y H:i:s');
```

### **Opción 3: Manual (no recomendado)**

```php
$tenant = \Spatie\Multitenancy\Models\Tenant::current();
$timezone = $tenant->timezone;

$fecha = Carbon::now()->setTimezone($timezone);
```

---

## 📊 **IMPACTO EN EL SISTEMA**

### **Qué se ve afectado:**

✅ **POS (Punto de Venta)**
- Hora de ventas
- Hora de apertura/cierre de caja
- Timestamps en facturas

✅ **Inventario**
- Movimientos de stock
- Fechas de entrada/salida de productos

✅ **Reportes**
- Filtros de fecha (hoy, ayer, esta semana)
- Exports de datos

✅ **Auditoría**
- Logs de actividad
- Historial de cambios

✅ **Notificaciones**
- Emails con fechas
- Alertas del sistema

---

## 🔄 **FUNCIONAMIENTO TÉCNICO**

### **Arquitectura:**

```
┌─────────────────────────────────────────────────┐
│  Usuario accede a tenant (kiwistore)            │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Middleware: MakeSpatieTenantCurrent            │
│  - Identifica tenant por subdominio             │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Switch Tenant Tasks (en orden):                │
│  1. SwitchTenantDatabaseTask                    │
│     → Cambia conexión a BD del tenant           │
│  2. SpatiePermissionsBootstrapper               │
│     → Configura permisos del tenant             │
│  3. TenantTimezoneBootstrapper ⬅️ NUEVO         │
│     → Configura timezone del tenant             │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Todas las funciones de fecha usan:            │
│  - config('app.timezone') = tenant timezone     │
│  - now() = hora del tenant                      │
│  - Carbon::now() = hora del tenant              │
└─────────────────────────────────────────────────┘
```

### **Componentes creados:**

**1. Migración:**
```
database/migrations/landlord/2025_10_13_045712_add_timezone_to_tenants_table.php
```

**2. Bootstrapper:**
```php
app/Services/Multitenancy/TenantTimezoneBootstrapper.php
```

**3. Helper:**
```php
app/Helpers/TenantTimeHelper.php
```

**4. Configuración:**
```php
config/multitenancy.php → switch_tenant_tasks
```

---

## 🧪 **TESTING**

### **Verificar que funciona:**

**1. Crear tenant con timezone diferente:**
```bash
# Desde panel admin, crear tenant con timezone "America/Lima" (GMT-5)
```

**2. Verificar en consola:**
```bash
./vendor/bin/sail artisan tinker --execute="
\$tenant = App\Models\Tenant::where('domain', 'kiwistore')->first();
\$tenant->makeCurrent();
echo 'Timezone configurado: ' . config('app.timezone') . PHP_EOL;
echo 'Hora actual tenant: ' . now()->format('Y-m-d H:i:s T') . PHP_EOL;
"
```

**3. Ver en el POS:**
- Accede al POS del tenant
- Realiza una venta
- Verifica que la hora de la venta corresponde a la zona horaria configurada

**4. Ver en logs:**
Si `APP_DEBUG=true`, verás en logs:
```
🌍 Timezone configurado para tenant
  tenant_id: 2
  tenant_name: Kiwi Store
  timezone: America/Argentina/Buenos_Aires
  current_time: 2025-10-13 01:00:00
```

---

## 🎓 **CASOS DE USO**

### **Caso 1: Tenant en Argentina con clientes en España**

```
Tenant: FerreteroBA (Buenos Aires, GMT-3)
Cliente: KartenantMadrid (España, GMT+1)

Solución:
- FerreteroBA usa timezone: America/Argentina/Buenos_Aires
- KartenantMadrid usa timezone: Europe/Madrid
- Cada uno ve sus datos en su hora local
```

### **Caso 2: Franquicia con sucursales en diferentes zonas**

```
Matriz: México DF (GMT-6)
Sucursal 1: Buenos Aires (GMT-3)
Sucursal 2: Lima (GMT-5)

Solución:
- Crear 3 tenants, cada uno con su timezone
- Los reportes consolidados se pueden convertir a UTC
```

### **Caso 3: Tenant con operaciones 24/7**

```
Centro de operaciones: Santiago de Chile (GMT-3/GMT-4)

Configuración:
- Usar timezone: America/Santiago
- El sistema respeta horario de verano automáticamente
```

---

## 🚀 **PRÓXIMAS MEJORAS (FUTURO)**

### **Potenciales features:**

**1. Timezone por usuario (no por tenant):**
```
- Permitir que cada usuario dentro de un tenant tenga su propia timezone
- Útil para equipos distribuidos geográficamente
```

**2. Formato de fecha personalizado:**
```
- dd/mm/yyyy (Latino América)
- mm/dd/yyyy (USA)
- yyyy-mm-dd (ISO)
```

**3. Idioma del tenant:**
```
- Español (es)
- Portugués (pt-BR)
- Inglés (en)
```

---

## ❓ **PREGUNTAS FRECUENTES**

### **¿Qué pasa si cambio la timezone de un tenant?**
Los registros existentes mantienen su timestamp UTC original. Al mostrarlos, se convierten automáticamente a la nueva timezone.

### **¿Cómo se almacenan las fechas en la base de datos?**
Laravel siempre almacena en UTC. La conversión a timezone del tenant es solo para visualización.

### **¿Afecta el horario de verano (DST)?**
Sí, las zonas horarias de PHP manejan automáticamente DST (ej: Chile, USA).

### **¿Puedo tener tenants en diferentes países?**
Sí, cada tenant tiene su propia timezone independiente.

### **¿Funciona con el sistema de cron/jobs?**
Los jobs se ejecutan en UTC por defecto. Si necesitas que un job respete la timezone del tenant, debes configurarlo explícitamente.

---

## 📚 **RECURSOS**

**Documentación oficial:**
- [PHP Timezones](https://www.php.net/manual/es/timezones.php)
- [Laravel Dates](https://laravel.com/docs/11.x/helpers#dates-and-time)
- [Carbon Documentation](https://carbon.nesbot.com/docs/)

**Lista completa de zonas horarias:**
```php
php artisan tinker
>>> timezone_identifiers_list();
```

---

**Última actualización:** 2025-10-13 00:05
**Autor:** Sistema Kartenant
**Estado:** ✅ IMPLEMENTADO Y FUNCIONAL
