# 🚀 Guía de Deployment - Kartenant

Documentación completa para desplegar Kartenant en producción.

## 📋 Checklist Pre-Deployment

### ✅ Requisitos del Servidor

- **OS**: Ubuntu 20.04 LTS o superior
- **CPU**: 2 cores mínimo, 4 recomendado
- **RAM**: 4GB mínimo, 8GB recomendado
- **Disco**: 20GB SSD mínimo
- **Red**: 100Mbps mínimo

### ✅ Servicios Requeridos

- **Web Server**: Nginx 1.20+
- **Database**: PostgreSQL 15+
- **PHP**: 8.2+ con FPM
- **SSL**: Certificado Let's Encrypt
- **Queue**: Redis o database
- **Cache**: Redis recomendado

### ✅ Configuración de Dominio

```
Dominio principal: tu-dominio.com
Wildcard SSL: *.tu-dominio.com (para tenants)
DNS A record: @ -> IP_SERVIDOR
DNS CNAME: * -> @
```

## 🏗️ Instalación Automatizada

### Script de Deployment

**deploy.sh:**
```bash
#!/bin/bash

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}🚀 Iniciando deployment de Kartenant${NC}"

# Variables
APP_NAME="kartenant"
DOMAIN="tu-dominio.com"
DB_NAME="kartenant"
DB_USER="kartenant_user"
DB_PASS="$(openssl rand -base64 12)"

# Actualizar sistema
echo -e "${YELLOW}📦 Actualizando sistema...${NC}"
apt update && apt upgrade -y

# Instalar dependencias
echo -e "${YELLOW}🔧 Instalando dependencias...${NC}"
apt install -y curl wget gnupg2 software-properties-common

# Instalar Nginx
echo -e "${YELLOW}🌐 Instalando Nginx...${NC}"
apt install -y nginx
systemctl enable nginx

# Instalar PHP 8.2
echo -e "${YELLOW}🐘 Instalando PHP 8.2...${NC}"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-pgsql

# Instalar PostgreSQL
echo -e "${YELLOW}🐘 Instalando PostgreSQL...${NC}"
apt install -y postgresql postgresql-contrib

# Instalar Redis
echo -e "${YELLOW}🔄 Instalando Redis...${NC}"
apt install -y redis-server
systemctl enable redis-server

# Instalar Node.js
echo -e "${YELLOW}📱 Instalando Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Instalar Composer
echo -e "${YELLOW}🎼 Instalando Composer...${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar PostgreSQL
echo -e "${YELLOW}🗄️ Configurando PostgreSQL...${NC}"
sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';"
sudo -u postgres psql -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;"

# Crear directorio de la aplicación
echo -e "${YELLOW}📁 Creando directorio de aplicación...${NC}"
mkdir -p /var/www/$APP_NAME
chown -R www-data:www-data /var/www/$APP_NAME

# Clonar repositorio
echo -e "${YELLOW}📥 Clonando repositorio...${NC}"
cd /var/www/$APP_NAME
git clone https://github.com/tu-usuario/kartenant.git .
git checkout main

# Instalar dependencias PHP
echo -e "${YELLOW}📦 Instalando dependencias PHP...${NC}"
composer install --no-dev --optimize-autoloader

# Configurar entorno
echo -e "${YELLOW}⚙️ Configurando entorno...${NC}"
cp .env.example .env
sed -i "s/APP_NAME=.*/APP_NAME=\"$APP_NAME\"/" .env
sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
sed -i "s/APP_URL=.*/APP_URL=https:\/\/$DOMAIN/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env

# Generar key
php artisan key:generate

# Ejecutar migraciones
echo -e "${YELLOW}🗄️ Ejecutando migraciones...${NC}"
php artisan migrate --force

# Crear usuario administrador
echo -e "${YELLOW}👤 Creando usuario administrador...${NC}"
php artisan kartenant:create-superadmin

# Instalar dependencias frontend
echo -e "${YELLOW}📱 Instalando dependencias frontend...${NC}"
npm ci
npm run build

# Optimizar Laravel
echo -e "${YELLOW}⚡ Optimizando Laravel...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Configurar Nginx
echo -e "${YELLOW}🌐 Configurando Nginx...${NC}"
cat > /etc/nginx/sites-available/$APP_NAME << EOF
server {
    listen 80;
    server_name $DOMAIN *.$DOMAIN;
    root /var/www/$APP_NAME/public;

    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Logs
    access_log /var/log/nginx/$APP_NAME.access.log;
    error_log /var/log/nginx/$APP_NAME.error.log;
}
EOF

ln -s /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# Configurar SSL
echo -e "${YELLOW}🔒 Configurando SSL...${NC}"
apt install -y certbot python3-certbot-nginx
certbot --nginx -d $DOMAIN -d *.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN

# Configurar supervisor para queues
echo -e "${YELLOW}🔄 Configurando queues...${NC}"
apt install -y supervisor

cat > /etc/supervisor/conf.d/$APP_NAME.conf << EOF
[program:$APP_NAME-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/$APP_NAME/artisan queue:work --sleep=3 --tries=3 --max-jobs=1000
directory=/var/www/$APP_NAME
stopwaitsecs=10
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/$APP_NAME/storage/logs/queue.log
EOF

supervisorctl reread
supervisorctl update
supervisorctl start $APP_NAME-queue:*

# Configurar cron
echo -e "${YELLOW}⏰ Configurando cron jobs...${NC}"
(crontab -l ; echo "* * * * * cd /var/www/$APP_NAME && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Configurar logrotate
echo -e "${YELLOW}📝 Configurando logrotate...${NC}"
cat > /etc/logrotate.d/$APP_NAME << EOF
/var/www/$APP_NAME/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        supervisorctl restart $APP_NAME-queue:*
    endscript
}
EOF

# Configurar firewall
echo -e "${YELLOW}🔥 Configurando firewall...${NC}"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Permisos finales
echo -e "${YELLOW}🔐 Ajustando permisos...${NC}"
chown -R www-data:www-data /var/www/$APP_NAME
chmod -R 755 /var/www/$APP_NAME
chmod -R 775 /var/www/$APP_NAME/storage
chmod -R 775 /var/www/$APP_NAME/bootstrap/cache

echo -e "${GREEN}✅ Deployment completado exitosamente!${NC}"
echo -e "${GREEN}🌐 URL: https://$DOMAIN${NC}"
echo -e "${GREEN}👤 Admin: admin@tu-dominio.com${NC}"
echo -e "${GREEN}🔑 Password: [revisar logs de instalación]${NC}"
```

### Ejecutar Deployment

```bash
# Hacer ejecutable
chmod +x deploy.sh

# Ejecutar como root
sudo ./deploy.sh
```

## 🔄 Estrategias de Deployment

### Blue-Green Deployment

```
┌─────────────────┐    ┌─────────────────┐
│   BLUE ENV      │    │   GREEN ENV     │
│   (v1.0.0)      │    │   (v1.1.0)      │
│   ├─────────────┤    ├─────────────┤   │
│   │ Web Server  │    │ Web Server  │   │
│   │ Database    │    │ Database    │   │
│   │ Cache       │    │ Cache       │   │
│   └─────────────┘    └─────────────┘   │
└─────────────────┘    └─────────────────┘
         │                       │
         └─────── LOAD BALANCER ─┘
                     │
                ┌────▼────┐
                │  USERS  │
                └─────────┘
```

**Implementación:**
```bash
# Crear nueva instancia
aws ec2 run-instances --image-id ami-12345 --count 1 --instance-type t3.medium

# Deploy en nueva instancia
ansible-playbook -i inventory deploy.yml --extra-vars "env=green"

# Ejecutar tests
curl -f https://green.tu-dominio.com/health

# Cambiar load balancer
aws elbv2 modify-listener \
  --listener-arn $LISTENER_ARN \
  --default-actions Type=forward,TargetGroupArn=$GREEN_TG_ARN

# Verificar
curl -f https://tu-dominio.com/health

# Destruir blue environment
aws ec2 terminate-instances --instance-ids $BLUE_INSTANCE_ID
```

### Rolling Deployment

```bash
# Para múltiples instancias
ansible-playbook -i inventory rolling-deploy.yml

# rolling-deploy.yml
---
- hosts: webservers
  serial: 1  # Una instancia a la vez
  tasks:
    - name: Disable in load balancer
      # ...

    - name: Deploy new version
      # ...

    - name: Run health checks
      # ...

    - name: Enable in load balancer
      # ...
```

## 📊 Monitoreo y Alertas

### Métricas a Monitorear

```bash
# CPU y Memoria
top -b -n1 | head -5

# Disco
df -h

# Conexiones de red
netstat -tlnp | grep :80

# Procesos PHP
ps aux | grep php-fpm

# Logs de aplicación
tail -f /var/www/kartenant/storage/logs/laravel.log
```

### Configurar Monitoreo

**Instalar Prometheus + Grafana:**
```bash
# Prometheus
wget https://github.com/prometheus/prometheus/releases/download/v2.40.0/prometheus-2.40.0.linux-amd64.tar.gz
tar xvf prometheus-2.40.0.linux-amd64.tar.gz
cd prometheus-2.40.0.linux-amd64/
./prometheus --config.file=prometheus.yml

# Grafana
wget https://dl.grafana.com/oss/release/grafana_9.3.0_amd64.deb
dpkg -i grafana_9.3.0_amd64.deb
systemctl enable grafana-server
systemctl start grafana-server
```

**Dashboard Laravel:**
```php
// routes/web.php
Route::get('/metrics', function () {
    return response()->json([
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'database_connections' => DB::getConnections(),
        'cache_status' => Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore,
        'queue_status' => Queue::size(),
        'active_tenants' => \App\Models\Tenant::count(),
        'total_users' => \App\Models\User::count(),
    ]);
});
```

### Alertas

**Configurar alertas por email:**
```bash
# Instalar mailutils
apt install -y mailutils

# Script de monitoreo
cat > /usr/local/bin/monitor.sh << 'EOF'
#!/bin/bash

# Verificar servicios
if ! systemctl is-active --quiet nginx; then
    echo "Nginx is down" | mail -s "ALERT: Nginx Down" admin@tu-dominio.com
fi

if ! systemctl is-active --quiet php8.2-fpm; then
    echo "PHP-FPM is down" | mail -s "ALERT: PHP-FPM Down" admin@tu-dominio.com
fi

if ! systemctl is-active --quiet postgresql; then
    echo "PostgreSQL is down" | mail -s "ALERT: PostgreSQL Down" admin@tu-dominio.com
fi

# Verificar espacio en disco
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    echo "Disk usage is ${DISK_USAGE}%" | mail -s "ALERT: High Disk Usage" admin@tu-dominio.com
fi

# Verificar memoria
MEM_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
if [ $MEM_USAGE -gt 90 ]; then
    echo "Memory usage is ${MEM_USAGE}%" | mail -s "ALERT: High Memory Usage" admin@tu-dominio.com
fi
EOF

chmod +x /usr/local/bin/monitor.sh

# Agregar a cron (cada 5 minutos)
(crontab -l ; echo "*/5 * * * * /usr/local/bin/monitor.sh") | crontab -
```

## 🔄 Actualizaciones y Rollbacks

### Zero-Downtime Updates

```bash
# Script de update
cat > update.sh << 'EOF'
#!/bin/bash

echo "🚀 Iniciando actualización..."

# Crear backup
php artisan backup:run

# Poner en modo mantenimiento
php artisan down --message="Actualizando sistema..."

# Actualizar código
git pull origin main

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Ejecutar migraciones
php artisan migrate --force

# Limpiar cachés
php artisan optimize:clear
php artisan optimize

# Salir de mantenimiento
php artisan up

echo "✅ Actualización completada!"
EOF

chmod +x update.sh
```

### Rollback Strategy

```bash
# Script de rollback
cat > rollback.sh << 'EOF'
#!/bin/bash

echo "🔄 Iniciando rollback..."

# Poner en modo mantenimiento
php artisan down --message="Revirtiendo cambios..."

# Rollback de migraciones
php artisan migrate:rollback --step=1

# Rollback de código
git reset --hard HEAD~1
git push origin HEAD --force

# Reinstalar dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Limpiar cachés
php artisan optimize:clear
php artisan optimize

# Salir de mantenimiento
php artisan up

echo "✅ Rollback completado!"
EOF

chmod +x rollback.sh
```

## 🛡️ Seguridad en Producción

### Configuración SSL

```nginx
# /etc/nginx/sites-available/kartenant
server {
    listen 443 ssl http2;
    server_name tu-dominio.com *tu-dominio.com;

    ssl_certificate /etc/letsencrypt/live/tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio.com/privkey.pem;

    # SSL Security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;

    # CSP
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;

    # X-Frame-Options
    add_header X-Frame-Options "SAMEORIGIN" always;

    # X-Content-Type-Options
    add_header X-Content-Type-Options "nosniff" always;

    # Referrer Policy
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }
}
```

### Configuración PHP

```ini
# /etc/php/8.2/fpm/php.ini
max_execution_time = 30
memory_limit = 256M
post_max_size = 50M
upload_max_filesize = 50M
max_file_uploads = 20

# Security
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php8.2-fpm.log

# Sessions
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Strict
```

### Configuración PostgreSQL

```conf
# /etc/postgresql/15/main/pg_hba.conf
# IPv4 local connections:
host    all             all             127.0.0.1/32            md5
host    kartenant kartenant_user    127.0.0.1/32            md5

# /etc/postgresql/15/main/postgresql.conf
listen_addresses = 'localhost'
max_connections = 100
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB
```

## 📈 Escalabilidad

### Horizontal Scaling

**Load Balancer (Nginx):**
```nginx
upstream backend {
    ip_hash;
    server 10.0.1.10:80;
    server 10.0.1.11:80;
    server 10.0.1.12:80;
}

server {
    listen 80;
    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**Database Read Replicas:**
```php
// config/database.php
'connections' => [
    'pgsql' => [
        'read' => [
            'host' => ['read-replica-1', 'read-replica-2'],
        ],
        'write' => [
            'host' => 'master-db',
        ],
    ],
];
```

### CDN para Assets

```bash
# Configurar CloudFront o similar
# Servir assets estáticos desde CDN
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
```

## 🔍 Troubleshooting

### Problemas Comunes

**Error 502 Bad Gateway:**
```bash
# Verificar PHP-FPM
systemctl status php8.2-fpm

# Verificar Nginx config
nginx -t

# Verificar logs
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.2-fpm.log
```

**Error de Base de Datos:**
```bash
# Verificar conexión
php artisan tinker
>>> DB::connection()->getPdo()

# Verificar migraciones
php artisan migrate:status
```

**Altas Latencias:**
```bash
# Verificar queries lentas
php artisan tinker
>>> DB::enableQueryLog()
// Ejecutar queries
>>> dd(DB::getQueryLog())

# Verificar cache
php artisan cache:clear
php artisan config:clear
```

**Alto Uso de CPU/Memoria:**
```bash
# Verificar procesos
top -c

# Verificar queues
php artisan queue:status

# Verificar logs
tail -f storage/logs/laravel.log | grep ERROR
```

## 📞 Contacto y Soporte

¿Problemas con el deployment?

- **📧 Email:** deployment@kartenant.com
- **💬 Chat:** [Discord Deployment Channel](https://discord.gg/kartenant)
- **📚 Docs:** [Deployment Troubleshooting](docs/deployment/troubleshooting.md)
- **🐛 Issues:** [GitHub Deployment Issues](https://github.com/kartenant/deployment-issues)

---

**¡Tu deployment está listo!** 🎉

Visita `https://tu-dominio.com` para comenzar a usar Kartenant en producción.