# 🔑 Autenticación y Autorización — Kartenant ERP

Esta guía documenta todos los mecanismos de autenticación (¿quién eres?) y autorización (¿qué puedes hacer?) implementados en Kartenant ERP.

---

## 🏗️ Arquitectura de Acceso

Kartenant tiene **dos paneles separados** con sus propios flujos de autenticación:

| Panel | URL | Usuarios |
|---|---|---|
| **Landlord Admin** | `/admin` | Superadmin, operadores de plataforma |
| **Tenant App** | `/app` (o subdominio) | Admins de tienda, cajeros, supervisores |

---

## 🔐 Autenticación

### Login Estándar

- Email + contraseña (hash **bcrypt**, cost factor 12)
- Protección CSRF en el formulario
- Rate limiting: máximo **5 intentos** en 60 segundos por IP
- Bloqueo temporal de cuenta tras intentos excesivos
- Mensajes de error genéricos (no revela si el email existe)

### Two-Factor Authentication (2FA)

Kartenant implementa **TOTP** (Time-based One-Time Password):

1. El usuario activa 2FA desde **Perfil → Seguridad → Activar 2FA**
2. Se genera un código QR para escanear con Google Authenticator, Authy o similar
3. En cada login, después de email+contraseña, se solicita el código de 6 dígitos
4. Los códigos expiran en 30 segundos

**Forzar 2FA para todos los usuarios de un tenant:**

Desde **Admin → Configuración → Seguridad → Requerir 2FA**: Activar.

### Renovación de Contraseñas

Política de expiración configurable. Ver detalles completos en [password_renewal.md](../features/password_renewal.md).

```
Contraseña expira cada N días (configurable por tenant)
        ↓
El usuario es redirigido al formulario de cambio
        ↓
No puede acceder a ninguna otra sección hasta cambiarla
        ↓
Nueva contraseña no puede ser igual a las últimas 5
```

### Recuperación de Contraseña

Flujo estándar de reset por email con token de un solo uso y expiración de 60 minutos. Ver [password-recovery-system.md](../password-recovery-system.md).

---

## 👥 Autorización — Roles y Permisos

Kartenant usa **spatie/laravel-permission** con un esquema de roles jerárquicos.

### Roles del Panel Landlord

| Rol | Descripción |
|---|---|
| `superadmin` | Control total de la plataforma, gestión de todos los tenants |
| `platform_operator` | Soporte técnico, acceso de solo lectura a tenants |

### Roles del Panel Tenant

| Rol | Descripción |
|---|---|
| `admin` | Administración completa del tenant |
| `manager` | Gestión operativa, reportes, sin configuración crítica |
| `cashier` | Acceso exclusivo al POS y operaciones de caja |
| `viewer` | Solo lectura de reportes |

### Cómo crear un usuario con rol específico

Desde **Admin → Usuarios → Nuevo Usuario**:

```
Nombre:    Juan Cajero
Email:     juan@mitienda.com
Rol:       cashier
Activo:    ✅
```

El usuario recibirá un email con credenciales temporales y deberá cambiar su contraseña en el primer acceso.

### Asignar permisos granulares adicionales

```php
// Via Artisan tinker — solo si se necesita un permiso fuera del rol estándar
$user = User::find($id);
$user->givePermissionTo('reports.export');
```

> Ver listado completo de permisos disponibles en [PERMISOS_Y_FUNCIONALIDADES.md](../features/PERMISOS_Y_FUNCIONALIDADES.md)

---

## 🔒 Sesiones

- Driver de sesión: `redis` (producción) / `file` (desarrollo)
- Duración de sesión: **120 minutos** de inactividad (configurable)
- Cierre de sesión en todos los dispositivos disponible desde el perfil
- Las sesiones se invalidan al cambiar contraseña

---

## 🛡️ Protecciones Adicionales

| Amenaza | Mecanismo |
|---|---|
| CSRF | Token CSRF en todos los formularios (Laravel nativo) |
| XSS | Escape automático en Blade templates |
| Clickjacking | Header `X-Frame-Options: DENY` |
| Enumeración de usuarios | Mensajes de error genéricos en login y reset |
| Fuerza bruta | Rate limiting por IP + bloqueo temporal de cuenta |
| Session fixation | Regeneración de ID de sesión en cada login |

---

## ⚙️ Configuración en .env

```bash
# Duración de sesión (en minutos)
SESSION_LIFETIME=120

# Driver de sesión (redis recomendado en producción)
SESSION_DRIVER=redis

# Forzar HTTPS en producción
SESSION_SECURE_COOKIE=true
```

---

## 🔗 Documentos Relacionados

- [Sistema de Seguridad completo](../features/security-system.md)
- [Renovación de contraseñas](../features/password_renewal.md)
- [Recuperación de contraseña](../password-recovery-system.md)
- [Permisos y Funcionalidades](../features/PERMISOS_Y_FUNCIONALIDADES.md)
- [Seguridad Mejorada Multi-Tenant](../features/ENHANCED-TENANT-SECURITY.md)
- [Patch de Seguridad 2026-03-31](SECURITY-PATCH-2026-03-31.md)
- [Reportar vulnerabilidades](../../SECURITY.md)
