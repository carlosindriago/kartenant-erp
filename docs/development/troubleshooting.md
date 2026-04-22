# 🚨 Troubleshooting Guide - Emporio Digital

Documentación de problemas críticos y sus soluciones implementadas en Emporio Digital SaaS multi-tenant.

---

## 📋 Índice

- [Filament v3 Placeholder Badge Issue](#-filament-v3-placeholder-badge-issue)
- [404 on SoftDeleted Records](#-404-on-softdeleted-records)
- [Docker Permissions for File Operations](#-docker-permissions-for-file-operations)
- [Vite & Node Permission Errors](#-vite--node-permission-errors)
- [Error Monitoring System Schema Updates](#-error-monitoring-system-schema-updates)
- [Tenant Route Model Binding](#-tenant-route-model-binding)

---

## 🔧 Filament v3 Placeholder Badge Issue

**Problem Date:** November 2025
**Status:** ✅ FIXED
**Files Affected:** Multiple Filament Resources

### 🐛 Problem Description

Al actualizar componentes de Filament v3, aparecía el siguiente error:

```
Call to undefined method Filament\Forms\Components\Placeholder::badge()
```

Este error ocurría en form schemas que intentaban aplicar badges a componentes `Placeholder`.

### 🎯 Root Cause

En Filament v3, el método `badge()` no existe en la clase `Filament\Forms\Components\Placeholder`.
Este método solo está disponible en:

- `Filament\Infolists\Components\TextEntry` (para infolists)
- `Filament\Tables\Columns\TextColumn` (para tablas)

### ✅ Solution

Reemplazar `Placeholder::badge()` con alternativas apropiadas según el contexto:

#### Option A: Usar `TextEntry` en Infolists (RECOMENDADO)
```php
// ❌ ANTES (Error)
Forms\Components\Placeholder::make('status')
    ->label('Estado')
    ->content(fn ($record) => $record->status_label)
    ->badge() // ← Método no existe
    ->color(fn ($record) => $record->status_color);

// ✅ DESPUÉS (Correcto)
Components\TextEntry::make('status')
    ->label('Estado')
    ->badge()
    ->color(fn ($record) => $record->status_color)
    ->formatStateUsing(fn ($state) => match($state) {
        'archived' => 'Archivado 📦',
        default => $state,
    });
```

#### Option B: Usar `Text` con HTML personalizado en Forms
```php
// ✅ ALTERNATIVA para Forms
Forms\Components\Text::make('status_display')
    ->label('Estado')
    ->formatStateUsing(function ($record) {
        return new HtmlString(sprintf(
            '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-%s-100 text-%s-800">%s</span>',
            $record->status_color === 'success' ? 'green' : 'red',
            $record->status_color === 'success' ? 'green' : 'red',
            $record->status_label
        ));
    })
    ->disabled();
```

### 📍 Files Fixed

- `app/Filament/Resources/ArchivedTenantResource.php` - Líneas 69-74
- `app/Filament/Resources/TenantResource.php` - Similar patterns
- Other Resources using Placeholder::badge()

### 🔍 Prevention Strategy

**Golden Rule:**
- **Forms**: Usar `Text` o `Placeholder` SIN `badge()`
- **Infolists**: Usar `TextEntry` CON `badge()`
- **Tables**: Usar `TextColumn` CON `badge()`

---

## 🎯 404 on SoftDeleted Records

**Problem Date:** November 2025
**Status:** ✅ FIXED
**Files Affected:** ArchivedTenantResource, ViewArchivedTenant

### 🐛 Problem Description

Al intentar ver un tenant archivado (soft-deleted) en el admin panel:

```
404 Not Found
ModelNotFoundException: No query results for model [App\Models\Tenant]
```

### 🎯 Root Cause

Laravel's implicit route model binding excluye automáticamente los registros soft-deleted
(`deleted_at IS NOT NULL`). Al intentar acceder a `/admin/archived-tenants/{tenant}`,
Laravel no encontraba el registro porque estaba archivado.

### ✅ Solution

Override el método `resolveRecord()` en las páginas View para incluir registros soft-deleted:

```php
// app/Filament/Resources/ArchivedTenantResource/Pages/ViewArchivedTenant.php

/**
 * Resolve record for ViewArchivedTenant page.
 * CRITICAL: Must include withTrashed() to find soft-deleted tenants.
 *
 * @param  int | string  $key
 * @return \Illuminate\Database\Eloquent\Model
 */
protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
{
    return static::getResource()::getEloquentQuery()
        ->withTrashed() // <--- CRITICAL FIX: Include soft-deleted records
        ->findOrFail($key);
}
```

### 🔍 Pattern for All Soft-Deleted Resources

```php
// 1. Resource Query (in Resource class)
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScope(SoftDeletingScope::class) // Remove default scope
        ->whereNotNull('deleted_at') // Only show soft-deleted
        ->where('status', Model::STATUS_ARCHIVED);
}

// 2. Page Resolution (in View page)
protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
{
    return static::getResource()::getEloquentQuery()
        ->withTrashed() // Include soft-deleted
        ->findOrFail($key);
}
```

### 📍 Files Fixed

- `app/Filament/Resources/ArchivedTenantResource/Pages/ViewArchivedTenant.php` - Lines 27-32
- `app/Filament/Resources/ArchivedTenantResource.php` - Lines 722-727
- `tests/Feature/ArchivedTenantViewTest.php` - Test verification

### 🧪 Testing

```php
// tests/Feature/ArchivedTenantViewTest.php
test('archived tenant view loads correctly', function () {
    $tenant = \App\Models\Tenant::factory()->create([
        'status' => \App\Models\Tenant::STATUS_ARCHIVED,
        'deleted_at' => now()->subDays(30),
    ]);

    $response = $this->get("/admin/archived-tenants/{$tenant->domain}");
    $response->assertStatus(200);
    $response->assertSee($tenant->name);
});
```

---

## 🐳 Docker Permissions for File Operations

**Problem Date:** November 2025
**Status:** ✅ FIXED
**Files Affected:** Development environment, Vite, Tests

### 🐛 Problem Description

Errores de permisos durante desarrollo:

```bash
EACCES: permission denied, open '/var/www/html/storage/logs/laravel.log'
EACCES: permission denied, unlink '/var/www/html/storage/framework/cache/data/...'
Vite: Error watching files for changes...
```

### 🎯 Root Cause

Archivos creados como `root` user en Docker containers no son accesibles
para el proceso web (user `sail` - UID 1000).

### ✅ Solution

#### Option A: Usar Laravel Sail (RECOMENDADO)

```bash
# ✅ CORRECTO - Siempre usar Sail
./vendor/bin/sail artisan make:controller NewController
./vendor/bin/sail artisan tinker
./vendor/bin/sail npm run dev
./vendor/bin/sail composer require package/name

# ✅ CORRECTO - Crear archivos con usuario sail
./vendor/bin/sail exec -u sail laravel.test touch storage/logs/new.log
./vendor/bin/sail exec -u sail laravel.test mkdir new-directory
```

#### Option B: Corregir permisos de archivos existentes

```bash
# Si archivos fueron creados como root:
sudo chown -R $USER:$(id -gn) /path/to/project

# O específicamente para archivos Laravel:
sudo chown -R $USER:$(id -gn) storage/
sudo chown -R $USER:$(id -gn) bootstrap/cache/
```

#### Option C: Configurar VSCode/SFTP

```json
// .vscode/settings.json
{
    "remote.SSH.remoteServerListenOnSocket": false,
    "files.associations": {
        "*.php": "php"
    }
}
```

### 📍 Files Affected

- `public/` - Assets compilados por Vite
- `storage/logs/` - Logs de Laravel
- `storage/framework/cache/` - Caché
- `bootstrap/cache/` - Config cache
- `node_modules/` - Dependencias npm

### 🔍 Prevention Best Practices

1. **Always use Sail for commands:**
   ```bash
   ./vendor/bin/sail artisan <command>
   ./vendor/bin/sail npm <command>
   ```

2. **Never create files as root:**
   ```bash
   # ❌ INCORRECTO
   docker exec -it laravel.test root bash
   touch newfile.php

   # ✅ CORRECTO
   ./vendor/bin/sail exec -u sail laravel.test touch newfile.php
   ```

3. **Verify ownership before commits:**
   ```bash
   # Verificar dueño de archivos nuevos
   find . -newer .git/HEAD -not -user $USER -ls
   ```

### 🧪 Testing Environment

```bash
# Test Vite hot reload
./vendor/bin/sail npm run dev

# Test cache operations
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# Test file operations
./vendor/bin/sail artisan logs:clear
```

---

## ⚡ Vite & Node Permission Errors

**Problem Date:** November 2025
**Status:** ✅ FIXED
**Files Affected:** Vite temp files, public/hot, node_modules

### 🐛 Problem Description

Vite crashes immediately on startup with `EACCES` permission errors when running `npm run dev` via Laravel Sail. This is a critical blocking issue that prevents development workflow.

**Common Error Messages:**
```bash
EACCES: permission denied, open '/var/www/html/node_modules/.vite-temp/index.html-aBc123.js'
EACCES: permission denied, open 'public/hot'
Vite: Error: EACCES: permission denied, mkdir '/var/www/html/node_modules/.vite-temp'
Error: watch public ENOENT
```

**Development Impact:**
- Hot module reloading (HMR) completely broken
- Development server fails to start
- Frontend compilation errors
- Productivity loss for developers

### 🎯 Root Cause Analysis

This occurs when Docker processes (running as root) create temporary files that the `sail` user (UID 1000) cannot overwrite or access:

1. **Temp File Ownership:** Vite needs write access to `node_modules/.vite-temp/` directory
2. **HMR Socket:** Hot reload creates `public/hot` file for WebSocket connection
3. **Permission Mismatch:** Root-owned files (UID 0) vs sail user (UID 1000)
4. **Container Context:** File permissions get out of sync between host and container

### ✅ Solution Protocol

#### Scenario 1: Error mentions `node_modules/.vite-temp`

**Symptoms:** Vite immediately crashes with permission denied in temp directory.

```bash
# Step 1: From host machine - Remove locked temp folder
sudo rm -rf node_modules/.vite-temp

# Step 2: Inside container - Restore ownership to sail user
./vendor/bin/sail exec -u root laravel.test chown -R sail:sail node_modules

# Step 3: Clear any residual cache
./vendor/bin/sail exec -u sail laravel.test rm -rf node_modules/.vite

# Step 4: Restart development server
./vendor/bin/sail npm run dev
```

#### Scenario 2: Error mentions `public/hot` file

**Symptoms:** HMR not working, WebSocket connection errors.

```bash
# Step 1: From host machine - Remove the locked hotfile
sudo rm -f public/hot

# Step 2: Inside container - Restore ownership of public folder
./vendor/bin/sail exec -u root laravel.test chown -R sail:sail public

# Step 3: Ensure Vite can write to public directory
./vendor/bin/sail exec -u sail laravel.test chmod 755 public

# Step 4: Restart development server
./vendor/bin/sail npm run dev
```

#### Scenario 3: Complete Cleanup (Persistent Issues)

**When:** Problems persist after individual fixes or multiple permission issues.

```bash
# Step 1: Complete permission restoration from host
sudo chown -R $USER:$USER ./

# Step 2: Inside container - Fix all common permission issues
./vendor/bin/sail exec -u root laravel.test chown -R sail:sail \
  node_modules \
  public \
  storage \
  bootstrap/cache

# Step 3: Set correct permissions
./vendor/bin/sail exec -u root laravel.test chmod -R 755 public
./vendor/bin/sail exec -u root laravel.test chmod -R 755 storage
./vendor/bin/sail exec -u root laravel.test chmod -R 755 bootstrap/cache

# Step 4: Clear all caches
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan config:clear

# Step 5: Restart containers completely
./vendor/bin/sail down
./vendor/bin/sail up -d

# Step 6: Start development
./vendor/bin/sail npm run dev
```

#### Scenario 4: Emergency Fix (Development Deadline)

**When:** Quick fix needed, can afford to reinstall dependencies.

```bash
# Step 1: Remove node_modules completely
sudo rm -rf node_modules

# Step 2: Clear package-lock.json if corrupted
rm -f package-lock.json

# Step 3: Reinstall with Sail (correct permissions)
./vendor/bin/sail npm install

# Step 4: Start development
./vendor/bin/sail npm run dev
```

### 🔍 Prevention Strategy

#### Best Practices for Development

**1. Always use Laravel Sail for npm commands:**
```bash
# ✅ CORRECT
./vendor/bin/sail npm run dev
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# ❌ INCORRECT
docker exec -it laravel.test npm run dev  # Defaults to root
npm run dev  # Outside container
```

**2. Avoid running npm scripts as root:**
```bash
# ❌ NEVER DO THIS
docker exec -u root laravel.test npm run dev
docker exec -it laravel.test bash  # Then npm run dev as root

# ✅ ALWAYS DO THIS
./vendor/bin/sail exec -u sail laravel.test npm run dev
# Or simply:
./vendor/bin/sail npm run dev
```

**3. Include in Docker Compose health checks:**
```yaml
# docker-compose.yml (optional enhancement)
services:
  laravel.test:
    # ... existing config
    healthcheck:
      test: ["CMD", "test", "-w", "/var/www/html/node_modules/.vite-temp"]
      interval: 30s
      timeout: 10s
      retries: 3
```

**4. Add to project onboarding documentation:**
```bash
# First-time setup for new developers
git clone repository
cd repository
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev  # Test Vite permissions immediately
```

### 🛠️ Development Environment Setup

#### Optimal Docker Configuration

```bash
# .env file settings for development
SAIL_SHARE_DASHBOARD=false
SAIL_SHARE_SERVER_PORT=8080
VITE_APP_NAME="${APP_NAME}"
```

#### VSCode Integration

```json
// .vscode/settings.json
{
    "terminal.integrated.profiles.linux": {
        "bash": {
            "path": "bash",
            "args": ["-lc", "cd /var/www/html && bash"]
        }
    },
    "files.watcherExclude": {
        "**/node_modules/**": true,
        "**/vendor/**": true,
        "**/storage/framework/**": true
    }
}
```

### 🧪 Testing Vite Permission Fixes

#### Verification Commands

```bash
# Test 1: Check Vite startup
./vendor/bin/sail npm run dev
# Should see: Local: http://localhost:5173/

# Test 2: Verify temp directory creation
ls -la node_modules/.vite-temp/
# Should show sail:sail ownership

# Test 3: Check HMR functionality
# Modify a CSS file and verify hot reload works

# Test 4: Verify public/hot file
ls -la public/hot
# Should exist with sail:sail ownership
```

#### Automated Test Script

```bash
#!/bin/bash
# scripts/test-vite-permissions.sh

echo "🧪 Testing Vite Permissions..."

# Test temp directory creation
echo "1. Testing .vite-temp directory..."
./vendor/bin/sail exec -u sail laravel.test mkdir -p node_modules/.vite-temp
if [ $? -eq 0 ]; then
    echo "✅ Temp directory creation: OK"
else
    echo "❌ Temp directory creation: FAILED"
    exit 1
fi

# Test public directory write access
echo "2. Testing public directory access..."
./vendor/bin/sail exec -u sail laravel.test touch public/test-vite-permissions
if [ $? -eq 0 ]; then
    echo "✅ Public directory access: OK"
    ./vendor/bin/sail exec -u sail laravel.test rm public/test-vite-permissions
else
    echo "❌ Public directory access: FAILED"
    exit 1
fi

# Test npm permissions
echo "3. Testing npm commands..."
./vendor/bin/sail npm run build
if [ $? -eq 0 ]; then
    echo "✅ npm permissions: OK"
else
    echo "❌ npm permissions: FAILED"
    exit 1
fi

echo "🎉 All Vite permission tests passed!"
```

### 🔧 Debugging Vite Issues

#### Common Scenarios and Solutions

**1. Vite starts but HMR doesn't work:**
```bash
# Check if public/hot exists and has correct permissions
ls -la public/hot
# If missing or wrong permissions:
sudo rm -f public/hot
./vendor/bin/sail npm run dev
```

**2. Vite can't watch files:**
```bash
# Check file watcher limits
./vendor/bin/sail exec -u sail laravel.test cat /proc/sys/fs/inotify/max_user_watches

# Increase if needed (temporary)
./vendor/bin/sail exec -u root laravel.test echo 819200 > /proc/sys/fs/inotify/max_user_watches
```

**3. Port conflicts:**
```bash
# Check what's using port 5173
./vendor/bin/sail exec -u sail laravel.test netstat -tlnp | grep 5173

# Kill process if needed
./vendor/bin/sail exec -u sail laravel.test pkill -f "vite"
```

### 📊 Monitoring and Health Checks

#### Health Check Script

```bash
#!/bin/bash
# scripts/vite-health-check.sh

VITE_PID=$(pgrep -f "vite")

if [ -z "$VITE_PID" ]; then
    echo "❌ Vite is not running"
    exit 1
fi

# Check if temp directory is accessible
if [ ! -w "node_modules/.vite-temp" ]; then
    echo "❌ Vite temp directory not writable"
    exit 1
fi

# Check if HMR file exists
if [ ! -f "public/hot" ]; then
    echo "❌ HMR file missing"
    exit 1
fi

echo "✅ Vite is healthy"
exit 0
```

### 📍 Files and Directories Affected

**Critical Files/Directories:**
- `node_modules/.vite-temp/` - Vite temporary compilation files
- `public/hot` - HMR WebSocket connection file
- `public/build/` - Compiled assets
- `node_modules/` - npm dependencies
- `storage/logs/` - Laravel logs (can affect Vite error reporting)

**Permission Patterns:**
```bash
# Correct ownership pattern
sail:sail node_modules/
sail:sail public/
sail:sail storage/
sail:sail bootstrap/cache/

# Correct permissions
755 directories
644 files
```

### 🔍 Integration with CI/CD

#### Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit

echo "🔍 Checking Vite permissions..."

# Check if Vite can start (quick test)
./vendor/bin/sail npm run build

if [ $? -ne 0 ]; then
    echo "❌ Vite build failed - check permissions"
    exit 1
fi

echo "✅ Vite permissions OK"
```

### 📚 References

- [Laravel Sail Documentation](https://laravel.com/docs/sail)
- [Vite Configuration Guide](https://vitejs.dev/config/)
- [Docker User Management](https://docs.docker.com/engine/reference/user/)
- [Linux File Permissions](https://www.linux.com/learn/file-permissions-linux)

---

## 📊 Error Monitoring System Schema Updates

**Problem Date:** November 2025
**Status:** ✅ FIXED
**Files Affected:** bug_reports table, error monitoring

### 🐛 Problem Description

El sistema de monitoreo de errores fallaba con:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column "file" does not exist
```

### 🎯 Root Cause

La tabla `bug_reports` no tenía la columna `file` requerida por el ErrorMonitoringService.

### ✅ Solution

Agregar migración para la columna faltante:

```php
// database/migrations/landlord/2025_11_25_000006_add_file_to_bug_reports.php
public function up(): void
{
    Schema::table('bug_reports', function (Blueprint $table) {
        $table->string('file')->nullable()->after('stack_trace');
        $table->unsignedInteger('line')->nullable()->after('file');
    });
}
```

### 🔍 Prevention Strategy

- Validar schema antes de implementar nuevas funcionalidades
- Incluir todas las columnas requeridas en migraciones iniciales
- Testear con datos reales antes de producción

---

## 🔗 Tenant Route Model Binding

**Problem Date:** November 2025
**Status:** ✅ FIXED
**Files Affected:** Tenant model, ArchivedTenantResource

### 🐛 Problem Description

Implicit route binding no funcionaba para tenants con estados especiales:

```php
// URL: /admin/archived-tenants/{tenant}
// Error: 404 - Tenant not found (pero existe en BD)
```

### 🎯 Root Cause

Route binding estándar no incluye `withTrashed()` ni considera estados personalizados.

### ✅ Solution

```php
// app/Models/Tenant.php
public function resolveRouteBinding($value, $field = null)
{
    return $this->where($field ?? 'id', $value)
        ->withTrashed() // Incluir archivados
        ->firstOrFail();
}

// O en Resource específico
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withTrashed() // Incluir soft-deleted
        ->where('status', self::STATUS_ARCHIVED);
}
```

### 🔍 Pattern for Multi-Status Models

```php
// 1. Override route binding
public function resolveRouteBinding($value, $field = null)
{
    return $this->where($field ?? $this->getRouteKeyName(), $value)
        ->withTrashed()
        ->firstOrFail();
}

// 2. Custom route key if needed
public function getRouteKeyName()
{
    return 'domain'; // o 'slug', 'uuid', etc.
}

// 3. Resource-specific query
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScope(SoftDeletingScope::class)
        ->where('status', 'desired_status');
}
```

---

## 🛡️ Testing & Verification

### Automated Tests

```bash
# Test archived tenant access
./vendor/bin/sail test tests/Feature/ArchivedTenantViewTest.php

# Test soft-deleted route binding
./vendor/bin/sail test tests/Feature/TenantRouteBindingTest.php

# Test file permissions
./vendor/bin/sail test tests/Feature/FilePermissionsTest.php
```

### Manual Verification

```bash
# 1. Verificar archived tenant view
# Crear tenant archivado y visitar /admin/archived-tenants/{id}

# 2. Verificar badges en infolists
# Visitar recursos con badges y confirmar que muestran correctamente

# 3. Verificar permisos de archivos
ls -la storage/logs/
ls -la public/css/
```

### Health Monitoring

```bash
# Monitorear sistema de errores
./vendor/bin/sail artisan monitor:errors

# Verificar logs
./vendor/bin/sail artisan logs:show --level=error

# Health check general
curl -f http://localhost/health || echo "Health check failed"
```

---

## 📚 References

- [Laravel Route Model Binding](https://laravel.com/docs/routing#route-model-binding)
- [Laravel Soft Deleting](https://laravel.com/docs/eloquent#soft-deleting)
- [Filament v3 Forms](https://filamentphp.com/docs/3.x/panels/forms)
- [Laravel Sail Documentation](https://laravel.com/docs/sail)
- [Docker User Management](https://docs.docker.com/engine/reference/user/)

---

**Última actualización:** 30 de Noviembre de 2025
**Próxima revisión:** Diciembre 2025
**Maintainer:** Carlos Indriago