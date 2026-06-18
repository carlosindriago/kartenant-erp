# Contribuyendo a Kartenant ERP

¡Gracias por considerar contribuir a Kartenant! Al participar en este proyecto, nos ayudas a construir un ecosistema mejor para pequeñas y medianas empresas en LATAM y el mundo.

---

## 📋 Acuerdo de Licencia de Contribuidor (CLA)

Antes de contribuir, por favor lee el **[Contributor License Agreement (CLA)](CLA.md)**.

Al abrir un Pull Request en este repositorio, confirmas que has leído y aceptas los términos del CLA. No se requiere firma adicional — enviar un PR constituye aceptación. El CLA es necesario para preservar el modelo Open-Core del proyecto y está disponible en español e inglés.

---

## Código de Conducta

Al participar en este proyecto, aceptas cumplir con nuestro [Código de Conducta](CODE_OF_CONDUCT.md).

---

## Cómo contribuir

### Reportar Bugs

Utiliza la plantilla de Issue de GitHub para reportar bugs, proporcionando toda la información posible para que podamos reproducirlo: versión del sistema, pasos para reproducir, comportamiento esperado vs. actual.

### Solicitar Nuevas Características

Nos encantan las nuevas ideas. Abre un Issue usando la plantilla de Feature Request y discute la idea antes de empezar a programarla.

### Proceso de Desarrollo (Pull Requests)

1. Haz un fork del repositorio desde [https://github.com/carlosindriago/kartenant-erp](https://github.com/carlosindriago/kartenant-erp).
2. Crea una rama para tu característica: `git checkout -b feature/mi-nueva-caracteristica`, o para correcciones: `git checkout -b fix/solucion-del-problema`.
3. Sigue el estándar **PSR-12** para PHP.
4. Escribe pruebas (**Pest 3**) para tu código.
5. Haz commit usando _Conventional Commits_: `git commit -m 'feat: añade nueva característica'`.
6. Haz push a tu rama: `git push origin feature/mi-nueva-caracteristica`.
7. Abre un Pull Request apuntando **SIEMPRE a la rama `develop`** (nunca directamente a `main`), rellenando la plantilla proporcionada.

> ⚠️ Los PRs que apunten directamente a `main` serán rechazados.

---

## Entorno de Desarrollo Local

Para comenzar a desarrollar rápidamente, recomendamos usar **Laravel Sail** (Docker):

```bash
git clone https://github.com/carlosindriago/kartenant-erp.git
cd kartenant-erp
cp .env.example .env
composer install
npm install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --database=landlord --path=database/migrations/landlord
./vendor/bin/sail php scripts/seed-landlord.php
./vendor/bin/sail npm run dev
```

Recuerda que cualquier nueva funcionalidad debe mantener compatibilidad con la arquitectura multi-tenant (Database-per-Tenant) cuando `APP_MODE=saas`.

---

## Seguridad

Si descubres una vulnerabilidad de seguridad, **no abras un Issue público**. Sigue el proceso descrito en [SECURITY.md](SECURITY.md).

---

¡Gracias por tu apoyo! 🚀
