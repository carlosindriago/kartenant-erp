# 🔐 Configuración del Sistema de Renovación de Contraseñas

## ✅ Cambios Implementados

### 1. Plugin Instalado
- **Plugin**: `yebor974/filament-renew-password` v2.1
- **Propósito**: Forzar cambio de contraseña en primer login del superadmin

### 2. Migraciones Ejecutadas
- ✅ Agregadas columnas a tabla `users`:
  - `force_renew_password` (boolean, default: false)
  - `last_password_change_at` (timestamp, nullable)

### 3. Modelo User Actualizado
- ✅ Implementa `RenewPasswordContract`
- ✅ Usa trait `RenewPassword`
- ✅ Campos agregados a `$fillable` y `$casts`

### 4. AdminPanelProvider Configurado
- ✅ Plugin registrado con:
  - `forceRenewPassword()` activado
  - Columna personalizada: `force_renew_password`
  - Timestamp: `last_password_change_at`
  - Ruta personalizada: `/admin/cambiar-contrasena`
- ✅ Middleware `ForcePasswordChange` removido (ahora usa el plugin)

### 5. Comando kartenant:make-superadmin Actualizado
- ✅ Ahora establece `force_renew_password = true` siempre
- ✅ Mensaje actualizado para indicar que se requerirá cambio de contraseña

### 6. Nuevo Comando: kartenant:delete-superadmin
- ✅ Permite eliminar superadmin existente (solo desarrollo)
- ✅ Requiere confirmación doble
- ✅ Registra acción en logs

---

## 📋 Comandos para Resetear y Crear Nuevo SuperAdmin

### Paso 1: Eliminar SuperAdmin Existente

```bash
php artisan kartenant:delete-superadmin admin@kartenant.com --force
```

O de forma interactiva:
```bash
php artisan kartenant:delete-superadmin
```

### Paso 2: Crear Nuevo SuperAdmin

```bash
php artisan kartenant:make-superadmin
```

El comando solicitará:
- **Email**: admin@kartenant.com (o el que prefieras)
- **Nombre**: Super Admin (o el que prefieras)
- **Password**: (opcional, se generará automáticamente si no se proporciona)

**Ejemplo con parámetros:**
```bash
php artisan kartenant:make-superadmin admin@kartenant.com --name="Super Admin" --password="TuPasswordSegura123!"
```

### Paso 3: Limpiar Caché

```bash
php artisan optimize:clear
```

---

## 🔄 Flujo de Primer Login

1. **Usuario accede a**: `https://kartenant.test/admin/login`
2. **Ingresa credenciales** del superadmin creado
3. **Sistema detecta** `force_renew_password = true`
4. **Redirige automáticamente a**: `https://kartenant.test/admin/cambiar-contrasena`
5. **Usuario cambia contraseña**
6. **Sistema actualiza**:
   - `force_renew_password = false`
   - `last_password_change_at = now()`
7. **Redirige a**: `https://kartenant.test/admin` (dashboard)

---

## 🧪 Verificación

### Verificar que el plugin está activo:
```bash
php artisan route:list --path=admin/cambiar-contrasena
```

Debería mostrar la ruta del plugin.

### Verificar columnas en base de datos:
```sql
SELECT email, force_renew_password, last_password_change_at, is_super_admin 
FROM users 
WHERE is_super_admin = true;
```

### Verificar usuario creado:
```bash
php artisan tinker
>>> \App\Models\User::where('is_super_admin', true)->first(['name', 'email', 'force_renew_password', 'last_password_change_at'])
```

---

## 📝 Notas Importantes

### Ruta Personalizada
- ❌ **Antigua ruta** (ya no existe): `/admin/force-password-change`
- ✅ **Nueva ruta** (plugin): `/admin/cambiar-contrasena`

### Archivos Obsoletos (pueden eliminarse)
- `app/Http/Middleware/ForcePasswordChange.php` (ya no se usa)
- `app/Filament/Pages/Auth/ForcePasswordChange.php` (ya no se usa)
- `resources/views/filament/pages/auth/force-password-change.blade.php` (si existe)

### Seguridad
- ✅ El comando `kartenant:make-superadmin` solo permite crear UN superadmin
- ✅ El comando `kartenant:delete-superadmin` solo funciona en desarrollo (salvo variable de entorno)
- ✅ Todas las acciones se registran en logs
- ✅ Alertas a Slack si está configurado

---

## 🚀 Comandos Rápidos (Resumen)

```bash
# 1. Eliminar superadmin actual
php artisan kartenant:delete-superadmin admin@kartenant.com --force

# 2. Crear nuevo superadmin
php artisan kartenant:make-superadmin admin@kartenant.com --name="Super Admin" --password="Password123!"

# 3. Limpiar caché
php artisan optimize:clear

# 4. Verificar
php artisan route:list --path=admin/cambiar
```

---

## 🔧 Troubleshooting

### Error: "Ya existe un Super Administrador"
```bash
# Eliminar el existente primero
php artisan kartenant:delete-superadmin
```

### Error: Ruta no encontrada
```bash
# Limpiar caché de rutas
php artisan route:clear
php artisan optimize:clear
```

### Error: Columnas no existen
```bash
# Ejecutar migración
php artisan migrate --database=landlord --path=database/migrations/landlord
```

### Plugin no funciona
```bash
# Verificar que el plugin está instalado
composer show yebor974/filament-renew-password

# Reinstalar si es necesario
composer require yebor974/filament-renew-password:^2.0
```

---

**Fecha de implementación**: 2025-11-10
**Versión del plugin**: 2.1.x
**Compatibilidad**: Filament v3.x
