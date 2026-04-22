# 🔌 API Reference - Kartenant

Documentación completa de la API REST de Kartenant para integraciones de terceros.

## 📋 Visión General

La API de Kartenant está diseñada siguiendo principios REST, con autenticación Bearer Token y responses JSON estructurados.

### 🌐 Base URL
```
https://api.kartenant.test/v1
```

### 🔐 Autenticación
```bash
Authorization: Bearer {tenant_api_token}
```

### 📊 Formato de Response
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "pagination": { ... }
  },
  "message": "Operación exitosa"
}
```

### ❌ Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los datos proporcionados no son válidos",
    "details": {
      "field": ["El campo es requerido"]
    }
  }
}
```

---

## 🔑 Autenticación y Autorización

### Obtener API Token

**Endpoint:** `POST /auth/login`

**Request:**
```json
{
  "email": "admin@tenant.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@tenant.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

### Refresh Token

**Endpoint:** `POST /auth/refresh`

**Headers:**
```
Authorization: Bearer {current_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "new_token_here",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

---

## 🛒 Ventas (Sales)

### Crear Venta

**Endpoint:** `POST /sales`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request:**
```json
{
  "customer_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ],
  "payment_method": "cash",
  "notes": "Venta realizada por API"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "invoice_number": "FAC-20251011-0123",
    "customer": {
      "id": 1,
      "name": "Juan Pérez"
    },
    "items": [
      {
        "product_id": 1,
        "product_name": "Martillo Stanley",
        "quantity": 2,
        "unit_price": 1000.00,
        "line_total": 2000.00
      }
    ],
    "subtotal": 2000.00,
    "tax_amount": 420.00,
    "total": 2420.00,
    "payment_method": "cash",
    "status": "completed",
    "created_at": "2025-10-11T14:30:00Z"
  }
}
```

### Listar Ventas

**Endpoint:** `GET /sales`

**Query Parameters:**
- `page` (int): Página (default: 1)
- `per_page` (int): Items por página (default: 15, max: 100)
- `date_from` (date): Fecha desde (YYYY-MM-DD)
- `date_to` (date): Fecha hasta (YYYY-MM-DD)
- `customer_id` (int): Filtrar por cliente
- `status` (string): Estado (pending, completed, cancelled)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "invoice_number": "FAC-20251011-0123",
      "customer": {
        "id": 1,
        "name": "Juan Pérez"
      },
      "total": 2420.00,
      "status": "completed",
      "created_at": "2025-10-11T14:30:00Z"
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 1,
      "last_page": 1,
      "from": 1,
      "to": 1
    }
  }
}
```

### Obtener Venta Específica

**Endpoint:** `GET /sales/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "invoice_number": "FAC-20251011-0123",
    "customer": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@email.com"
    },
    "user": {
      "id": 2,
      "name": "María García"
    },
    "items": [
      {
        "product_id": 1,
        "product_name": "Martillo Stanley",
        "sku": "HAM-001",
        "quantity": 2,
        "unit_price": 1000.00,
        "tax_rate": 21.00,
        "line_total": 2420.00
      }
    ],
    "subtotal": 2000.00,
    "tax_amount": 420.00,
    "total": 2420.00,
    "payment_method": "cash",
    "status": "completed",
    "notes": null,
    "created_at": "2025-10-11T14:30:00Z",
    "updated_at": "2025-10-11T14:30:00Z"
  }
}
```

### Cancelar Venta

**Endpoint:** `DELETE /sales/{id}`

**Nota:** Solo ventas de las últimas 5 minutos pueden cancelarse vía API.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "cancelled",
    "cancelled_at": "2025-10-11T14:35:00Z"
  },
  "message": "Venta cancelada exitosamente"
}
```

---

## 📦 Productos (Products)

### Listar Productos

**Endpoint:** `GET /products`

**Query Parameters:**
- `page`, `per_page`: Paginación
- `category_id` (int): Filtrar por categoría
- `search` (string): Buscar por nombre/SKU
- `in_stock` (boolean): Solo productos con stock
- `active` (boolean): Solo productos activos (default: true)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Martillo Stanley",
      "sku": "HAM-001",
      "barcode": "123456789",
      "price": 1000.00,
      "category": {
        "id": 1,
        "name": "Herramientas"
      },
      "tax": {
        "id": 1,
        "name": "IVA 21%",
        "rate": 21.00
      },
      "stock": 45,
      "min_stock": 5,
      "is_active": true,
      "created_at": "2025-10-01T10:00:00Z"
    }
  ],
  "meta": {
    "pagination": { ... }
  }
}
```

### Obtener Producto

**Endpoint:** `GET /products/{id}`

**Response:** Similar al listado pero con datos completos incluyendo descripción, peso, dimensiones, etc.

### Crear Producto

**Endpoint:** `POST /products`

**Request:**
```json
{
  "name": "Destornillador Philips",
  "sku": "DES-001",
  "price": 250.00,
  "category_id": 1,
  "tax_id": 1,
  "stock": 20,
  "min_stock": 3,
  "description": "Destornillador profesional punta Philips #2"
}
```

### Actualizar Producto

**Endpoint:** `PUT /products/{id}`

**Request:** Mismos campos que creación, opcionales.

### Eliminar Producto

**Endpoint:** `DELETE /products/{id}`

**Nota:** Solo productos sin ventas asociadas pueden eliminarse.

---

## 👥 Clientes (Customers)

### Listar Clientes

**Endpoint:** `GET /customers`

**Query Parameters:**
- `page`, `per_page`: Paginación
- `search` (string): Buscar por nombre/email
- `active` (boolean): Solo activos (default: true)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@email.com",
      "phone": "+5491112345678",
      "document_type": "DNI",
      "document_number": "12345678",
      "address": {
        "street": "Av. Corrientes",
        "number": "1234",
        "city": "Buenos Aires",
        "state": "Buenos Aires",
        "country": "AR",
        "postal_code": "1000"
      },
      "credit_limit": 5000.00,
      "current_balance": 0.00,
      "is_active": true,
      "created_at": "2025-10-01T10:00:00Z"
    }
  ]
}
```

### Crear Cliente

**Endpoint:** `POST /customers`

**Request:**
```json
{
  "name": "María González",
  "email": "maria@email.com",
  "phone": "+5491187654321",
  "document_type": "DNI",
  "document_number": "87654321",
  "address_street": "Calle Florida",
  "address_number": "567",
  "address_city": "Buenos Aires",
  "address_country": "AR"
}
```

### Actualizar Cliente

**Endpoint:** `PUT /customers/{id}`

### Eliminar Cliente

**Endpoint:** `DELETE /customers/{id}`

**Nota:** Solo clientes sin ventas pueden eliminarse.

---

## 📊 Reportes (Reports)

### Reporte de Ventas

**Endpoint:** `GET /reports/sales`

**Query Parameters:**
- `date_from` (required): Fecha desde (YYYY-MM-DD)
- `date_to` (required): Fecha hasta (YYYY-MM-DD)
- `group_by` (string): 'day', 'week', 'month' (default: 'day')
- `format` (string): 'json', 'csv', 'pdf' (default: 'json')

**Response (JSON):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_sales": 150,
      "total_revenue": 250000.00,
      "total_tax": 43750.00,
      "average_ticket": 1666.67
    },
    "periods": [
      {
        "date": "2025-10-11",
        "sales_count": 15,
        "revenue": 25000.00,
        "tax": 4375.00
      }
    ]
  }
}
```

### Reporte de Inventario

**Endpoint:** `GET /reports/inventory`

**Query Parameters:**
- `category_id` (int): Filtrar por categoría
- `low_stock_only` (boolean): Solo productos bajo stock mínimo

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_products": 150,
      "total_value": 500000.00,
      "low_stock_count": 5,
      "out_of_stock_count": 2
    },
    "products": [
      {
        "id": 1,
        "name": "Martillo Stanley",
        "stock": 3,
        "min_stock": 5,
        "value": 3000.00,
        "status": "low_stock"
      }
    ]
  }
}
```

### Exportar Reportes

**Endpoint:** `GET /reports/{type}/export`

**Formats disponibles:**
- CSV: Para análisis en Excel
- PDF: Para archivo
- JSON: Para integraciones

**Ejemplo:**
```
GET /reports/sales/export?date_from=2025-10-01&date_to=2025-10-31&format=csv
```

---

## 📈 Dashboard

### Métricas del Dashboard

**Endpoint:** `GET /dashboard/metrics`

**Response:**
```json
{
  "success": true,
  "data": {
    "today_sales": {
      "count": 15,
      "revenue": 25000.00,
      "change_percent": 12.5
    },
    "month_sales": {
      "count": 450,
      "revenue": 750000.00,
      "change_percent": 8.3
    },
    "low_stock_products": 5,
    "pending_returns": 2,
    "top_products": [
      {
        "product_id": 1,
        "name": "Martillo Stanley",
        "sold_quantity": 25,
        "revenue": 25000.00
      }
    ],
    "recent_sales": [
      {
        "id": 123,
        "invoice_number": "FAC-20251011-0123",
        "customer_name": "Juan Pérez",
        "total": 2420.00,
        "created_at": "2025-10-11T14:30:00Z"
      }
    ]
  }
}
```

---

## 🔄 Webhooks

### Configurar Webhooks

**Endpoint:** `POST /webhooks`

**Request:**
```json
{
  "url": "https://mi-app.com/webhook",
  "events": ["sale.completed", "product.low_stock"],
  "secret": "mi_secret_para_verificar"
}
```

### Eventos Disponibles

- `sale.completed` - Venta completada
- `sale.cancelled` - Venta cancelada
- `product.created` - Producto creado
- `product.updated` - Producto actualizado
- `product.low_stock` - Producto bajo stock mínimo
- `customer.created` - Cliente creado
- `return.processed` - Devolución procesada

### Payload de Webhook

```json
{
  "event": "sale.completed",
  "timestamp": "2025-10-11T14:30:00Z",
  "tenant_id": 1,
  "data": {
    "sale": {
      "id": 123,
      "invoice_number": "FAC-20251011-0123",
      "total": 2420.00,
      "customer_id": 1
    }
  },
  "signature": "sha256=abc123..."
}
```

### Verificación de Webhook

```php
$signature = hash_hmac('sha256', $payload, $secret);
if (hash_equals($signature, $receivedSignature)) {
    // Webhook válido
}
```

---

## ⚡ Rate Limiting

### Límites por Endpoint

| Endpoint | Límite | Ventana |
|----------|--------|---------|
| `/auth/*` | 10 | minuto |
| `/sales` | 100 | minuto |
| `/products` | 200 | minuto |
| `/customers` | 200 | minuto |
| `/reports/*` | 50 | minuto |
| `/dashboard/*` | 100 | minuto |

### Headers de Rate Limit

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1633977600
X-RateLimit-Retry-After: 60
```

---

## 📝 Códigos de Error

### Códigos de Error Comunes

| Código | HTTP Status | Descripción |
|--------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Datos inválidos |
| `UNAUTHORIZED` | 401 | Token inválido o expirado |
| `FORBIDDEN` | 403 | Permisos insuficientes |
| `NOT_FOUND` | 404 | Recurso no encontrado |
| `CONFLICT` | 409 | Conflicto (ej: SKU duplicado) |
| `RATE_LIMITED` | 429 | Límite de requests excedido |
| `INTERNAL_ERROR` | 500 | Error interno del servidor |

### Ejemplo Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los datos proporcionados no son válidos",
    "details": {
      "email": ["El formato del email es inválido"],
      "price": ["El precio debe ser mayor a 0"]
    }
  }
}
```

---

## 🧪 SDKs y Librerías

### PHP SDK

```bash
composer require kartenant/api-sdk
```

```php
use Kartenant\ApiClient;

$client = new ApiClient('your-api-token');

$sale = $client->sales()->create([
    'customer_id' => 1,
    'items' => [
        ['product_id' => 1, 'quantity' => 2]
    ],
    'payment_method' => 'cash'
]);
```

### JavaScript SDK

```bash
npm install @kartenant/api-sdk
```

```javascript
import { KartenantAPI } from '@kartenant/api-sdk';

const api = new KartenantAPI('your-api-token');

const sales = await api.sales.list({
    date_from: '2025-10-01',
    date_to: '2025-10-31'
});
```

---

## 📚 Ejemplos de Integración

### Sincronización de Productos

```php
// Obtener productos actualizados desde tu sistema
$products = $externalApi->getProducts();

// Sincronizar con Kartenant
foreach ($products as $product) {
    $api->products()->updateOrCreate([
        'sku' => $product['sku']
    ], [
        'name' => $product['name'],
        'price' => $product['price'],
        'stock' => $product['stock']
    ]);
}
```

### Webhook para Actualizar Inventario

```php
// En tu aplicación
Route::post('/webhook/kartenant', function (Request $request) {
    $payload = $request->all();

    // Verificar firma
    if (!$this->verifyWebhookSignature($payload)) {
        return response('Unauthorized', 401);
    }

    if ($payload['event'] === 'sale.completed') {
        // Actualizar inventario en tu sistema
        $this->updateInventory($payload['data']['sale']);
    }

    return response('OK');
});
```

---

## 🔒 Seguridad

### Mejores Prácticas

1. **Almacenar tokens de forma segura** - Nunca en código fuente
2. **Rotar tokens periódicamente** - Cambiar cada 30-90 días
3. **Usar HTTPS** - Siempre en producción
4. **Validar webhooks** - Verificar firma de seguridad
5. **Rate limiting** - Respetar límites de API
6. **Logging** - Registrar todas las llamadas a API

### Certificados SSL

La API requiere conexiones HTTPS en producción. Se aceptan certificados de:
- Let's Encrypt
- DigiCert
- GlobalSign
- Otros certificados válidos

---

## 📞 Soporte

¿Necesitas ayuda con la API?

- **📧 Email:** api@kartenant.com
- **💬 Discord:** [API Support Channel](https://discord.gg/kartenant)
- **📚 Documentación:** [docs.kartenant.com/api](https://docs.kartenant.com/api)
- **🐛 Issues:** [GitHub API Issues](https://github.com/kartenant/api-issues)

---

**Versión de API:** v1.0
**Última actualización:** Octubre 2025
**Estado:** Production Ready