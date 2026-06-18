# 🔐 Sistema de Seguridad — Kartenant ERP

Kartenant ERP implementa seguridad en capas: desde la autenticación del usuario hasta la verificación de integridad de cada documento generado. Esta guía documenta todos los mecanismos de seguridad activos en el sistema.

---

## 🎯 Filosofía de Seguridad

"Confianza, pero verificación". Cualquier documento que el sistema genera puede ser verificado independientemente por el receptor usando solo el código QR impreso. Sin depender de conexión a internet ni de que el servidor esté disponible.

---

## 🛡️ Capas de Seguridad

### 1. Autenticación

- **Login estándar** con email y contraseña (bcrypt)
- **Two-Factor Authentication (2FA)** — TOTP con apps como Google Authenticator o Authy
- **Renovación forzada de contraseñas** — Política configurable de expiración (ver [password_renewal.md](password_renewal.md))
- **Bloqueo de cuenta** tras N intentos fallidos
- **Sesiones seguras** con CSRF protection en todos los formularios

### 2. Autorización (Roles y Permisos)

Kartenant usa **spatie/laravel-permission** con un sistema de roles jerárquico:

| Rol | Descripción |
|---|---|
| `superadmin` | Acceso total al sistema, configuración de tenants |
| `admin` | Administración del tenant, gestión de usuarios |
| `manager` | Gestión operativa, reportes, sin configuración |
| `cashier` | Acceso solo al POS y operaciones de caja |
| `viewer` | Solo lectura de reportes |

> Ver listado completo de permisos por rol en [PERMISOS_Y_FUNCIONALIDADES.md](PERMISOS_Y_FUNCIONALIDADES.md)

### 3. Aislamiento Multi-Tenant

En modo `APP_MODE=saas`, cada tenant opera en su propia base de datos PostgreSQL. Un usuario de un tenant **nunca puede ver ni acceder** a datos de otro tenant, ya que el nivel de aislamiento es a nivel de base de datos, no solo de filtros de consulta.

> Ver detalles técnicos en [ENHANCED-TENANT-SECURITY.md](ENHANCED-TENANT-SECURITY.md)

### 4. Verificación de Documentos con SHA-256 + QR

Cada documento crítico (facturas, recibos, notas de crédito) incluye:

1. Un **hash SHA-256** calculado sobre el contenido del documento
2. Un **código QR** impreso en el PDF que codifica ese hash
3. Una URL de verificación que el receptor puede usar para confirmar la autenticidad

**Flujo de verificación:**
```
Receptor escanea QR del documento PDF
        ↓
Sistema recalcula el hash del documento almacenado
        ↓
Comparación hash QR vs hash calculado
        ↓
✅ "Documento auténtico" | ❌ "Documento alterado"
```

> Ver documentación completa en [PDF_VERIFICATION_SYSTEM.md](../PDF_VERIFICATION_SYSTEM.md) y [INTERNAL_VERIFICATION_SYSTEM.md](../INTERNAL_VERIFICATION_SYSTEM.md)

### 5. Auditoría de Actividad

Kartenant usa **spatie/laravel-activitylog** para registrar automáticamente:
- Login / logout de usuarios
- Creación, edición y eliminación de registros
- Ajustes de inventario
- Cambios de configuración del sistema

El log de actividad es de **solo lectura** para todos los roles, incluyendo `superadmin`.

### 6. Backups Automáticos

Usando **spatie/laravel-backup**:
- Backup diario automático de base de datos y archivos
- Notificación al admin si el backup falla
- Retención configurable de copias antiguas

> Ver configuración en [SECURITY.md](../../SECURITY.md)

### 7. Protección contra Ataques Comunes

| Amenaza | Mecanismo de protección |
|---|---|
| SQL Injection | Eloquent ORM + queries parametrizadas |
| XSS | Blade templates con escape automático |
| CSRF | Token CSRF en todos los formularios |
| Fuerza bruta | Rate limiting + bloqueo de cuenta |
| Enumeración de usuarios | Mensajes de error genéricos en login |
| Acceso entre tenants | Base de datos separada por tenant |

> Ver análisis detallado en [sql-injection-protection.md](../security/sql-injection-protection.md)

---

## 🔑 Configuración de 2FA

```bash
# El 2FA se activa desde el panel de usuario
# Ruta: /admin/profile → Autenticación de Dos Factores
# Se genera un QR para escanear con Google Authenticator / Authy
```

**Para forzar 2FA a todos los usuarios de un tenant:**

Desde **Admin → Configuración → Seguridad → Requerir 2FA**: Activar.

---

## 🚨 Reporte de Vulnerabilidades

Si descubres una vulnerabilidad de seguridad, **no abras un Issue público**. Sigue el proceso responsable descrito en [SECURITY.md](../../SECURITY.md).

---

## 🔗 Documentos Relacionados

- [Verificación de PDFs](../PDF_VERIFICATION_SYSTEM.md)
- [Verificación Interna](../INTERNAL_VERIFICATION_SYSTEM.md)
- [Protección SQL Injection](../security/sql-injection-protection.md)
- [Patch de Seguridad 2026-03-31](../security/SECURITY-PATCH-2026-03-31.md)
- [Seguridad Mejorada Multi-Tenant](ENHANCED-TENANT-SECURITY.md)
- [Renovación de Contraseñas](password_renewal.md)
- [Permisos y Funcionalidades](PERMISOS_Y_FUNCIONALIDADES.md)
