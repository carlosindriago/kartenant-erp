# Sistema de Recuperación de Contraseña para Tenants

## 🎯 Descripción

Este sistema permite enviar emails de recuperación de contraseña a los usuarios de los tenants (empleados) utilizando una notificación personalizada que evita el problema de compatibilidad de Laravel 12 con el método `MailChannel::make()`.

## 🔧 Componentes del Sistema

### 1. Notificación Personalizada
**Archivo:** `app/Notifications/PasswordResetNotification.php`

Notificación personalizada que:
- Implementa `ShouldQueue` para procesamiento asíncrono
- Usa el método `via()` con canal `'mail'`
- Evita el problema `MailChannel::make()` de Laravel 12
- Genera emails en español con branding de Emporio Digital

### 2. Método Personalizado en User Model
**Archivo:** `app/Models/User.php`

Se agregó el método `sendPasswordResetNotification($token)` que:
- Intercepta el llamado estándar de Laravel
- Usa nuestra notificación personalizada
- Reemplaza la notificación por defecto que causa el error

### 3. Comando Artisan
**Archivo:** `app/Console/Commands/SendPasswordResetEmail.php`

Comando para enviar manualmente emails de recuperación:
```bash
php artisan emporio:send-password-reset {email} [--tenant={subdomain}]
```

## 📖 Uso del Sistema

### Para enviar un email de recuperación:

#### Opción 1: Usar el comando Artisan (Recomendado)
```bash
# Enviar a cualquier usuario
php artisan emporio:send-password-reset email@ejemplo.com

# Enviar especificando el tenant
php artisan emporio:send-password-reset email@ejemplo.com --tenant=cocostore
```

#### Opción 2: Uso automático del sistema
Cuando un usuario solicita recuperación de contraseña a través del formulario:
1. Laravel llama automáticamente a `user->sendPasswordResetNotification($token)`
2. Nuestro método personalizado intercepta el llamado
3. Se usa nuestra notificación personalizada
4. Se evita el error `MailChannel::make()`

## 🎨 Email que recibe el usuario

El email enviado contiene:
- **Asunto:** "Restablecer Contraseña - Emporio Digital"
- **Saludo personalizado:** "Hola [Nombre del usuario]"
- **Mensaje explicativo:** Información sobre la solicitud
- **Botón de acción:** "Restablecer Contraseña" con enlace único
- **Tiempo de expiración:** Información sobre validez del enlace
- **Seguridad:** Notificación sobre no hacer nada si no se solicitó
- **Firma:** "Saludos, el equipo de Emporio Digital"

## 🔍 Verificación y Troubleshooting

### Verificar el token generado:
El comando muestra el token generado:
```bash
🔑 Token: abc123def456...
🔗 Enlace de recuperación: https://tudominio.com/reset-password/abc123def456...
```

### Errores comunes:
1. **Usuario no encontrado:** `❌ No se encontró ningún usuario con el email`
   - Verificar el email exacto en la base de datos
2. **Tenant no encontrado:** `❌ No se encontró el tenant`
   - Verificar el subdomain del tenant
3. **Error de envío:** `❌ Error al enviar el email`
   - Revisar configuración SMTP en `.env`

### Configuración requerida en `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.tuproveedor.com
MAIL_PORT=587
MAIL_USERNAME=tuemail@tudominio.com
MAIL_PASSWORD=tucontraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@tudominio.com"
MAIL_FROM_NAME="Emporio Digital"
```

## 🚀 Flujo Completo de Recuperación

1. **Usuario solicita recuperación** → Formulario en el login del tenant
2. **Laravel genera token** → `Password::createToken($user)`
3. **Nuestro método intercepta** → `sendPasswordResetNotification()`
4. **Se envía email personalizado** → `PasswordResetNotification`
5. **Usuario recibe email** → Con enlace de recuperación único
6. **Usuario restablece contraseña** → Formulario con validación de token
7. **Acceso restaurado** → Usuario puede hacer login con nueva contraseña

## 🎯 Ventajas de esta Solución

✅ **Compatible con Laravel 12** - Evita el problema `MailChannel::make()`
✅ **En español** - Mejor UX para usuarios latinos
✅ **Branding consistente** - Emails con identidad Emporio Digital
✅ **Procesamiento asíncrono** - No bloquea la aplicación
✅ **Seguro** - Tokens únicos con tiempo de expiración
✅ **Flexible** - Funciona con y sin especificar tenant
✅ **Debugging friendly** - Comando con información detallada

## 📞 Soporte

Si tienes problemas con el sistema de recuperación:
1. Revisa la configuración SMTP en `.env`
2. Verifica que el usuario exista en la base de datos
3. Usa el comando manual para debugging: `php artisan emporio:send-password-reset email@ejemplo.com`
4. Revisa los logs de Laravel: `storage/logs/laravel.log`