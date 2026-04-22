FROM ubuntu:24.04

# Variables de entorno
ARG WWWUSER=1000
ARG WWWGROUP=1000
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependencias básicas
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    software-properties-common \
    ca-certificates \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update

# Instalar PHP 8.3 y extensiones
RUN apt-get install -y \
    php8.3-cli \
    php8.3-fpm \
    php8.3-pgsql \
    php8.3-redis \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-intl

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Crear usuario sail
RUN groupadd --gid $WWWGROUP sail \
    && useradd -ms /bin/bash --uid $WWWUSER --gid $WWWGROUP sail

# Configurar directorio de trabajo
WORKDIR /var/www/html

USER sail
