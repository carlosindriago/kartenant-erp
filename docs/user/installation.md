# 🛠️ Guía de Instalación - Kartenant

Esta guía te ayudará a instalar y configurar Kartenant en tu servidor o entorno local.

## 📋 Prerrequisitos del Sistema

### Requisitos Mínimos
- **SO**: Linux (Ubuntu 20.04+), macOS, o Windows con WSL2
- **PHP**: 8.2 o superior
- **Base de datos**: PostgreSQL 15+
- **Web Server**: Nginx o Apache
- **RAM**: 2GB mínimo, 4GB recomendado
- **Disco**: 10GB disponible
- **Node.js**: 18+ (para assets)

### Dependencias de PHP
```bash
# Extensiones requeridas
php8.2-cli php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl
```

## 🚀 Instalación Rápida (Desarrollo Local)

### Opción 1: Docker (Recomendado)

```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/kartenant.git
cd kartenant

# 2. Instalar dependencias PHP
composer install

# 3. Copiar configuración
cp .env.example .env

# 4. Configurar base de datos en .env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=kartenant
DB_USERNAME=sail
DB_PASSWORD=password

# 5. Levantar contenedores
./vendor/bin/sail up -d

# 6. Generar key de aplicación
./vendor/bin/sail artisan key:generate

# 7. Ejecutar migraciones
./vendor/bin/sail artisan migrate

# 8. Cargar datos iniciales (seeders)
./vendor/bin/sail exec laravel.test php seed-landlord.php

# 9. Crear usuario administrador
./vendor/bin/sail artisan kartenant:make-superadmin

# 10. Instalar dependencias frontend
./vendor/bin/sail npm install

# 11. Compilar assets
./vendor/bin/sail npm run build

# ✅ Listo! Accede a http://localhost
```

### Opción 2: Instalación Nativa

```bash
# 1. Instalar PHP y PostgreSQL
sudo apt update
sudo apt install php8.2 postgresql postgresql-contrib

# 2. Crear base de datos
sudo -u postgres createdb kartenant
sudo -u postgres createuser --interactive --pwprompt kartenant_user

# 3. Clonar y configurar
git clone https://github.com/tu-usuario/kartenant.git
cd kartenant
composer install
cp .env.example .env

# 4. Configurar .env
php artisan key:generate

# 5. Ejecutar migraciones
php artisan migrate

# 6. Cargar datos iniciales (seeders)
php seed-landlord.php

# 7. Crear usuario administrador
php artisan kartenant:make-superadmin

# 8. Instalar assets
npm install && npm run build

# 9. Iniciar servidor
php artisan serve
```

## ⚙️ Configuración Avanzada

### Variables de Entorno (.env)

```bash
# Aplicación
APP_NAME="Kartenant"
APP_ENV=production
APP_KEY=base64:tu_clave_generada
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kartenant
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# Cache y sesiones
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (opcional pero recomendado)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@tu-dominio.com"
MAIL_FROM_NAME="${APP_NAME}"

# Storage
FILESYSTEM_DISK=local

# Multitenancy
TENANCY_LANDLORD_DATABASE=landlord
TENANCY_TENANT_DATABASE_PREFIX=tenant_
```

### Configuración de Nginx

```nginx
server {
    listen 80;
    server_name tu-dominio.com *.tu-dominio.com;
    root /var/www/kartenant/public;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    # Cache estático
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### SSL con Let's Encrypt

```bash
# Instalar certbot
sudo apt install certbot python3-certbot-nginx

# Generar certificado
sudo certbot --nginx -d tu-dominio.com -d *.tu-dominio.com

# Configurar renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 🗄️ Configuración de Base de Datos

### PostgreSQL Setup

```bash
# Crear usuario y base de datos
sudo -u postgres psql

CREATE USER kartenant_user WITH PASSWORD 'tu_password_segura';
CREATE DATABASE kartenant OWNER kartenant_user;
CREATE DATABASE landlord OWNER kartenant_user;
GRANT ALL PRIVILEGES ON DATABASE kartenant TO kartenant_user;
GRANT ALL PRIVILEGES ON DATABASE landlord TO kartenant_user;

# Salir
\q
```

### Migraciones Iniciales

```bash
# Ejecutar todas las migraciones
php artisan migrate

# Para tenants específicos
php artisan tenants:artisan "migrate --database=tenant" --tenant=1

# Seeders
php artisan db:seed
php artisan tenants:artisan "db:seed" --tenant=1
```

## 🔐 Configuración de Seguridad

### Usuario Superadministrador

```bash
# ✅ MÉTODO OFICIAL - Crear superadmin
php artisan kartenant:make-superadmin

# El comando solicitará:
# - Nombre completo del administrador
# - Email (ejemplo: admin@tudominio.com)
# - Password (mínimo 8 caracteres)
```

**Credenciales recomendadas para desarrollo:**
- Email: `admin@kartenant.com`
- Password: `[tu_password]` (cámbialo inmediatamente en producción)

**Acceso al panel:**
```
http://tu-dominio.com/admin/login
```

**❌ NO usar tinker manualmente** (a menos que sea estrictamente necesario):
```bash
# Solo como alternativa si el comando no está disponible
php artisan tinker
>>> \App\Models\User::create(['name'=>'Admin','email'=>'admin@tu-dominio.com','password'=>\Hash::make('password')]);
```

### Permisos de Archivos

```bash
# Ajustar permisos
sudo chown -R www-data:www-data /var/www/kartenant
sudo chmod -R 755 /var/www/kartenant
sudo chmod -R 775 /var/www/kartenant/storage
sudo chmod -R 775 /var/www/kartenant/bootstrap/cache
```

### Firewall

```bash
# UFW básico
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
```

## 🚀 Puesta en Producción

### Checklist Pre-Producción

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://tu-dominio.com`
- [ ] Base de datos configurada
- [ ] SSL configurado
- [ ] Usuario admin creado
- [ ] Migraciones ejecutadas
- [ ] Assets compilados
- [ ] Cache optimizado

### Optimización de Performance

```bash
# Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Compilar assets para producción
npm run build

# Configurar queue worker (opcional)
php artisan queue:work --daemon
```

### Monitoreo

```bash
# Instalar herramientas básicas
sudo apt install htop iotop ncdu

# Configurar logrotate
sudo nano /etc/logrotate.d/kartenant
```

## 🧪 Verificación de Instalación

### Tests Básicos

```bash
# Verificar PHP
php --version

# Verificar Composer
composer --version

# Verificar Laravel
php artisan --version

# Verificar base de datos
php artisan migrate:status

# Verificar assets
ls -la public/build/

# Verificar permisos
ls -la storage/
```

### URLs de Verificación

- **Aplicación**: https://tu-dominio.com
- **Admin Panel**: https://tu-dominio.com/admin
- **Health Check**: https://tu-dominio.com/health
- **API Docs**: https://tu-dominio.com/docs/api

## 🔧 Troubleshooting

### Problemas Comunes

**Error de conexión a BD**
```bash
# Verificar credenciales
php artisan tinker
>>> DB::connection()->getPdo();
```

**Assets no cargan**
```bash
# Limpiar y recompilar
php artisan optimize:clear
npm run build
```

**Permisos denegados**
```bash
# Ajustar ownership
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

**Migraciones fallan**
```bash
# Reset y re-run
php artisan migrate:reset
php artisan migrate
```

## 📞 Soporte

¿Problemas con la instalación?

- **📧 Email**: soporte@kartenant.com
- **💬 WhatsApp**: +54 9 11 1234-5678
- **📚 Documentación**: [docs.kartenant.com](https://docs.kartenant.com)
- **🐛 Issues**: [GitHub Issues](https://github.com/tu-usuario/kartenant/issues)

---

**¡Tu instalación está lista!** 🎉

Visita `https://tu-dominio.com` para comenzar a usar Kartenant.