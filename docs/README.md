# 📚 Documentación de Kartenant ERP

Bienvenido a la documentación completa de **Kartenant ERP** — una plataforma SaaS multi-tenant de código abierto para gestión empresarial de PYMEs.

> Los documentos marcados con ✅ están disponibles. Los marcados con 🚧 están en el roadmap de documentación.

---

## 🗂️ Estructura de Documentación

### 📖 Para Usuarios
Documentación orientada a usuarios finales, administradores y clientes del sistema.

| Estado | Documento | Descripción |
|---|---|---|
| 🚧 | Guía de Usuario | Uso diario del sistema |
| ✅ | [Instalación](user/installation.md) | Setup inicial paso a paso |
| ✅ | [Primeros Pasos](user/getting-started.md) | Configuración inicial y primera venta |
| ✅ | [Preguntas Frecuentes](user/faq.md) | Dudas comunes |

> 💡 Para instalación rápida, consulta el [README principal](../README.md#-getting-started).

---

### 🛠️ Para Desarrolladores
Documentación técnica para desarrolladores, DevOps y contribuidores.

| Estado | Documento | Descripción |
|---|---|---|
| 🚧 | Arquitectura | Diseño del sistema |
| 🚧 | Base de Datos | Esquemas y migraciones |
| 🚧 | API Reference | Endpoints disponibles |
| ✅ | [Combined Tabs Mode](technical/combined-tabs-solution.md) | Patrones UX para formularios complejos |
| ✅ | [Patrones de Implementación](technical/combined-tabs-patterns.md) | Guía de patrones reutilizables |
| 🚧 | Testing | Guías de testing con Pest 3 |
| 🚧 | Deployment | Puesta en producción |

> 📌 Ver también: [Resumen Técnico](TECHNICAL_SUMMARY.md) — [Setup SSL/Web](setup-web-ssl.md)

---

### ✨ Funcionalidades
Documentación detallada de cada módulo del sistema.

| Estado | Documento | Descripción |
|---|---|---|
| ✅ | [Sistema POS](features/pos-system.md) | Punto de venta completo |
| ✅ | [Mejoras Terminal POS](features/pos-terminal-improvements.md) | Mejoras recientes del terminal |
| ✅ | [POS Pantalla Completa](features/pos-fullscreen-setup.md) | Modo fullscreen del POS |
| ✅ | [POS Multi-Usuario](features/multi_user_pos.md) | Soporte de múltiples cajeros |
| ✅ | [Dashboard](features/dashboard_plan.md) | Métricas y KPIs en tiempo real |
| ✅ | [Renovación de Contraseñas](features/password_renewal.md) | Política de renovación forzada |
| ✅ | [Permisos y Funcionalidades](features/PERMISOS_Y_FUNCIONALIDADES.md) | Roles y control de acceso |
| ✅ | [Seguridad Multi-Tenant](features/ENHANCED-TENANT-SECURITY.md) | Aislamiento avanzado entre tenants |
| ✅ | [Zonas Horarias por Tenant](features/ZONA_HORARIA_TENANTS.md) | Configuración de timezone por tenant |
| ✅ | [Gestión de Inventario](features/inventory-management.md) | Control de stock e historial de movimientos |
| ✅ | [Gestión de Clientes (CRM)](features/customer-management.md) | Historial de compras y relaciones |
| ✅ | [Reportes y Analytics](features/reports-analytics.md) | Dashboards y métricas exportables |

---

### 🔒 Seguridad
Información sobre seguridad, protección de datos y cumplimiento.

| Estado | Documento | Descripción |
|---|---|---|
| ✅ | [Sistema de Seguridad](features/security-system.md) | Capas de seguridad, 2FA y auditoría |
| ✅ | [Autenticación y Autorización](security/authentication.md) | Roles, permisos y control de acceso |
| ✅ | [Verificación de Documentos PDF](PDF_VERIFICATION_SYSTEM.md) | Sistema de hash SHA-256 y QR |
| ✅ | [Verificación Interna](INTERNAL_VERIFICATION_SYSTEM.md) | Verificación de integridad interna |
| ✅ | [Protección SQL Injection](security/sql-injection-protection.md) | Análisis de seguridad |
| ✅ | [Patch de Seguridad 2026-03-31](security/SECURITY-PATCH-2026-03-31.md) | Último parche aplicado |

> Para reportar vulnerabilidades sigue el proceso en [SECURITY.md](../SECURITY.md). **No abras Issues públicos.**

---

### 🐛 Troubleshooting
Guías para resolución de problemas y soporte técnico.

| Estado | Documento | Descripción |
|---|---|---|
| ✅ | [Guía de Troubleshooting](TROUBLESHOOTING.md) | Problemas comunes y soluciones |
| ✅ | [Guía de Permisos](permission-fix-guide.md) | Corrección de problemas de permisos |
| ✅ | [Recuperación de Contraseñas](password-recovery-system.md) | Sistema de recuperación |
| 🚧 | Logs y Debugging | Análisis de logs con Pail |

---

### 📦 Historial y Estado del Proyecto

| Estado | Documento | Descripción |
|---|---|---|
| ✅ | [Estado del Proyecto](project-status.md) | Progreso y roadmap actual |
| ✅ | [Roadmap Notas de Crédito](CREDIT_NOTES_ROADMAP.md) | Roadmap de facturación |
| ✅ | [Estructura de Precios](PRICING_STRUCTURE.md) | Modelo de precios Open-Core |
| ✅ | [Devoluciones y Seguridad](SISTEMA_DEVOLUCIONES_Y_SEGURIDAD.md) | Sistema de devoluciones |
| ✅ | [Límites Suaves](soft-limits-interface.md) | Interfaz de límites por plan |
| ✅ | [Mejora Formato Horario](dashboard-time-format-improvement.md) | Mejora de formato de hora |
| ✅ | [Verificación Facturación](billing-system-verification.md) | Verificación del sistema de billing |
| ✅ | [Movimientos de Stock](stock-movement-verification-system.md) | Sistema de trazabilidad de stock |

---

## 🚀 Inicio Rápido

### Para Usuarios
1. Lee la [Guía de Instalación](user/installation.md)
2. Sigue los [Primeros Pasos](user/getting-started.md)
3. Consulta el [FAQ](user/faq.md) o [Troubleshooting](TROUBLESHOOTING.md) si tienes dudas

### Para Desarrolladores
1. Clona el repo: `git clone https://github.com/carlosindriago/kartenant-erp.git`
2. Revisa el [Resumen Técnico](TECHNICAL_SUMMARY.md)
3. Sigue el proceso de contribución en [CONTRIBUTING.md](../CONTRIBUTING.md)
4. Lee el [CLA](../CLA.md) antes de abrir un PR

---

## 📋 Estado del MVP

### ✅ Completo
- Arquitectura multi-tenant funcional (Database-per-Tenant)
- Sistema POS completo con escáner de códigos
- Gestión de inventario con trazabilidad completa
- Dashboard con métricas en tiempo real
- Sistema de seguridad avanzado (2FA, roles, permisos)
- Backups automáticos y monitoreo
- Verificación de documentos con SHA-256 y QR

### 🚧 En desarrollo / Roadmap
- Integraciones de pago (MercadoPago, Stripe)
- Facturación electrónica oficial (AFIP, SRI, SUNAT)
- Sistema multi-sucursal
- Gestión de empleados y comisiones
- API REST completa para integraciones

---

## 🎯 Filosofía de Diseño

El sistema está diseñado pensando en el usuario final típico: un pequeño comerciante que no es técnico pero necesita herramientas poderosas.

- **Lenguaje simple** — Sin jerga técnica en la interfaz
- **Flujo intuitivo** — Operaciones en pocos clics
- **Feedback inmediato** — Confirmaciones visuales y sonoras
- **Confianza** — Verificación de documentos con QR
- **Escalabilidad** — De 1 tienda a cadenas multinacionales

---

## 📧 Contacto y Soporte

- **🐛 Reportar Issues:** [GitHub Issues](https://github.com/carlosindriago/kartenant-erp/issues)
- **🔒 Vulnerabilidades de seguridad:** Ver [SECURITY.md](../SECURITY.md)
- **🤝 Contribuir:** Ver [CONTRIBUTING.md](../CONTRIBUTING.md)

---

**Última actualización:** Junio 2026  
**Versión del sistema:** 1.0.0-beta  
**Estado:** Documentación activa
