// Agregamos el header X-Requested-With a todas las peticiones fetch
// para mantener la compatibilidad con el sistema de protección CSRF y AJAX de Laravel
const originalFetch = window.fetch;
window.fetch = async (resource, options = {}) => {
    options.headers = {
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };
    return originalFetch(resource, options);
};
