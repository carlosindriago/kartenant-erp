# Kartenant ERP 🚀

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.x-blue.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-blue.svg)](https://postgresql.org)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)
[![Tests](https://img.shields.io/badge/Tests-Pest%203-brightgreen.svg)](https://pestphp.com)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](LICENSE)

**Kartenant ERP** is a modern, enterprise-grade **Multi-Tenant SaaS platform** designed for retail businesses, hardware stores, and SMBs across LATAM and beyond. Built on an **Open-Core** model using Laravel 12, Filament v3, and a Database-per-Tenant architecture.

> ⚠️ **License Notice**: This project is licensed under the **GNU Affero General Public License v3 (AGPL-3.0)**. See the [LICENSE](LICENSE) file for full terms. By contributing, you agree to the [Contributor License Agreement (CLA)](CLA.md).

---

## 🌟 Open-Core Model

Kartenant operates under an **Open-Core** philosophy — the core engine is open and auditable, while premium infrastructure features are offered as a hosted SaaS.

| Edition | Description |
|---|---|
| 🌍 **Community (Open Source)** | Core POS, inventory, and single-tenant mode. Free forever under AGPL-3.0. |
| 💼 **Premium Cloud (SaaS)** | Full multi-tenant isolation (Database-per-Tenant), automated backups, priority support, multi-branch management, and advanced dashboards. |

---

## ✨ Key Features

- 🛍️ **Professional POS** — Fast, keyboard-first terminal with barcode scanner support
- 📦 **Smart Inventory** — Real-time stock tracking, automated movement logging, and low-stock alerts
- 👥 **CRM & Customer Management** — Track purchase history and manage client relationships
- 📊 **Intelligent Dashboard** — Real-time KPIs, sales analytics, and business insights
- 🏢 **Multi-Tenant Architecture** — Complete data isolation using Database-per-Tenant (Premium/SaaS mode)
- 🔐 **Enterprise Security** — 2FA, granular roles & permissions, automated backups, SHA-256 document verification with QR codes

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12.x, PHP 8.2+ |
| Frontend | Livewire 3, Alpine.js, Tailwind CSS |
| Admin & App Panels | Filament v3 |
| Database | PostgreSQL 15+ |
| PDF Generation | DomPDF (barryvdh/laravel-dompdf) |
| QR Codes | SimpleSoftwareIO/simple-qrcode |
| Excel Export | Maatwebsite/Excel + pxlrbt/filament-excel |
| Multi-Tenancy | spatie/laravel-multitenancy v4 |
| Permissions | spatie/laravel-permission |
| Activity Logs | spatie/laravel-activitylog |
| Infrastructure | Docker (Laravel Sail), Nginx, Redis |
| Testing | Pest 3, Laravel Dusk |
| Static Analysis | PHPStan / Larastan Level 8 |

### Architecture Highlights

- **Modular Design** — Features are encapsulated in dedicated modules (`Inventory`, `POS`, `Clients`)
- **Database-per-Tenant** — Maximum data isolation; separate databases for Landlord and each Tenant
- **Dual Mode** — `APP_MODE=standalone` (single DB) for self-hosted SMBs; `APP_MODE=saas` for full multi-tenant cloud deployment
- **Service Layer Pattern** — Business logic lives in dedicated Service classes, keeping controllers and models lean

---

## 📚 Documentation

Full documentation is available in the [`docs/`](docs/) directory:

| Category | Link |
|---|---|
| 📖 User Guides | [docs/user/](docs/user/) |
| 🏗️ Architecture & API | [docs/technical/](docs/technical/) |
| 🚀 Development & Roadmap | [docs/development/](docs/development/) |
| ✨ Feature Deep Dives | [docs/features/](docs/features/) |
| 🔒 Security Practices | [docs/security/](docs/security/) |
| 🐛 Bugfix Logs | [docs/bugfixes/](docs/bugfixes/) |
| 🔧 Troubleshooting | [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) |

---

## 🚀 Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.2+
- Composer
- Node.js & NPM

### Installation (Community Edition)

**1. Clone the repository**

```bash
git clone https://github.com/carlosindriago/kartenant-erp.git
cd kartenant-erp
```

**2. Environment Setup**

```bash
cp .env.example .env
# Edit .env — set your APP_MODE, DB credentials, etc.
# APP_MODE=standalone  → single DB, self-hosted
# APP_MODE=saas        → Database-per-Tenant, multi-tenant cloud
```

**3. Install Dependencies**

```bash
composer install
npm install && npm run build
```

**4. Start Services (Laravel Sail)**

```bash
./vendor/bin/sail up -d
```

**5. Run Migrations & Seeders**

```bash
# Generate application key
./vendor/bin/sail artisan key:generate

# Run landlord migrations
./vendor/bin/sail artisan migrate --database=landlord --path=database/migrations/landlord

# Seed initial landlord data
./vendor/bin/sail php scripts/seed-landlord.php
```

**6. Create Super Admin**

```bash
./vendor/bin/sail artisan kartenant:make-superadmin
```

Access the admin panel at: **http://localhost/admin/login**

> 📖 For detailed installation guides (standalone vs SaaS mode, production deployment), see [docs/user/installation.md](docs/user/installation.md).

---

## 🤝 Contributing

Contributions are welcome! Bug fixes, new features, and documentation improvements all matter.

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit following Conventional Commits: `git commit -m 'feat: add amazing feature'`
4. Push your branch: `git push origin feature/amazing-feature`
5. Open a Pull Request against `develop`

> 📋 By submitting a Pull Request you agree to the [Contributor License Agreement (CLA)](CLA.md). Please also read [CONTRIBUTING.md](CONTRIBUTING.md) and our [Code of Conduct](CODE_OF_CONDUCT.md).

For security vulnerabilities, please follow the [Security Policy](SECURITY.md) — **do not open a public issue**.

---

## 📄 License

Kartenant ERP is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

This means:
- ✅ You can use, study, modify, and distribute the software freely
- ✅ You can run it for your business or clients
- ⚠️ If you modify it **and deploy it as a network service**, you must release your modifications under AGPL-3.0
- ❌ You **cannot** sublicense it under proprietary terms without a commercial agreement

See the full [LICENSE](LICENSE) file for details. For commercial licensing inquiries, please contact the maintainer.

---

*Built with ❤️ for SMBs and hardware stores in LATAM and beyond.*
