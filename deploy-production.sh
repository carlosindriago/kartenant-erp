#!/bin/bash

# Kartenant - Production Deployment Script
# Automated setup for Laravel multi-tenant SaaS on Ubuntu/Debian servers
# Version: 1.0.0

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="kartenant"
APP_USER="www-data"
PHP_VERSION="8.3"
NODE_VERSION="20"
POSTGRES_VERSION="16"

# Functions
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

install_dependencies() {
    print_header "Installing System Dependencies"

    apt-get update
    apt-get install -y software-properties-common curl wget git unzip

    print_success "Base dependencies installed"
}

install_php() {
    print_header "Installing PHP ${PHP_VERSION}"

    # Add PHP repository
    add-apt-repository ppa:ondrej/php -y
    apt-get update

    # Install PHP and extensions
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-redis

    print_success "PHP ${PHP_VERSION} installed"
}

install_composer() {
    print_header "Installing Composer"

    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

    print_success "Composer installed"
}

install_nodejs() {
    print_header "Installing Node.js ${NODE_VERSION}"

    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    apt-get install -y nodejs

    print_success "Node.js ${NODE_VERSION} installed"
}

install_postgresql() {
    print_header "Installing PostgreSQL ${POSTGRES_VERSION}"

    # Add PostgreSQL repository
    sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
    wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
    apt-get update

    apt-get install -y postgresql-${POSTGRES_VERSION} postgresql-contrib-${POSTGRES_VERSION}

    print_success "PostgreSQL ${POSTGRES_VERSION} installed"
}

install_nginx() {
    print_header "Installing Nginx"

    apt-get install -y nginx

    print_success "Nginx installed"
}

install_redis() {
    print_header "Installing Redis"

    apt-get install -y redis-server
    systemctl enable redis-server
    systemctl start redis-server

    print_success "Redis installed"
}

install_supervisor() {
    print_header "Installing Supervisor (for queue workers)"

    apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor

    print_success "Supervisor installed"
}

configure_ssl() {
    print_header "Installing Certbot for SSL"

    apt-get install -y certbot python3-certbot-nginx

    print_success "Certbot installed"
    print_warning "Run 'certbot --nginx -d yourdomain.com' to generate SSL certificates"
}

show_summary() {
    print_header "Installation Complete!"

    echo -e "System packages installed:"
    echo -e "  ${GREEN}✓${NC} PHP ${PHP_VERSION}"
    echo -e "  ${GREEN}✓${NC} Composer"
    echo -e "  ${GREEN}✓${NC} Node.js ${NODE_VERSION}"
    echo -e "  ${GREEN}✓${NC} PostgreSQL ${POSTGRES_VERSION}"
    echo -e "  ${GREEN}✓${NC} Nginx"
    echo -e "  ${GREEN}✓${NC} Redis"
    echo -e "  ${GREEN}✓${NC} Supervisor"
    echo -e "  ${GREEN}✓${NC} Certbot (SSL)"

    echo -e "\n${BLUE}Next Steps:${NC}"
    echo -e "1. Create PostgreSQL database and user"
    echo -e "2. Clone your repository to /var/www/${APP_NAME}"
    echo -e "3. Configure .env file"
    echo -e "4. Run 'composer install --optimize-autoloader --no-dev'"
    echo -e "5. Run 'npm install && npm run build'"
    echo -e "6. Run migrations: 'php artisan migrate --database=landlord'"
    echo -e "7. Configure Nginx virtual host"
    echo -e "8. Generate SSL certificate with certbot"
    echo -e "9. Configure supervisor for queue workers"
    echo -e "10. Set proper permissions on storage and cache"

    echo -e "\n${YELLOW}For detailed instructions, see PRODUCTION-DEPLOYMENT.md${NC}\n"
}

# Main execution
main() {
    check_root

    print_header "Kartenant - Production Server Setup"
    echo "This script will install all required dependencies for Laravel multi-tenant SaaS"

    read -p "Continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi

    install_dependencies
    install_php
    install_composer
    install_nodejs
    install_postgresql
    install_nginx
    install_redis
    install_supervisor
    configure_ssl

    show_summary
}

main "$@"
