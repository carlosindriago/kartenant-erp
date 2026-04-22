# Parche de Seguridad: Axios Supply Chain Attack
**Fecha:** 2026-03-31

## Contexto
Se reportó un ataque crítico a la cadena de suministro en la librería `axios` (versiones 1.14.1 y 0.30.4 contenían un RAT troyano multiplataforma), además de vulnerabilidades previas de denegación de servicio (CVE-2026-25639 y CVE-2025-58754).

## Acción Tomada
Para garantizar la integridad y seguridad del proyecto, se tomó la decisión arquitectónica de **eliminar completamente la dependencia de axios** y reemplazarla por la **Fetch API nativa de Node.js / Browser**.

### Cambios Específicos
- **`package.json` / `package-lock.json`**: Se eliminó `axios` de las dependencias.
- **`resources/js/bootstrap.js`**: Se eliminó la inicialización de Axios. En su lugar, se implementó un interceptor nativo sobre `window.fetch` que inyecta automáticamente el header `X-Requested-With: XMLHttpRequest`. Esto mantiene total compatibilidad con los mecanismos de protección CSRF y las respuestas AJAX (JSON) del backend de Laravel sin añadir peso adicional al bundle del frontend.
