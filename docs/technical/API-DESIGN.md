# API Design - Kartenant v2.0
## API-First Architecture for Multi-Tenant SaaS

**Versión:** 2.0.0
**Última actualización:** 2025-10-31
**Estado:** En diseño

---

## 🎯 Objetivo

Crear una API REST completa que sirva como backend único para:
- ✅ Web App (Vue 3 SPA)
- ✅ Apps móviles (Flutter Android/iOS)
- ✅ Integraciones futuras (webhooks, third-party)

---

## 🏗️ Arquitectura General

```
┌──────────────────────────────────────────────────────┐
│                   API REST Laravel                    │
│                                                       │
│  Base URL: https://api.kartenant.com/v1        │
│  Auth: Laravel Sanctum (Token-based)                │
│  Multi-tenancy: Header X-Tenant-ID                  │
└──────────────────────────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        ▼               ▼               ▼
   ┌────────┐     ┌─────────┐    ┌─────────┐
   │Vue SPA │     │ Flutter │    │ Flutter │
   │  Web   │     │ Android │    │   iOS   │
   └────────┘     └─────────┘    └─────────┘
```

---

## 🔐 Autenticación (Laravel Sanctum)

### Endpoints de Auth

```http
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me
POST   /api/v1/auth/password/forgot
POST   /api/v1/auth/password/reset
```

### Login Flow

**Request:**
```json
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "tenant_domain": "tornillostore",
  "device_name": "Web Browser"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "token": "1|abcdefgh123456789...",
    "token_type": "Bearer",
    "expires_at": "2025-11-30T00:00:00Z",
    "user": {
      "id": 2,
      "name": "Juan Pérez",
      "email": "juan@tornillostore.com",
      "role": "admin",
      "permissions": ["products.create", "sales.view", ...]
    },
    "tenant": {
      "id": 7,
      "name": "Tornillo Store",
      "domain": "tornillostore",
      "plan": "premium"
    }
  }
}
```

### Headers en Requests Autenticados

```http
GET /api/v1/products
Authorization: Bearer 1|abcdefgh123456789...
X-Tenant-ID: 7
Accept: application/json
```

---

## 📦 Estructura de Respuestas

### Success Response

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "timestamp": "2025-10-31T12:00:00Z",
    "version": "2.0.0"
  }
}
```

### Success Response con Paginación

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 15,
    "per_page": 15,
    "last_page": 5,
    "total": 73,
    "path": "/api/v1/products",
    "links": {
      "first": "/api/v1/products?page=1",
      "last": "/api/v1/products?page=5",
      "prev": null,
      "next": "/api/v1/products?page=2"
    }
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los datos proporcionados no son válidos",
    "details": {
      "name": ["El campo nombre es obligatorio"],
      "price": ["El precio debe ser mayor a 0"]
    }
  },
  "meta": {
    "timestamp": "2025-10-31T12:00:00Z"
  }
}
```

### HTTP Status Codes

| Code | Significado | Uso |
|------|-------------|-----|
| 200 | OK | Request exitoso |
| 201 | Created | Recurso creado |
| 204 | No Content | Delete exitoso |
| 400 | Bad Request | Request inválido |
| 401 | Unauthorized | Token inválido/expirado |
| 403 | Forbidden | Sin permisos |
| 404 | Not Found | Recurso no existe |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Error del servidor |

---

## 🗂️ Recursos y Endpoints

### 1. Products (Productos)

```http
GET    /api/v1/products              # Listar productos
POST   /api/v1/products              # Crear producto
GET    /api/v1/products/{id}         # Ver producto
PUT    /api/v1/products/{id}         # Actualizar producto
DELETE /api/v1/products/{id}         # Eliminar producto
GET    /api/v1/products/search       # Buscar productos

# Relaciones
GET    /api/v1/products/{id}/movements       # Movimientos del producto
GET    /api/v1/products/{id}/sales-history   # Historial de ventas
```

**Query Parameters:**
- `page` - Número de página (default: 1)
- `per_page` - Items por página (default: 15, max: 100)
- `search` - Búsqueda por nombre/SKU
- `category_id` - Filtrar por categoría
- `min_stock` - Stock mínimo
- `max_stock` - Stock máximo
- `sort` - Campo de ordenamiento (name, price, stock, created_at)
- `order` - Dirección (asc, desc)
- `include` - Relaciones (category, supplier, tax)

**Example Request:**
```http
GET /api/v1/products?search=tornillo&category_id=5&sort=name&order=asc&include=category,tax&page=1
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "sku": "TORN-001",
      "name": "Tornillo Hexagonal 1/2\"",
      "description": "Tornillo hexagonal de acero...",
      "barcode": "7501234567890",
      "price": 2.50,
      "cost": 1.20,
      "stock": 150,
      "min_stock": 20,
      "max_stock": 500,
      "category": {
        "id": 5,
        "name": "Tornillería"
      },
      "tax": {
        "id": 1,
        "name": "IVA",
        "rate": 16.00
      },
      "image_url": "https://...",
      "created_at": "2025-10-15T10:30:00Z",
      "updated_at": "2025-10-30T14:20:00Z"
    }
  ],
  "meta": { ... pagination ... }
}
```

### 2. Categories (Categorías)

```http
GET    /api/v1/categories            # Listar categorías
POST   /api/v1/categories            # Crear categoría
GET    /api/v1/categories/{id}       # Ver categoría
PUT    /api/v1/categories/{id}       # Actualizar categoría
DELETE /api/v1/categories/{id}       # Eliminar categoría
```

### 3. Stock Movements (Movimientos de Inventario)

```http
GET    /api/v1/stock-movements       # Listar movimientos
POST   /api/v1/stock-movements       # Crear movimiento (entrada/salida)
GET    /api/v1/stock-movements/{id}  # Ver movimiento
GET    /api/v1/stock-movements/{id}/pdf  # Descargar PDF

# Endpoints específicos
POST   /api/v1/stock-movements/entry    # Registrar entrada
POST   /api/v1/stock-movements/exit     # Registrar salida
```

**Create Entry Request:**
```json
POST /api/v1/stock-movements/entry

{
  "product_id": 10,
  "quantity": 50,
  "reason": "Compra a Proveedor",
  "supplier_id": 3,
  "invoice_reference": "FACT-12345",
  "batch_number": "LOTE-2025-001",
  "expiry_date": "2026-12-31",
  "notes": "Compra de fin de mes"
}
```

### 4. Sales (Ventas)

```http
GET    /api/v1/sales                 # Listar ventas
POST   /api/v1/sales                 # Crear venta
GET    /api/v1/sales/{id}            # Ver venta
PUT    /api/v1/sales/{id}            # Actualizar venta
DELETE /api/v1/sales/{id}            # Cancelar venta

# Acciones
POST   /api/v1/sales/{id}/return     # Procesar devolución
GET    /api/v1/sales/{id}/receipt    # Obtener ticket
GET    /api/v1/sales/stats           # Estadísticas de ventas
```

**Create Sale Request:**
```json
POST /api/v1/sales

{
  "customer_id": 5,
  "items": [
    {
      "product_id": 10,
      "quantity": 3,
      "price": 2.50,
      "discount": 0,
      "tax_rate": 16
    }
  ],
  "payment_method": "cash",
  "subtotal": 7.50,
  "tax": 1.20,
  "discount": 0,
  "total": 8.70,
  "paid_amount": 10.00,
  "change": 1.30,
  "notes": "Cliente frecuente"
}
```

### 5. Customers (Clientes)

```http
GET    /api/v1/customers             # Listar clientes
POST   /api/v1/customers             # Crear cliente
GET    /api/v1/customers/{id}        # Ver cliente
PUT    /api/v1/customers/{id}        # Actualizar cliente
DELETE /api/v1/customers/{id}        # Eliminar cliente
GET    /api/v1/customers/{id}/sales  # Ventas del cliente
GET    /api/v1/customers/{id}/stats  # Estadísticas del cliente
```

### 6. Suppliers (Proveedores)

```http
GET    /api/v1/suppliers             # Listar proveedores
POST   /api/v1/suppliers             # Crear proveedor
GET    /api/v1/suppliers/{id}        # Ver proveedor
PUT    /api/v1/suppliers/{id}        # Actualizar proveedor
DELETE /api/v1/suppliers/{id}        # Eliminar proveedor
```

### 7. Cash Registers (Cajas)

```http
GET    /api/v1/cash-registers        # Listar cajas
POST   /api/v1/cash-registers/open   # Abrir caja
POST   /api/v1/cash-registers/close  # Cerrar caja
GET    /api/v1/cash-registers/current # Caja actual del usuario
GET    /api/v1/cash-registers/{id}/movements  # Movimientos de caja
```

### 8. Reports (Reportes)

```http
GET    /api/v1/reports/dashboard     # Dashboard stats
GET    /api/v1/reports/sales         # Reporte de ventas
GET    /api/v1/reports/inventory     # Reporte de inventario
GET    /api/v1/reports/abc-analysis  # Análisis ABC
GET    /api/v1/reports/profitability # Rentabilidad
```

### 9. Settings (Configuraciones)

```http
GET    /api/v1/settings              # Obtener configuraciones del tenant
PUT    /api/v1/settings              # Actualizar configuraciones
GET    /api/v1/settings/branding     # Configuración de branding
PUT    /api/v1/settings/branding     # Actualizar branding
```

---

## 🔍 Filtros Avanzados (Spatie Query Builder)

Usaremos `spatie/laravel-query-builder` para filtros avanzados:

```http
GET /api/v1/products?filter[name]=tornillo&filter[stock]=>=20&sort=-created_at&include=category
```

**Operadores soportados:**
- `=` - Igual
- `!=` - Diferente
- `>` - Mayor que
- `>=` - Mayor o igual
- `<` - Menor que
- `<=` - Menor o igual
- `like` - Búsqueda parcial

---

## 🛡️ Rate Limiting

```php
// Para usuarios autenticados
'api' => 120 requests / minuto

// Para endpoints públicos (login, forgot password)
'public' => 10 requests / minuto
```

---

## 📄 Versionado de API

**URL Pattern:**
```
/api/v1/*     # Versión 1 (actual)
/api/v2/*     # Versión 2 (futuro)
```

Cuando necesitemos cambios breaking, crearemos v2 manteniendo v1 funcional por 6-12 meses.

---

## 🧪 Testing

Cada endpoint debe tener:
- ✅ Test de autenticación
- ✅ Test de validación
- ✅ Test de permisos
- ✅ Test de respuesta exitosa
- ✅ Test de multi-tenancy (no ver datos de otros tenants)

---

## 📚 Documentación

- **Postman Collection** - Para testing manual
- **OpenAPI/Swagger** - Generada automáticamente con `scramble`
- **API Docs URL:** `https://api.kartenant.com/docs`

---

## 🚀 Roadmap de Implementación

### Fase 1: Foundation (Semana 1)
- [ ] Setup Laravel Sanctum
- [ ] Middleware de multi-tenancy
- [ ] Base API Resources
- [ ] Auth endpoints
- [ ] Rate limiting

### Fase 2: Core Resources (Semana 2-3)
- [ ] Products API
- [ ] Categories API
- [ ] Stock Movements API
- [ ] Suppliers API

### Fase 3: Sales & POS (Semana 4)
- [ ] Sales API
- [ ] Customers API
- [ ] Cash Registers API

### Fase 4: Reports & Settings (Semana 5)
- [ ] Reports API
- [ ] Settings API
- [ ] Dashboard stats API

### Fase 5: Testing & Docs (Semana 6)
- [ ] Tests completos
- [ ] OpenAPI docs
- [ ] Postman collection

---

## 📝 Notas Importantes

1. **Multi-Tenancy:** Todas las queries deben filtrar por tenant automáticamente usando scope global.
2. **Soft Deletes:** Usar soft deletes en todos los recursos principales.
3. **Auditoría:** Registrar quién creó/modificó cada recurso.
4. **Timestamps:** Siempre incluir created_at, updated_at.
5. **Validation:** Usar Form Requests para validación consistente.
6. **Permissions:** Verificar permisos usando Spatie Laravel Permission.
7. **Rate Limiting:** Implementar para evitar abuso.
8. **CORS:** Configurar correctamente para Vue/Flutter.

---

## 🔗 Referencias

- Laravel Sanctum: https://laravel.com/docs/11.x/sanctum
- Spatie Query Builder: https://spatie.be/docs/laravel-query-builder
- API Resources: https://laravel.com/docs/11.x/eloquent-resources
- Scramble (OpenAPI): https://scramble.dedoc.co/

---

**Próximos pasos:**
1. Implementar autenticación Sanctum
2. Crear API Resources base
3. Implementar primer endpoint (Products) como template
4. Replicar patrón en demás recursos
