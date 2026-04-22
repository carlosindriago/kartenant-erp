# 🏗️ Arquitectura del Sistema - Kartenant

Esta documentación describe la arquitectura técnica completa de Kartenant, un sistema SaaS multi-tenant construido con Laravel y PostgreSQL.

## 📋 Visión General

Kartenant es una **plataforma SaaS multi-tenant** diseñada para gestión empresarial completa. La arquitectura sigue principios de **Domain-Driven Design (DDD)**, **Clean Architecture** y **microservicios lógicos**.

### 🎯 Principios Arquitectónicos

- **Multi-Tenant First**: Aislamiento completo de datos por tenant
- **API-First**: Diseño pensando en integraciones futuras
- **Event-Driven**: Comunicación desacoplada entre módulos
- **Testable**: Arquitectura que facilita testing automatizado
- **Scalable**: Diseño que permite crecimiento horizontal

---

## 🏛️ Arquitectura General

### Diagrama de Alto Nivel

```
┌─────────────────────────────────────────────────────────────┐
│                    KARTENANT DIGITAL                           │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   LANDLORD      │  │   TENANTS       │  │   SHARED    │ │
│  │   (Sistema)     │  │   (Negocios)    │  │   (Core)    │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────┤ │
│  │ • Users         │  │ • tenant_001    │  │ • Auth      │ │
│  │ • Tenants       │  │ • tenant_002    │  │ • Queue     │ │
│  │ • Subscriptions │  │ • tenant_003    │  │ • Cache     │ │
│  │ • System Config │  │   ...           │  │ • Storage   │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   WEB LAYER     │  │   API LAYER     │  │   JOBS      │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────┤ │
│  │ • Filament      │  │ • REST API      │  │ • Backup    │ │
│  │ • Livewire      │  │ • GraphQL       │  │ • Reports   │ │
│  │ • Blade         │  │ • Webhooks      │  │ • Email     │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Capas de Arquitectura

```
┌─────────────────────────────────────────┐
│           PRESENTATION LAYER            │
│  ┌───────────────────────────────────┐  │
│  │  Controllers  │  Livewire  │ API  │  │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│           APPLICATION LAYER             │
│  ┌───────────────────────────────────┐  │
│  │ Services │ Commands │ Events │ Jobs │  │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│            DOMAIN LAYER                 │
│  ┌───────────────────────────────────┐  │
│  │  Models │ Policies │ Rules │ Value │  │
│  │         │          │       │ Objects│  │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│          INFRASTRUCTURE LAYER           │
│  ┌───────────────────────────────────┐  │
│  │ Database │ Cache │ Queue │ Storage │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

---

## 🗄️ Arquitectura Multi-Tenant

### Database-per-Tenant

**Ventajas:**
- ✅ Aislamiento completo de datos
- ✅ Backup/restore por tenant
- ✅ Escalabilidad horizontal
- ✅ Compliance (GDPR, etc.)
- ✅ Performance optimizada

**Implementación:**
```php
// config/database.php
'connections' => [
    'landlord' => [
        'database' => env('DB_DATABASE'),
        // Configuración landlord
    ],
    'tenant' => [
        'database' => null, // Se setea dinámicamente
        // Configuración tenant
    ],
],
```

### Spatie Laravel Multitenancy

**Configuración principal:**
```php
// config/tenancy.php
'central_domains' => [
    'kartenant.test',
    'app.kartenant.test',
],

'tenant_domains' => [
    '{tenant}.kartenant.test',
],
```

**Middleware de tenant:**
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ... otros middlewares
        \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
        \Spatie\Multitenancy\Http\Middleware\BindTenantDatabase::class,
    ],
];
```

### Context Switching

```php
// Cambiar a contexto de tenant
$tenant = Tenant::find(1);
$tenant->execute(function () {
    // Aquí estamos en la BD del tenant
    $products = Product::all();
    $sales = Sale::where('created_at', '>', now()->subDay())->get();
});
```

---

## 📦 Estructura de Módulos

### Domain Modules

```
app/
├── Models/                 # Modelos base (User, Tenant, etc.)
├── Modules/                # Módulos de dominio
│   ├── POS/               # Punto de Venta
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Resources/
│   │   └── Policies/
│   ├── Inventory/         # Gestión de Inventario
│   ├── Reporting/         # Reportes y Analytics
│   └── UserManagement/    # Gestión de Usuarios
├── Services/              # Servicios de aplicación
├── Events/                # Eventos del sistema
├── Listeners/             # Listeners de eventos
└── Jobs/                  # Jobs en cola
```

### Module Structure

Cada módulo sigue esta estructura:

```
Modules/{ModuleName}/
├── Models/                # Modelos específicos del módulo
├── Services/              # Lógica de negocio
├── Resources/             # Filament resources
├── Pages/                 # Páginas Filament
├── Widgets/               # Widgets de dashboard
├── Policies/              # Políticas de autorización
├── Events/                # Eventos del módulo
├── Jobs/                  # Jobs específicos
├── Migrations/            # Migraciones del módulo
├── Seeders/               # Seeders del módulo
└── Tests/                 # Tests del módulo
```

---

## 🔄 Patrón Repository

### Implementación

```php
// app/Modules/POS/Repositories/SaleRepository.php
interface SaleRepositoryInterface
{
    public function create(array $data): Sale;
    public function findById(int $id): ?Sale;
    public function getByDateRange(Carbon $start, Carbon $end): Collection;
    public function getTotalSales(Carbon $start, Carbon $end): float;
}

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function create(array $data): Sale
    {
        return Sale::create($data);
    }

    public function findById(int $id): ?Sale
    {
        return Sale::with(['items.product', 'customer'])->find($id);
    }

    // ... otros métodos
}
```

### Service Layer

```php
// app/Modules/POS/Services/SaleService.php
class SaleService
{
    private SaleRepositoryInterface $saleRepository;
    private InventoryService $inventoryService;
    private CashRegisterService $cashRegisterService;

    public function __construct(
        SaleRepositoryInterface $saleRepository,
        InventoryService $inventoryService,
        CashRegisterService $cashRegisterService
    ) {
        $this->saleRepository = $saleRepository;
        $this->inventoryService = $inventoryService;
        $this->cashRegisterService = $cashRegisterService;
    }

    public function processSale(array $saleData): Sale
    {
        DB::transaction(function () use ($saleData) {
            // 1. Crear venta
            $sale = $this->saleRepository->create($saleData);

            // 2. Actualizar inventario
            $this->inventoryService->reduceStock($sale->items);

            // 3. Actualizar caja
            $this->cashRegisterService->addSale($sale);

            // 4. Disparar eventos
            event(new SaleProcessed($sale));

            return $sale;
        });
    }
}
```

---

## 🎯 Domain-Driven Design (DDD)

### Bounded Contexts

```
┌─────────────────────────────────────────────────────────────┐
│                    KARTENANT DIGITAL DDD                      │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   POS CONTEXT   │  │ INVENTORY CTX   │  │ REPORTING   │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────┤ │
│  │ • Sale          │  │ • Product       │  │ • Report    │ │
│  │ • Cart          │  │ • StockMovement │  │ • Chart     │ │
│  │ • Payment       │  │ • Supplier      │  │ • Export    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   USER MGMT     │  │   TENANCY       │  │   SECURITY  │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────┤ │
│  │ • User          │  │ • Tenant        │  │ • Permission│ │
│  │ • Role          │  │ • Subscription  │  │ • AuditLog  │ │
│  │ • Permission    │  │ • Domain        │  │ • 2FA       │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Value Objects

```php
// app/Modules/POS/ValueObjects/Money.php
readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency = 'ARS'
    ) {}

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException();
        }

        return new Money(
            $this->amount + $other->amount,
            $this->currency
        );
    }

    public function multiply(float $factor): Money
    {
        return new Money(
            $this->amount * $factor,
            $this->currency
        );
    }
}
```

### Domain Events

```php
// app/Modules/POS/Events/SaleCompleted.php
class SaleCompleted
{
    public function __construct(
        public readonly Sale $sale,
        public readonly Carbon $completedAt
    ) {}
}

// Listener
class UpdateInventoryListener
{
    public function handle(SaleCompleted $event): void
    {
        // Actualizar inventario
        foreach ($event->sale->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }
    }
}
```

---

## 🔌 API Architecture

### REST API Structure

```
POST   /api/v1/sales                    # Crear venta
GET    /api/v1/sales                    # Listar ventas
GET    /api/v1/sales/{id}               # Obtener venta
PUT    /api/v1/sales/{id}               # Actualizar venta
DELETE /api/v1/sales/{id}               # Eliminar venta

POST   /api/v1/products                 # Crear producto
GET    /api/v1/products                 # Listar productos
GET    /api/v1/products/{id}            # Obtener producto
PUT    /api/v1/products/{id}            # Actualizar producto
DELETE /api/v1/products/{id}            # Eliminar producto
```

### API Versioning

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
});
```

### API Authentication

```php
// config/auth.php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

### Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

---

## 🔄 Event-Driven Architecture

### Event System

```php
// app/Events/TenantCreated.php
class TenantCreated
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly User $owner
    ) {}
}

// Listener
class SetupTenantListener
{
    public function handle(TenantCreated $event): void
    {
        // Crear base de datos
        $this->databaseService->createTenantDatabase($event->tenant);

        // Ejecutar migraciones
        $event->tenant->execute(function () {
            Artisan::call('migrate');
        });

        // Crear usuario admin
        $this->userService->createTenantAdmin($event->tenant, $event->owner);

        // Enviar email de bienvenida
        $this->notificationService->sendWelcomeEmail($event->tenant, $event->owner);
    }
}
```

### Queue System

```php
// app/Jobs/ProcessSale.php
class ProcessSale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $saleData,
        public readonly int $tenantId
    ) {}

    public function handle(): void
    {
        // Ejecutar en contexto del tenant
        $tenant = Tenant::find($this->tenantId);
        $tenant->execute(function () {
            app(SaleService::class)->processSale($this->saleData);
        });
    }
}
```

---

## 🧪 Testing Architecture

### Testing Pyramid

```
┌─────────────────────────────────────────┐
│           END-TO-END TESTS              │
│    (Feature tests - Selenium/Cypress)  │
├─────────────────────────────────────────┤
│           INTEGRATION TESTS             │
│      (HTTP Tests - Laravel Dusk)       │
├─────────────────────────────────────────┤
│            UNIT TESTS                   │
│     (PHPUnit - Models, Services)       │
└─────────────────────────────────────────┘
```

### Test Structure

```
tests/
├── Feature/              # Tests end-to-end
│   ├── POS/
│   │   ├── CreateSaleTest.php
│   │   └── ProcessPaymentTest.php
│   └── Inventory/
│       └── StockMovementTest.php
├── Unit/                 # Tests unitarios
│   ├── Services/
│   │   └── SaleServiceTest.php
│   └── Models/
│       └── ProductTest.php
├── Integration/          # Tests de integración
│   └── Api/
│       └── SalesApiTest.php
└── Database/             # Tests de migraciones
    └── Migrations/
        └── TenantMigrationsTest.php
```

### Testing Multi-Tenant

```php
// tests/Feature/POS/CreateSaleTest.php
class CreateSaleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tenant de prueba
        $this->tenant = Tenant::factory()->create();

        // Ejecutar en contexto del tenant
        $this->tenant->execute(function () {
            // Crear datos de prueba
            $this->product = Product::factory()->create(['stock' => 10]);
            $this->user = User::factory()->create();
        });
    }

    public function test_can_create_sale()
    {
        $this->tenant->execute(function () {
            $saleData = [
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'unit_price' => $this->product->price,
                    ]
                ],
                'payment_method' => 'cash',
                'total' => $this->product->price * 2,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/sales', $saleData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'total',
                        'items',
                        'created_at'
                    ]
                ]);

            // Verificar que el stock se redujo
            $this->assertEquals(8, $this->product->fresh()->stock);
        });
    }
}
```

---

## 🚀 Deployment Architecture

### Infrastructure

```
┌─────────────────────────────────────────────────────────────┐
│                    PRODUCTION INFRA                          │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   LOAD BALANCER │  │   WEB SERVERS   │  │   DATABASE  │ │
│  │   (Nginx)       │  │   (PHP-FPM)     │  │   (RDS)     │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   REDIS CACHE   │  │   QUEUE WORKERS │  │   S3/Cloud  │ │
│  │   (ElastiCache) │  │   (EC2)         │  │   Storage   │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   MONITORING    │  │   LOG AGGREG.  │  │   BACKUPS   │ │
│  │   (DataDog)     │  │   (ELK Stack)   │  │   (S3)      │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: |
          composer install
          php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to production
        run: |
          # Deployment logic here
```

### Blue-Green Deployment

```
┌─────────────────┐    ┌─────────────────┐
│   BLUE ENV      │    │   GREEN ENV     │
│   (v1.0.0)      │    │   (v1.1.0)      │
│   ├─────────────┤    ├─────────────┤   │
│   │ Web Server  │    │ Web Server  │   │
│   │ Database    │    │ Database    │   │
│   │ Cache       │    │ Cache       │   │
│   └─────────────┘    └─────────────┘   │
└─────────────────┘    └─────────────────┘
         │                       │
         └─────── LOAD BALANCER ─┘
                     │
                ┌────▼────┐
                │  USERS  │
                └─────────┘
```

---

## 📊 Performance & Scalability

### Database Optimization

```sql
-- Índices optimizados
CREATE INDEX CONCURRENTLY idx_sales_tenant_date
ON sales (tenant_id, created_at DESC)
WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY idx_products_tenant_category
ON products (tenant_id, category_id)
WHERE deleted_at IS NULL;

-- Partitioning por tenant (futuro)
CREATE TABLE sales_y2025 PARTITION OF sales
FOR VALUES FROM ('2025-01-01') TO ('2026-01-01');
```

### Caching Strategy

```php
// Cache de productos populares
Cache::tags(['tenant', $tenantId])
    ->remember('popular_products', 3600, function () {
        return Product::where('tenant_id', $tenantId)
            ->orderBy('sales_count', 'desc')
            ->limit(10)
            ->get();
    });

// Cache de configuraciones
Cache::rememberForever("tenant_config_{$tenantId}", function () use ($tenantId) {
    return TenantSetting::where('tenant_id', $tenantId)->pluck('value', 'key');
});
```

### Horizontal Scaling

- **Database Sharding**: Por tenant ranges
- **Read Replicas**: Para reportes pesados
- **CDN**: Para assets estáticos
- **Microservicios**: Separar módulos críticos

---

## 🔒 Security Architecture

### Defense in Depth

```
┌─────────────────────────────────────────┐
│           NETWORK LAYER                 │
│  ┌───────────────────────────────────┐  │
│  │  WAF  │  DDoS  │  SSL/TLS  │ VPN │  │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│           APPLICATION LAYER             │
│  ┌───────────────────────────────────┐  │
│  │ Auth │ 2FA │ CSRF │ XSS │ SQLi │   │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│            DATA LAYER                   │
│  ┌───────────────────────────────────┐  │
│  │ Encrypt │ Backup │ Audit │ RLS │   │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### Security Headers

```php
// config/cors.php
return [
    'allowed_headers' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*.kartenant.test'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

// Middleware de seguridad
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        return $response;
    }
}
```

---

## 📈 Monitoring & Observability

### Application Monitoring

```php
// app/Exceptions/Handler.php
public function report(Throwable $exception)
{
    // Reportar a servicio de monitoring
    if (app()->bound('sentry')) {
        app('sentry')->captureException($exception);
    }

    // Log personalizado
    Log::error('Exception occurred', [
        'exception' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
        'user_id' => auth()->id(),
        'tenant_id' => tenant()?->id,
        'url' => request()->fullUrl(),
        'ip' => request()->ip(),
    ]);

    parent::report($exception);
}
```

### Business Metrics

```php
// app/Services/MetricsService.php
class MetricsService
{
    public function recordSale(Sale $sale): void
    {
        // Métricas de negocio
        $this->incrementCounter('sales.total');
        $this->histogram('sales.amount', $sale->total);
        $this->setGauge('sales.active_tenants', Tenant::count());

        // Métricas técnicas
        $this->histogram('sale_processing_time', $this->getProcessingTime());
    }
}
```

---

## 🎯 Conclusión

La arquitectura de Kartenant está diseñada para:

- **Escalabilidad**: Soporta crecimiento de 1 a 10,000+ tenants
- **Mantenibilidad**: Código modular y bien estructurado
- **Seguridad**: Múltiples capas de protección
- **Performance**: Optimizado para alta concurrencia
- **Flexibilidad**: Fácil agregar nuevas funcionalidades

Esta arquitectura ha demostrado ser robusta y escalable, soportando el crecimiento del producto desde MVP hasta plataforma enterprise.

---

**Última actualización:** Octubre 2025
**Versión de arquitectura:** 2.0
**Estado:** Production Ready