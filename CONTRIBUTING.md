# Contribuyendo a Kartenant

¡Gracias por considerar contribuir a Kartenant! Al participar en este proyecto de código abierto, nos ayudas a construir un ecosistema mejor para pequeñas y medianas empresas.

## Código de Conducta
Al participar en este proyecto, aceptas cumplir con nuestro [Código de Conducta](CODE_OF_CONDUCT.md).

## Cómo contribuir

### Reportar Bugs
Utiliza la plantilla de Issue de GitHub para reportar bugs, proporcionando toda la información posible para que podamos reproducirlo.

### Solicitar Nuevas Características
Nos encantan las nuevas ideas. Abre un Issue usando la plantilla de Feature Request y discute la idea antes de empezar a programarla.

### Proceso de Desarrollo (Pull Requests)
1. Haz un fork del repositorio.
2. Crea una rama para tu característica: `git checkout -b feature/mi-nueva-caracteristica` o corrección `git checkout -b fix/solucion-del-problema`.
3. Sigue el estándar PSR-12 para PHP.
4. Escribe pruebas (Pest PHP) para tu código.
5. Haz commit de tus cambios utilizando _Conventional Commits_: `git commit -m 'feat: añade nueva característica'`.
6. Haz push a tu rama: `git push origin feature/mi-nueva-caracteristica`.
7. Abre un Pull Request apuntando **SIEMPRE a la rama `develop`** (nunca a `main`), rellenando la plantilla proporcionada.

### Entorno de Desarrollo Local
Para comenzar a desarrollar rápidamente, recomendamos usar Laravel Sail (Docker):

```bash
git clone https://github.com/YOUR_USERNAME/kartenant.git
cd kartenant
cp .env.example .env
composer install
npm install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run dev
```

Recuerda que cualquier nueva funcionalidad debe mantener la arquitectura multi-tenant (Database-per-tenant).

¡Gracias por tu apoyo!