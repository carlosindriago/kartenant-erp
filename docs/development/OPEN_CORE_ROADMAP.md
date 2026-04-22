# Roadmap: Transición a Modelo Open-Core (Kartenant)

Este documento detalla el plan de ejecución paso a paso ("El Pergamino Definitivo") para transformar Kartenant de un SaaS puramente Multi-Tenant a un modelo **Open-Core** (Versión Comunidad gratuita + Versión SaaS Premium).

## 🎯 Estado General del Proyecto
**Fase Actual:** Fase 3 (En progreso - Migración Quirúrgica)
**Rama de Trabajo:** `feature/open-core-foundation`

---

## ✅ Fase 1: La Armadura Legal (Protección del Código)
**Estado:** COMPLETADO

Antes de abrir el código fuente, se aseguró la propiedad intelectual y se establecieron los límites legales para prevenir la explotación comercial no autorizada del núcleo gratuito.

- [x] **Implementar Licencia AGPLv3:** Se creó el archivo `LICENSE` en la raíz del proyecto con el texto completo de la licencia GNU AGPLv3. Esto obliga a cualquier entidad que ofrezca el software como servicio a publicar sus modificaciones.
- [x] **Aviso de Copyright en Archivos Clave:** Se desarrolló y ejecutó un script automatizado que inyectó el encabezado de Copyright y la referencia a la licencia AGPLv3 en todos los archivos PHP clave de la aplicación (`app/`). (Aprox. 300 archivos modificados).
- [ ] **Registro de Marca:** (Pendiente por el cliente) Iniciar trámite en el país de origen para registrar el nombre "Kartenant" y su logo en la clase de software.

## ✅ Fase 2: El Ilusionismo (El Modo Standalone)
**Estado:** COMPLETADO

Se adaptó la arquitectura Multi-Tenant para que pueda funcionar como una aplicación Single-Tenant (para una sola ferretería) sin requerir reescribir la lógica de negocio subyacente.

- [x] **El Switch Maestro:** Se introdujo la variable `APP_MODE` en el archivo `.env` (con valor por defecto `saas` o `standalone`) y se registró en `config/app.php`.
- [x] **Fusión de la Base de Datos:** Se modificó `config/database.php`. Cuando el sistema detecta el modo `standalone`, la conexión dinámica `tenant` apunta a la misma base de datos física que el `landlord`, eliminando la necesidad de múltiples bases de datos para usuarios de la versión comunitaria.
- [x] **Bypass del Middleware Multi-Tenant:** Se inyectó lógica en `app/Http/Middleware/MakeSpatieTenantCurrent.php`. En modo `standalone`, ignora la resolución por subdominio, asigna automáticamente el "Tenant ID 1" y purga la conexión para asegurar que las consultas funcionen de forma transparente.
- [x] **Comando Instalador Unificado:** Se creó el comando Artisan `php artisan kartenant:install-standalone`. Este comando automatiza la ejecución de todas las migraciones (landlord y tenant) en una sola base, crea el tenant por defecto y el primer usuario administrador.
- [x] **Limpieza de la Interfaz (UI):** Se modificaron recursos de Filament (`AppPanelProvider.php`, `SubscriptionStatus.php`, `UpgradePlan.php`, `SubscriptionStatusWidget.php`) para ocultar menús, páginas y widgets exclusivos del modelo de suscripción cuando el sistema opera en modo `standalone`.

## 🔄 Fase 3: La Bóveda Secreta (Paquetes Privados)
**Estado:** EN PROGRESO

El objetivo es separar el código exclusivo del SaaS (La "Magia Premium") del repositorio principal para protegerlo, manteniéndolo como una dependencia privada.

- [x] **Crear el Laboratorio Local:** Se creó el directorio `packages/kartenant/premium-core/src/` para alojar las funcionalidades de pago.
- [x] **Configuración del Paquete:** Se generó el `composer.json` del paquete privado y el `PremiumServiceProvider.php` base.
- [x] **Protección del Repositorio:** Se actualizó el archivo `.gitignore` en la raíz del proyecto para ignorar completamente la carpeta `packages/`, asegurando que el código premium nunca se suba al repositorio open-source.
- [x] **Vinculación Local:** Se modificó el `composer.json` principal del proyecto para agregar el repositorio local tipo `path`, permitiendo que el código SaaS cargue las funciones premium transparentemente.
- [ ] **Migración Quirúrgica (El 80/20):** (PRÓXIMO PASO) Mover los servicios premium desde `app/` hacia `packages/kartenant/premium-core/src/`. Servicios identificados para migrar:
  - `TenantBackupService.php` (Copias de seguridad automatizadas en la nube).
  - `ErrorMonitoringService.php` (Integración avanzada con Slack).
  - Módulos de Facturación Electrónica (AFIP/SUNAT) si existen.
  - Reportes Avanzados / BI.
- [ ] **Desacoplamiento con Eventos:** Refactorizar el código "Core" para que emita eventos (Ej. `SaleCompleted`) en lugar de llamar directamente a clases premium. El paquete premium escuchará estos eventos y actuará en consecuencia.

## ⏳ Fase 4: La Separación de los Reinos (Control de Versiones y Despliegue)
**Estado:** PARCIALMENTE CONFIGURADO

- [x] **Repositorio Independiente para el Premium Core:** Se inicializó un repositorio Git completamente nuevo y aislado dentro de `packages/kartenant/premium-core/`.
- [ ] **Repositorio Público (GitHub - Open Core):** Limpiar el historial (si es necesario) y hacer el push de la rama principal a un repositorio público en GitHub.
- [ ] **Repositorio Privado (Premium Core):** Hacer push del repositorio de la bóveda a un repositorio Git privado (ej. GitLab o GitHub Private).
- [ ] **Estrategia de Despliegue SaaS:** Configurar el pipeline de CI/CD (DigitalOcean/Forge/Vercel) del SaaS para que tenga acceso al repositorio privado mediante SSH Keys o Tokens, inyectando el código premium durante el proceso de build (Composer install).

---
*Documento generado durante la sesión de arquitectura para la transformación a modelo Open-Core.*