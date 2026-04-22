#!/bin/bash
set -e

# Dar permisos correctos
chown -R sail:sail /var/www/html/storage
chown -R sail:sail /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Dar permisos a node_modules para Vite
if [ -d "/var/www/html/node_modules" ]; then
    chown -R sail:sail /var/www/html/node_modules
    chmod -R 755 /var/www/html/node_modules
fi

# Iniciar PHP-FPM en primer plano
exec /usr/sbin/php-fpm8.3 --nodaemonize --fpm-config /etc/php/8.3/fpm/pool.d/www.conf