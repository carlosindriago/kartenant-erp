# CRITICAL FIX: DB_CONNECTION Configuration

## 🎯 Problema Final Identificado

**Error persistente:** `FATAL: database "sail" does not exist (Connection: tenant)`  
**Ocurría en:** Bulk delete de tenants en admin panel

## 🔍 Causa Raíz REAL

El archivo `.env` tenía:
```env
DB_CONNECTION=pgsql
```

Esto causaba que **TODAS** las queries por defecto usaran la conexión `pgsql` en lugar de `landlord`.

### Por Qué Esto Era Problemático

```
Query sin conexión explícita:
DB::table('permissions')->get()
    ↓
Laravel usa DB_CONNECTION del .env
    ↓
DB_CONNECTION=pgsql
    ↓
Pero 'pgsql' === 'landlord' en este caso (misma DB)
    ↓
El problema real: Spatie Permission models cargaban con conexión 'tenant'
    ↓
Tenant connection busca en database "sail" (no existe)
    ↓
ERROR
```

## ✅ Solución Definitiva

### 1. Cambiar `.env`
```env
# ANTES (INCORRECTO)
DB_CONNECTION=pgsql

# DESPUÉS (CORRECTO)
DB_CONNECTION=landlord
```

### 2. Actualizar `.env.example`
```env
# CRITICAL: Must be 'landlord' for multi-tenant architecture
DB_CONNECTION=landlord
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 3. Cambiar `config/permission.php`
```php
// DEFAULT models MUST be Landlord
'permission' => App\Models\Landlord\Permission::class,
'role' => App\Models\Landlord\Role::class,
```

### 4. Limpiar caché
```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

## 📊 Arquitectura Final

### Default Connection Flow

```
┌─────────────────────────────────────────┐
│ Request arrives                         │
├─────────────────────────────────────────┤
│ Laravel loads .env                      │
│ DB_CONNECTION=landlord                  │
├─────────────────────────────────────────┤
│ config/database.php                     │
│ 'default' => env('DB_CONNECTION')      │
│ = 'landlord'                           │
├─────────────────────────────────────────┤
│ config/permission.php                   │
│ Models = Landlord\Permission/Role       │
│ Connection = 'landlord'                 │
├─────────────────────────────────────────┤
│ ALL queries default to landlord         │
│ ✅ Admin panel works                    │
│ ✅ Spatie Permission works              │
│ ✅ No tenant context activation         │
└─────────────────────────────────────────┘
```

### Tenant Context Override (App Panel Only)

```
┌─────────────────────────────────────────┐
│ Request to tenant.domain.test/app       │
├─────────────────────────────────────────┤
│ MakeSpatieTenantCurrent middleware      │
│ resolves tenant by subdomain            │
├─────────────────────────────────────────┤
│ SpatiePermissionsBootstrapper           │
│ switches to tenant models:              │
│ - App\Models\Tenancy\Permission         │
│ - App\Models\Tenancy\Role               │
│ - Connection = 'tenant'                 │
├─────────────────────────────────────────┤
│ Tenant-specific queries                 │
│ ✅ App panel works with tenant data     │
└─────────────────────────────────────────┘
```

## 🎓 Lecciones Clave

### 1. Default Connection es Fundamental
En multi-tenant setup, el default DEBE ser el landlord:
- Admin panel NO tiene tenant context
- Queries sin conexión explícita usan default
- Default = landlord = seguro

### 2. Orden de Prioridad
```
1. Default en .env (landlord)
2. Middlewares ajustan si es necesario (tenant context)
3. Spatie Permission sigue la conexión del modelo
4. Modelos tienen conexión explícita
```

### 3. Testing Connection Config
Use the debug script:
```bash
./vendor/bin/sail php tests/debug-db-connections.php
```

Expected output:
```
✅ Default Connection: landlord
✅ Permission Model: landlord connection
✅ No issues detected!
```

## 🔧 Otros Fixes Aplicados

Para que la solución funcione completamente, se aplicaron también:

1. **Tenant Finder Desactivado**
   - `config/multitenancy.php`: `'tenant_finder' => null`

2. **Middleware Solo en App Panel**
   - `MakeSpatieTenantCurrent` removido de web middleware global
   - Solo presente en `AppPanelProvider`

3. **Query Builder Directo en Middleware**
   - `DisableMultitenancyDuringInstallation` usa `DB::table()` en lugar de Eloquent
   - Evita activación de traits (HasRoles)

4. **Force Landlord en Admin Middlewares**
   - `DisableMultitenancyDuringInstallation`: forget tenant, force landlord
   - `UseLandlordPermissionRegistrar`: DB::setDefaultConnection('landlord')

## ✅ Verificación

### Health Check
```bash
./vendor/bin/sail php tests/system-health-check.php
```
**Expected:** 21/21 tests passing (100%)

### Connection Debug
```bash
./vendor/bin/sail php tests/debug-db-connections.php
```
**Expected:** ✅ No issues detected

### Manual Test
1. Login to `https://kartenant.test/admin`
2. Go to Tenants
3. Select a tenant
4. Bulk action → Delete
5. **Should work without errors** ✅

## 📝 For New Developers

When setting up this project:

1. Copy `.env.example` to `.env`
2. **VERIFY** `DB_CONNECTION=landlord`
3. Run migrations: `sail artisan migrate --seed`
4. Create superadmin: `sail artisan kartenant:make-superadmin`
5. Run health check: `sail php tests/system-health-check.php`

**CRITICAL:** Never change `DB_CONNECTION` to anything other than `landlord` in production.

## 🚨 Troubleshooting

If you see "database sail does not exist":

1. Check `.env`: `DB_CONNECTION=landlord` ✓
2. Clear cache: `sail artisan config:clear`
3. Run debug: `sail php tests/debug-db-connections.php`
4. Check middleware order in `bootstrap/app.php`
5. Verify Spatie models in `config/permission.php`

## 📅 Change History

- **2025-10-09:** Initial fix applied
- **Commits:** 20+ commits over debugging session
- **Final Solution:** DB_CONNECTION=landlord in .env
- **Status:** ✅ RESOLVED

---

*Este documento es CRÍTICO para el mantenimiento del sistema.*  
*NO eliminar o modificar sin entender completamente las implicaciones.*
