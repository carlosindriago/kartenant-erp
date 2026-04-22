# Plan Estratégico: Startup SaaS para Ferreterías en Argentina
**Documento Confidencial de Estrategia de Producto y Negocio**

**Fecha:** 12 de Febrero de 2026
**Preparado por:** Equipo de Consultoría Técnica y de Negocios (Lead, Senior Dev, Arquitecto, Sales Expert)
**Proyecto Base:** Kartenant (Laravel/Filament)
**Nuevo Stack:** Next.js + Tailwind + Supabase (PostgreSQL)

---

## 1. Resumen Ejecutivo y Cambio de Metodología

### Del Modelo Tradicional al Modelo Startup (Lean)
Actualmente, *Kartenant* es un sistema robusto y monolítico basado en Laravel y Filament. Si bien es excelente para estabilidad, el enfoque para el nuevo proyecto será **"Speed to Market"**. Usaremos una arquitectura **Serverless y Composable** con Next.js y Supabase para reducir el *time-to-market* de meses a semanas, permitiendo iteraciones rápidas basadas en feedback real de ferreteros argentinos.

### El Pivot: "Ferretero Ágil" (Nombre Clave)
Dejamos de ser un "ERP genérico" para convertirnos en la herramienta definitiva de **gestión de mostrador y precios** para ferreterías, atacando el dolor más grande del mercado argentino: la inflación y la dispersión de precios.

---

## 2. Análisis Técnico: Migración de Arquitectura

| Componente | Kartenant (Actual) | Nuevo MVP (Startup Stack) | Justificación del Cambio |
|:--- |:--- |:--- |:--- |
| **Framework** | Laravel 11 (PHP) | **Next.js 14+ (App Router)** | Renderizado híbrido, velocidad de UI, ecosistema React masivo. |
| **Frontend** | Blade / Livewire / Filament | **React + Tailwind + Shadcn/UI** | UX de clase mundial, componentes reusables, optimizado para móvil. |
| **Base de Datos** | PostgreSQL (Docker/Local) | **Supabase (Managed Postgres)** | Escalamiento instantáneo, Auth integrado, APIs automáticas, Realtime. |
| **Auth** | Laravel Guards/Passport | **Supabase Auth** | Manejo de sesiones, Social Login y Magic Links "out-of-the-box". |
| **Infraestructura** | VPS / Docker / Sail | **Vercel + Supabase** | Cero mantenimiento de servidores (Serverless). Costo inicial $0. |
| **Multi-tenancy** | Database-per-tenant | **Row Level Security (RLS)** | Arquitectura más económica y simple para iniciar. Todos los tenants en una DB, segregados por `tenant_id` seguro a nivel de motor. |

---

## 3. Definición del MVP: "Ferretero Ágil"

Para el MVP (Producto Mínimo Viable), aplicaremos la regla del 80/20. No construiremos todo. Construiremos lo que **vende**.

### El Dolor del Cliente (Argentina)
1.  **Inflación:** Cambiar precios de 5,000 tornillos y herramientas manualmente es imposible. Pierden dinero por no actualizar a tiempo.
2.  **Mostrador Caótico:** Tardan mucho en cobrar, la gente espera, se van.
3.  **Stock Desconocido:** "¿Tenés codos de 1/2?" (El dueño va al fondo a fijarse).

### Funcionalidades Core del MVP

#### A. Módulo de Precios Inteligentes (Killer Feature)
*   **Actualización Masiva:** "Aumentar rubro 'Herramientas Manuales' un 15%".
*   **Importador de Listas:** Subir Excel de proveedores (ej: Hamilton, Bremen) y actualizar costos automáticamente.
*   **Historial de Precios:** Ver evolución para no perder margen.

#### B. POS (Punto de Venta) Ultra-Rápido
*   **Interfaz de Teclado:** Diseñada para no usar mouse (búsqueda rápida tipo Spotlight).
*   **Carrito Volátil:** Presupuestos rápidos que se convierten en venta o se guardan.
*   **Búsqueda Inteligente:** "Tornillo 3x30" encuentra variantes y marcas.

#### C. Inventario Simplificado
*   **Alta Rápida:** Escanear código de barras con el celular (PWA) para dar de alta.
*   **Alertas de Stock Bajo:** Semáforo simple (Verde/Amarillo/Rojo).

#### D. Gestión de Clientes (Cta. Cte.)
*   **Fiado Digital:** Registro simple de deuda por cliente (fundamental en ferreterías de barrio).
*   **Recordatorios WhatsApp:** Botón para enviar "Hola Juan, tu saldo es $X" por WP.

---

## 4. Viabilidad y Beneficios

### Beneficio para el Usuario
*   **Recupero de Dinero:** Al actualizar precios al instante, dejan de perder contra la inflación.
*   **Modernización:** Pasan del cuaderno o Excel a una app que pueden ver desde el celular en su casa.
*   **Cero Instalación:** Todo web, no dependen de técnicos que vayan a instalar el programa.

### Viabilidad de Mercado
*   **Nicho Desatendido:** Los sistemas actuales (Dragonfish, Tango, etc.) son caros, viejos y difíciles de usar para una ferretería pequeña/mediana.
*   **Mercado Masivo:** Miles de ferreterías en AMBA y el interior que necesitan digitalizarse.
*   **Adopción:** Si resolvemos el problema de la "Lista de Precios", la adopción está garantizada.

---

## 5. Roadmap del Proyecto (Fases)

### Fase 1: MVP - "Control de Precios y Caja" (Mes 1-2)
*   **Objetivo:** Validar que los usuarios carguen sus productos y actualicen precios.
*   **Entregables:** Login, Importador Excel, Listado Productos, Actualización Masiva %, POS Básico (Ticket interno), Cuentas Corrientes.
*   **KPI:** 10 Ferreterías usándolo activamente.

### Fase 2: "Cumplimiento y Conectividad" (Mes 3-4)
*   **Objetivo:** Profesionalizar la venta.
*   **Entregables:**
    *   **Factura Electrónica (AFIP):** Integración para emitir Factura A/B/C con un clic.
    *   **Etiquetas:** Generación de PDFs para imprimir códigos de barra para las góndolas.
    *   **Dashboard:** Métricas simples (Ventas del día, Ganancia estimada).

### Fase 3: "Expansión y Automatización" (Mes 5+)
*   **Objetivo:** Retención y Upselling.
*   **Entregables:**
    *   **Pedidos a Proveedor:** Generar orden de compra basada en stock bajo.
    *   **MercadoLibre:** Integración básica para sincronizar stock/precio.
    *   **Tienda Online Simple:** Catálogo público para que los vecinos vean si "hay stock".

---

## 6. Plan de Ventas y Costos

### Modelo de Negocio: SaaS (Suscripción Mensual)

*   **Plan "Barrio" (Free Tier / Freemium):**
    *   Hasta 500 productos.
    *   1 Usuario.
    *   Sin Factura Electrónica.
    *   *Objetivo:* Entrada masiva y viralidad.

*   **Plan "Mostrador" ($15.000 - $20.000 ARS/mes):**
    *   Productos ilimitados.
    *   2 Usuarios.
    *   Actualización masiva de precios.
    *   Soporte WhatsApp.

*   **Plan "Empresario" ($35.000+ ARS/mes):**
    *   Factura Electrónica (AFIP).
    *   Multi-usuario ilimitado.
    *   Múltiples sucursales.
    *   Reportes avanzados.

### Estrategia de Go-To-Market
1.  **Venta Directa (In-situ):** Visitar ferreterías en zonas comerciales (Av. Warnes, zonas industriales, barrios) con una tablet mostrando la demo.
2.  **Alianzas con Distribuidores:** Ofrecer el software a grandes distribuidores ferreteros para que se lo regalen/vendan a sus clientes minoristas (así el distribuidor se asegura que el minorista tenga precios actualizados).
3.  **Marketing de Contenidos:** "Cómo no perder plata con la inflación en tu ferretería".

### Estructura de Costos (Mensual - Estimado Start)
*   **Infraestructura (Vercel/Supabase):** $0 (Tier gratuito cubre el MVP sobrado).
*   **Dominio:** $10 USD/año.
*   **Desarrollo:** HH (Horas Hombre) propias iniciales.
*   **Marketing:** $100 USD (Ads localizados).
*   **Costo Real:** Tiempo. El stack elegido minimiza el costo financiero.

---

## 7. Proyección a Futuro

Una vez capturado el nicho de ferreterías, la plataforma permite escalar horizontalmente a otros rubros con problemáticas similares de *muchos items y precios volátiles*:
*   Repuesteras de Autos.
*   Casas de Electricidad.
*   Sanitarios.

La visión final es construir una **Red B2B**: Conectar el software del ferretero (nuestro cliente) directamente con el sistema del proveedor mayorista, automatizando la reposición de mercadería y cobrando una comisión por transacción (Fintech/Marketplace).