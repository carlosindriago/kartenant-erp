# Kartenant 🚀

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.x-blue.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue.svg)](https://postgresql.org)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)
[![Coverage](https://img.shields.io/badge/Coverage-Pending-yellow.svg)](https://kartenant.test)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

**Kartenant** is a modern, enterprise-grade Multi-Tenant SaaS platform designed for retail businesses, hardware stores, and SMBs. Built on a robust **Open-Core** model, it provides a comprehensive suite of tools for Point of Sale (POS), Inventory Management, and Billing.

## 🌟 The Open-Core Model

Kartenant is built with an **Open-Core** philosophy. We believe in providing immense value to the community while offering advanced capabilities for growing enterprises.

- 🌍 **Community Edition (Open Source)**: The core engine is free and open-source forever. It includes essential POS, inventory management, and single-tenant capabilities perfect for small, single-location businesses.
- 💼 **Premium Cloud (SaaS)**: Our hosted solution offers advanced multi-tenant isolation (Database-per-tenant), intelligent dashboards, automated backups, priority support, and multi-branch management.

## ✨ Key Features

*   🛍️ **Professional POS**: Fast, keyboard-first Point of Sale terminal with barcode scanner support.
*   📦 **Smart Inventory**: Real-time stock tracking, automated movement logging, and low-stock alerts.
*   👥 **CRM & Customer Management**: Track purchase history and manage client relationships.
*   📊 **Intelligent Dashboard**: Real-time KPIs, sales analytics, and business insights.
*   🏢 **Multi-Tenant Architecture**: Complete data isolation using a Database-per-Tenant strategy (Premium).
*   🔐 **Enterprise Security**: 2FA, granular roles & permissions, automated backups, and Document Verification via SHA-256 Hashes and QR codes.

## 🛠️ Tech Stack & Architecture

Kartenant is engineered for scalability, maintainability, and developer experience.

*   **Backend**: Laravel 11.x, PHP 8.2+
*   **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
*   **Admin & App Panels**: Filament v3
*   **Database**: PostgreSQL (Database-per-tenant architecture)
*   **PDF Generation**: DomPDF
*   **Infrastructure**: Docker (Sail), Nginx, Redis (Queue)

### Architecture Highlights
- **Modular Design**: Features are encapsulated in specific modules (`Inventory`, `POS`, `Clients`).
- **Database-per-Tenant**: Maximum data isolation and security. Separate databases for Landlord and Tenants.
- **Service Layer Pattern**: Fat models are avoided by using dedicated Service classes for business logic.

## 📚 Documentation

We have comprehensive documentation available in the [`docs/`](docs/) directory:

- 📖 **User Guides**: [User Documentation](docs/user/)
- 🏗️ **Technical & Architecture**: [Architecture & API](docs/technical/)
- 🚀 **Development & Roadmap**: [Development Guides](docs/development/)
- ✨ **Features**: [Feature Deep Dives](docs/features/)
- 🔒 **Security**: [Security Practices](docs/security/)
- 🐛 **Bugfixes & Logs**: [Bugfix Logs](docs/bugfixes/)

## 🚀 Getting Started (Community Edition)

### Prerequisites
- Docker & Docker Compose
- PHP 8.2+
- Composer
- Node.js & NPM

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/kartenant.git
   cd kartenant
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   # Update your .env with database credentials
   ```

3. **Install Dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

4. **Start Services (using Laravel Sail)**
   ```bash
   ./vendor/bin/sail up -d
   ```

5. **Run Migrations & Seeders**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --database=landlord --path=database/migrations/landlord
   php seed-landlord.php
   ```

6. **Create Super Admin**
   ```bash
   ./vendor/bin/sail artisan kartenant:make-superadmin
   ```

Access the admin panel at `http://localhost/admin/login`.

## 🤝 Contributing

We welcome contributions from the community! Whether it's a bug fix, new feature, or documentation improvement, please feel free to open an issue or submit a pull request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes following conventional commits (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

The Community Edition of Kartenant is open-sourced software licensed under the [MIT license](LICENSE).

---
*Built with ❤️ for SMBs and hardware stores in LATAM and beyond.*