# 🛠️ Guía de Instalación — Kartenant ERP

Esta guía te ayudará a instalar y configurar Kartenant ERP en tu entorno local o servidor de producción.

> **Repositorio oficial:** https://github.com/carlosindriago/kartenant-erp

---

## 📋 Prerrequisitos del Sistema

### Requisitos Mínimos
- **SO**: Linux (Ubuntu 20.04+), macOS, o Windows con WSL2
- **PHP**: 8.2 o superior
- **Base de datos**: PostgreSQL 15+
- **Web Server**: Nginx o Apache
- **RAM**: 2 GB mínimo, 4 GB recomendado
- **Disco**: 10 GB disponible
- **Node.js**: 18+

### Extensiones PHP requeridas

```bash
php8.2-cli php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl
```

---

## 🚀 Instalación Local (Docker — Recomendado)

```bash
# 1. Clonar el repositorio
git clone https://github.com/carlosindriago/kartenant-erp.git
cd kartenant-erp

# 2. Instalar dependencias PHP
composer install

# 3. Copiar configuración
cp .env.example .env
```

### Modos de operación — APP_MODE

Edita tu `.env` y elige el modo según tu caso:

```bash
# Modo standalone: una sola base de datos, ideal para un negocio
APP_MODE=standalone

# Modo SaaS: base de datos por tenant, para operar múltiples clientes
APP_MODE=saas
```

```bash
# 4. Levantar contenedores
./vendor/bin/sail up -d

# 5. Generar key de aplicación
./vendor/bin/sail artisan key:generate

# 6. Ejecutar migraciones del landlord
./vendor/bin/sail artisan migrate --database=landlord --path=database/migrations/landlord

# 7. Cargar datos iniciales
./vendor/bin/sail php scripts/seed-landlord.php

# 8. Crear usuario superadministrador
./vendor/bin/sail artisan kartenant:make-superadmin

# 9. Instalar y compilar assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

✅ Accede al panel en: **http://localhost/admin/login**

---

## ⚙️ Instalación Nativa (sin Docker)

```bash
# 1. Clonar el repositorio
git clone https://github.com/carlosindriago/kartenant-erp.git
cd kartenant-erp

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Ejecutar migraciones
php artisan migrate --database=landlord --path=database/migrations/landlord

# 5. Cargar datos iniciales
php scripts/seed-landlord.php

# 6. Crear superadmin
php artisan kartenant:make-superadmin

# 7. Compilar assets
npm run build

# 8. Iniciar servidor
php artisan serve
```

---

## 🗄️ Configuración de PostgreSQL

```sql
-- Crear usuario y bases de datos
CREATE USER kartenant_user WITH PASSWORD 'tu_password_segura';
CREATE DATABASE landlord OWNER kartenant_user;
GRANT ALL PRIVILEGES ON DATABASE landlord TO kartenant_user;

-- Si usas APP_MODE=saas, los tenant DBs se crean automáticamente
-- Si usas APP_MODE=standalone, crea una DB adicional:
CREATE DATABASE kartenant OWNER kartenant_user;
GRANT ALL PRIVILEGES ON DATABASE kartenant TO kartenant_user;
```

---

## ⚙️ Variables de Entorno Clave

```bash
# Aplicación
APP_NAME="Kartenant ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_MODE=saas   # standalone | saas

# Base de datos landlord
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=landlord
DB_USERNAME=kartenant_user
DB_PASSWORD=tu_password_segura

# Cache y colas (Redis recomendado en producción)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="noreply@tu-dominio.com"
MAIL_FROM_NAME="Kartenant ERP"
```

---

## 🔐 Crear Superadministrador

```bash
# Método oficial
php artisan kartenant:make-superadmin
# El comando pedirá: nombre, email y contraseña
```

Accede al panel admin en: `https://tu-dominio.com/admin/login`

---

## 🌐 Configuración Nginx

```nginx
server {
    listen 80;
    server_name tu-dominio.com *.tu-dominio.com;
    root /var/www/kartenant-erp/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht { deny all; }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 🚀 Optimización para Producción

```bash
# Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Compilar assets
npm run build

# Permisos correctos
sudo chown -R www-data:www-data /var/www/kartenant-erp
sudo chmod -R 775 storage bootstrap/cache
```

### Checklist pre-producción

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` con dominio real y HTTPS
- [ ] PostgreSQL configurado y accesible
- [ ] SSL activo
- [ ] Superadmin creado
- [ ] Migraciones ejecutadas
- [ ] Assets compilados (`npm run build`)
- [ ] Cache optimizado (`artisan optimize`)

---

## 🔧 Troubleshooting

| Problema | Solución |
|---|---|
| Error de conexión a BD | `./vendor/bin/sail artisan tinker` → `DB::connection()->getPdo()` |
| Assets no cargan | `php artisan optimize:clear && npm run build` |
| Permisos denegados | `sudo chown -R www-data:www-data . && chmod -R 775 storage` |
| Migraciones fallan | Verificar que la DB `landlord` exista y las credenciales en `.env` sean correctas |

Ver también: [Guía de Troubleshooting completa](../TROUBLESHOOTING.md)

---

## 📞 Soporte

- **🐛 Reportar Issues:** [GitHub Issues](https://github.com/carlosindriago/kartenant-erp/issues)
- **🔒 Vulnerabilidades de seguridad:** Ver [SECURITY.md](../../SECURITY.md)

---

*¡Tu instalación está lista!* 🎉 Visita `https://tu-dominio.com/admin/login` para comenzar.
