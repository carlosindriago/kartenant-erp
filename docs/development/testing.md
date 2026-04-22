# 🧪 Guía de Testing - Kartenant

Documentación completa para testing automatizado y manual en Kartenant.

## 📋 Visión General

Kartenant utiliza una estrategia de testing comprehensiva que incluye:

- **Unit Tests**: Testing de clases individuales
- **Feature Tests**: Testing end-to-end de funcionalidades
- **Integration Tests**: Testing de integración entre módulos
- **Database Tests**: Testing de migraciones y seeds
- **Browser Tests**: Testing con Selenium/Dusk

## 🏗️ Arquitectura de Testing

### Estructura de Tests

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
├── Database/             # Tests de migraciones
│   └── Migrations/
│       └── TenantMigrationsTest.php
└── Browser/              # Tests con navegador
    └── POSWorkflowTest.php
```

### Configuración Base

**phpunit.xml:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
        <testsuite name="Database">
            <directory suffix="Test.php">./tests/Database</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </coverage>
</phpunit>
```

## 🧪 Unit Tests

### Testing de Modelos

**tests/Unit/Models/ProductTest.php:**
```php
<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_correct_fillable_attributes()
    {
        $fillable = [
            'tenant_id', 'name', 'sku', 'barcode', 'description',
            'price', 'category_id', 'tax_id', 'stock', 'min_stock',
            'is_active', 'track_stock', 'allow_negative_stock'
        ];

        $product = new Product();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $product->getFillable());
        }
    }

    public function test_product_belongs_to_category()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_belongs_to_tax()
    {
        $tax = Tax::factory()->create();
        $product = Product::factory()->create(['tax_id' => $tax->id]);

        $this->assertInstanceOf(Tax::class, $product->tax);
        $this->assertEquals($tax->id, $product->tax->id);
    }

    public function test_product_calculates_final_price_correctly()
    {
        $tax = Tax::factory()->create(['rate' => 21.00]);
        $product = Product::factory()->create([
            'price' => 1000.00,
            'tax_id' => $tax->id
        ]);

        $this->assertEquals(1000.00, $product->price);
        $this->assertEquals(210.00, $product->tax_amount);
        $this->assertEquals(1210.00, $product->final_price);
    }

    public function test_product_scopes_work_correctly()
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::active()->get();
        $this->assertEquals(1, $activeProducts->count());
        $this->assertTrue($activeProducts->first()->is_active);
    }

    public function test_product_validation_rules()
    {
        $rules = Product::rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('sku', $rules);

        // Verificar que SKU es único por tenant
        $this->assertStringContains('unique:products,sku', $rules['sku']);
    }
}
```

### Testing de Servicios

**tests/Unit/Services/SaleServiceTest.php:**
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saleService = app(SaleService::class);
    }

    public function test_calculate_totals_works_correctly()
    {
        $product1 = Product::factory()->create(['price' => 1000.00]);
        $product2 = Product::factory()->create(['price' => 500.00]);

        $cart = [
            ['product' => $product1, 'qty' => 2],
            ['product' => $product2, 'qty' => 1]
        ];

        $totals = $this->saleService->calculateTotals($cart);

        $this->assertEquals(2500.00, $totals['subtotal']); // (1000*2) + (500*1)
        $this->assertEquals(525.00, $totals['tax_amount']); // 2500 * 0.21
        $this->assertEquals(3025.00, $totals['total']);     // 2500 + 525
    }

    public function test_process_sale_creates_sale_and_items()
    {
        $product = Product::factory()->create(['stock' => 10]);

        $saleData = [
            'customer_id' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2
                ]
            ],
            'payment_method' => 'cash'
        ];

        $sale = $this->saleService->processSale($saleData);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals(1, $sale->items->count());
        $this->assertEquals(2, $sale->items->first()->quantity);
        $this->assertEquals(8, $product->fresh()->stock); // Stock reducido
    }

    public function test_process_sale_fails_with_insufficient_stock()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $product = Product::factory()->create(['stock' => 1]);

        $saleData = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5 // Más que el stock disponible
                ]
            ],
            'payment_method' => 'cash'
        ];

        $this->saleService->processSale($saleData);
    }

    public function test_generate_invoice_number_format()
    {
        $number = $this->saleService->generateInvoiceNumber();

        $this->assertStringStartsWith('FAC-', $number);
        $this->assertMatchesRegularExpression('/FAC-\d{8}-\d{4}/', $number);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

## 🎯 Feature Tests

### Testing del POS

**tests/Feature/POS/CreateSaleTest.php:**
```php
<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tenant de prueba
        $this->tenant = \App\Models\Tenant::factory()->create();

        // Ejecutar en contexto del tenant
        $this->tenant->execute(function () {
            $this->user = User::factory()->create();
            $this->product = Product::factory()->create([
                'stock' => 10,
                'price' => 1000.00
            ]);
        });
    }

    public function test_user_can_create_sale_via_api()
    {
        $this->tenant->execute(function () {
            $saleData = [
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2
                    ]
                ],
                'payment_method' => 'cash',
                'notes' => 'Venta de prueba'
            ];

            $response = $this->actingAs($this->user, 'web')
                ->postJson('/api/v1/sales', $saleData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'invoice_number',
                        'total',
                        'items',
                        'created_at'
                    ]
                ]);

            // Verificar que se creó en BD
            $this->assertDatabaseHas('sales', [
                'payment_method' => 'cash',
                'total' => 2420.00 // 2000 + 21% IVA
            ]);

            // Verificar que el stock se actualizó
            $this->assertEquals(8, $this->product->fresh()->stock);
        });
    }

    public function test_sale_creation_validates_required_fields()
    {
        $this->tenant->execute(function () {
            $response = $this->actingAs($this->user, 'web')
                ->postJson('/api/v1/sales', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['items']);
        });
    }

    public function test_sale_creation_fails_with_invalid_product()
    {
        $this->tenant->execute(function () {
            $saleData = [
                'items' => [
                    [
                        'product_id' => 99999, // Producto inexistente
                        'quantity' => 1
                    ]
                ],
                'payment_method' => 'cash'
            ];

            $response = $this->actingAs($this->user, 'web')
                ->postJson('/api/v1/sales', $saleData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['items.0.product_id']);
        });
    }

    public function test_sale_creation_fails_with_insufficient_stock()
    {
        $this->tenant->execute(function () {
            $saleData = [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 20 // Más que el stock disponible
                    ]
                ],
                'payment_method' => 'cash'
            ];

            $response = $this->actingAs($this->user, 'web')
                ->postJson('/api/v1/sales', $saleData);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INSUFFICIENT_STOCK'
                    ]
                ]);
        });
    }
}
```

### Testing Multi-Tenant

**tests/Feature/MultiTenantTest.php:**
```php
<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_isolation_works()
    {
        // Crear dos tenants
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Crear productos en tenant 1
        $tenant1->execute(function () {
            \App\Models\Product::factory()->create(['name' => 'Producto Tenant 1']);
        });

        // Crear productos en tenant 2
        $tenant2->execute(function () {
            \App\Models\Product::factory()->create(['name' => 'Producto Tenant 2']);
        });

        // Verificar aislamiento
        $tenant1->execute(function () {
            $products = \App\Models\Product::all();
            $this->assertEquals(1, $products->count());
            $this->assertEquals('Producto Tenant 1', $products->first()->name);
        });

        $tenant2->execute(function () {
            $products = \App\Models\Product::all();
            $this->assertEquals(1, $products->count());
            $this->assertEquals('Producto Tenant 2', $products->first()->name);
        });
    }

    public function test_cross_tenant_user_relations_work()
    {
        $tenant = Tenant::factory()->create();

        $tenant->execute(function () {
            $user = User::factory()->create();
            $sale = \App\Models\Sale::factory()->create(['user_id' => $user->id]);

            // Verificar que la relación funciona
            $this->assertEquals($user->id, $sale->user->id);
            $this->assertEquals($user->name, $sale->user->name);
        });
    }
}
```

## 🔄 Integration Tests

### Testing de API

**tests/Integration/Api/SalesApiTest.php:**
```php
<?php

namespace Tests\Integration\Api;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SalesApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = \App\Models\Tenant::factory()->create();

        $this->tenant->execute(function () {
            $this->user = User::factory()->create();
            $this->product = Product::factory()->create(['stock' => 10]);

            // Crear token de API para el usuario
            Passport::actingAs($this->user);
        });
    }

    public function test_can_list_sales()
    {
        $this->tenant->execute(function () {
            Sale::factory()->count(3)->create();

            $response = $this->getJson('/api/v1/sales');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'invoice_number',
                            'total',
                            'status',
                            'created_at'
                        ]
                    ],
                    'meta' => [
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total'
                        ]
                    ]
                ]);
        });
    }

    public function test_can_create_sale_via_api()
    {
        $this->tenant->execute(function () {
            $saleData = [
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2
                    ]
                ],
                'payment_method' => 'cash'
            ];

            $response = $this->postJson('/api/v1/sales', $saleData);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total' => 2420.00,
                        'payment_method' => 'cash',
                        'status' => 'completed'
                    ]
                ]);

            $this->assertDatabaseHas('sales', [
                'total' => 2420.00,
                'payment_method' => 'cash'
            ]);
        });
    }

    public function test_api_rate_limiting_works()
    {
        $this->tenant->execute(function () {
            // Hacer muchas requests para testear rate limiting
            for ($i = 0; $i < 110; $i++) {
                $response = $this->getJson('/api/v1/sales');
                if ($i < 100) {
                    $response->assertStatus(200);
                }
            }

            // La request 101 debería ser rate limited
            $this->getJson('/api/v1/sales')
                ->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'RATE_LIMITED'
                    ]
                ]);
        });
    }
}
```

## 🗄️ Database Tests

### Testing de Migraciones

**tests/Database/Migrations/TenantMigrationsTest.php:**
```php
<?php

namespace Tests\Database\Migrations;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantMigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_migrations_create_all_tables()
    {
        $tenant = Tenant::factory()->create();

        $tenant->execute(function () {
            $expectedTables = [
                'users',
                'categories',
                'taxes',
                'products',
                'customers',
                'suppliers',
                'sales',
                'sale_items',
                'stock_movements',
                'cash_register_openings',
                'cash_register_closings',
                'sale_returns',
                'sale_return_items',
                'customer_credits',
                'customer_credit_applications'
            ];

            foreach ($expectedTables as $table) {
                $this->assertTrue(
                    Schema::hasTable($table),
                    "Table '{$table}' should exist"
                );
            }
        });
    }

    public function test_tenant_tables_have_correct_columns()
    {
        $tenant = Tenant::factory()->create();

        $tenant->execute(function () {
            // Verificar columnas de products
            $this->assertTrue(Schema::hasColumn('products', 'tenant_id'));
            $this->assertTrue(Schema::hasColumn('products', 'name'));
            $this->assertTrue(Schema::hasColumn('products', 'price'));
            $this->assertTrue(Schema::hasColumn('products', 'stock'));

            // Verificar columnas de sales
            $this->assertTrue(Schema::hasColumn('sales', 'tenant_id'));
            $this->assertTrue(Schema::hasColumn('sales', 'invoice_number'));
            $this->assertTrue(Schema::hasColumn('sales', 'total'));
            $this->assertTrue(Schema::hasColumn('sales', 'payment_method'));
        });
    }

    public function test_tenant_foreign_keys_work()
    {
        $tenant = Tenant::factory()->create();

        $tenant->execute(function () {
            // Crear registros de prueba
            $category = \App\Models\Category::factory()->create();
            $tax = \App\Models\Tax::factory()->create();

            $product = \App\Models\Product::factory()->create([
                'category_id' => $category->id,
                'tax_id' => $tax->id
            ]);

            // Verificar que las relaciones funcionan
            $this->assertEquals($category->id, $product->category->id);
            $this->assertEquals($tax->id, $product->tax->id);
        });
    }

    public function test_tenant_indexes_exist()
    {
        $tenant = Tenant::factory()->create();

        $tenant->execute(function () {
            // Verificar índices importantes
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('products');

            $indexNames = array_keys($indexes);

            $this->assertContains('idx_tenant_active', $indexNames);
            $this->assertContains('idx_sku', $indexNames);
            $this->assertContains('idx_barcode', $indexNames);
        });
    }
}
```

## 🌐 Browser Tests (Dusk)

### Testing del POS en Navegador

**tests/Browser/POSWorkflowTest.php:**
```php
<?php

namespace Tests\Browser;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class POSWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_complete_pos_workflow()
    {
        $tenant = \App\Models\Tenant::factory()->create();

        $tenant->execute(function () {
            $user = User::factory()->create();
            $product = Product::factory()->create([
                'name' => 'Test Product',
                'price' => 1000.00,
                'stock' => 10
            ]);

            $this->browse(function (Browser $browser) use ($tenant, $user, $product) {
                // Login
                $browser->visit("https://{$tenant->domain}.test/app/login")
                    ->type('email', $user->email)
                    ->type('password', 'password')
                    ->press('Login')
                    ->waitForLocation('/app');

                // Ir al POS
                $browser->clickLink('Punto de Venta')
                    ->waitForLocation('/pos');

                // Verificar que estamos en POS fullscreen
                $browser->assertPathIs('/pos')
                    ->assertMissing('.sidebar') // No debería haber sidebar
                    ->assertMissing('.topbar'); // No debería haber topbar

                // Buscar producto
                $browser->type('#product-search', 'Test Product')
                    ->waitFor('.product-item')
                    ->click('.product-item');

                // Verificar que se agregó al carrito
                $browser->assertSee('Test Product')
                    ->assertSee('$1,210.00'); // Precio con IVA

                // Procesar pago
                $browser->press('#process-payment')
                    ->waitFor('#payment-modal')
                    ->select('payment_method', 'cash')
                    ->type('amount_received', '1500')
                    ->press('#confirm-payment');

                // Verificar venta completada
                $browser->assertSee('Venta completada')
                    ->assertSee('Cambio: $90.00');
            });
        });
    }

    public function test_pos_handles_insufficient_stock()
    {
        $tenant = \App\Models\Tenant::factory()->create();

        $tenant->execute(function () {
            $user = User::factory()->create();
            $product = Product::factory()->create([
                'stock' => 1 // Solo 1 en stock
            ]);

            $this->browse(function (Browser $browser) use ($tenant, $user, $product) {
                // Login y agregar producto 5 veces (más que el stock)
                $browser->visit("https://{$tenant->domain}.test/pos")
                    ->waitFor('#product-search');

                for ($i = 0; $i < 5; $i++) {
                    $browser->type('#product-search', $product->name)
                        ->waitFor('.product-item')
                        ->click('.product-item');
                }

                // Intentar procesar pago
                $browser->press('#process-payment')
                    ->assertSee('Stock insuficiente');
            });
        });
    }
}
```

## 🏃‍♂️ Ejecutar Tests

### Comandos Básicos

```bash
# Ejecutar todos los tests
./vendor/bin/phpunit

# Ejecutar solo unit tests
./vendor/bin/phpunit --testsuite Unit

# Ejecutar solo feature tests
./vendor/bin/phpunit --testsuite Feature

# Ejecutar tests de un archivo específico
./vendor/bin/phpunit tests/Unit/Models/ProductTest.php

# Ejecutar tests con coverage
./vendor/bin/phpunit --coverage-html reports/coverage

# Ejecutar tests en paralelo
./vendor/bin/phpunit --parallel
```

### Tests con Docker

```bash
# Ejecutar tests
./vendor/bin/sail test

# Ejecutar tests con coverage
./vendor/bin/sail test --coverage-html reports/coverage

# Ejecutar solo un grupo de tests
./vendor/bin/sail test --testsuite Unit
```

### Tests de Browser (Dusk)

```bash
# Instalar Chrome Driver
./vendor/bin/dusk-updater detect

# Ejecutar tests de browser
./vendor/bin/dusk

# Ejecutar en headless mode
./vendor/bin/dusk --headless
```

## 📊 Cobertura de Código

### Configuración de Coverage

**phpunit.xml:**
```xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory>./app/Console</directory>
        <file>./app/Http/Kernel.php</file>
    </exclude>
    <report>
        <html outputDirectory="reports/coverage" />
        <text outputFile="reports/coverage.txt" />
        <clover outputFile="reports/coverage.xml" />
    </report>
</coverage>
```

### Métricas Objetivo

- **Unit Tests**: 80% cobertura mínima
- **Feature Tests**: 90% cobertura de funcionalidades críticas
- **Integration Tests**: 95% cobertura de APIs
- **Browser Tests**: 100% cobertura de flujos críticos

## 🔄 CI/CD Integration

### GitHub Actions

**.github/workflows/tests.yml:**
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, pdo_pgsql

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader

      - name: Copy environment file
        run: cp .env.ci .env

      - name: Generate key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run tests
        run: ./vendor/bin/phpunit --coverage-text --min=80

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./reports/coverage.xml
```

## 🐛 Debugging Tests

### Tests que Fallan

**Problema: Database not found**
```bash
# Solución: Usar RefreshDatabase o DatabaseMigrations
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // Esto crea una BD limpia para cada test
}
```

**Problema: Tenant context lost**
```bash
# Solución: Ejecutar todo dentro de tenant->execute()
$tenant->execute(function () {
    // Todo el código del test aquí
    $product = Product::factory()->create();
    // ...
});
```

**Problema: Foreign key constraints**
```bash
# Solución: Crear dependencias en orden correcto
$category = Category::factory()->create();
$tax = Tax::factory()->create();
$product = Product::factory()->create([
    'category_id' => $category->id,
    'tax_id' => $tax->id
]);
```

### Debugging Tools

```php
// Ver queries ejecutadas
\DB::enableQueryLog();
$products = Product::all();
dd(\DB::getQueryLog());

// Ver estructura de BD
\Schema::getColumnListing('products');

// Ver conexiones activas
\DB::getConnections();
```

## 📈 Mejores Prácticas

### Estructura de Tests

1. **Arrange**: Preparar datos de prueba
2. **Act**: Ejecutar la acción a testear
3. **Assert**: Verificar resultados

### Factories para Datos de Prueba

```php
// database/factories/ProductFactory.php
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->word(),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'price' => $this->faker->randomFloat(2, 10, 10000),
            'stock' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock()
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 1,
            'min_stock' => 5,
        ]);
    }
}
```

### Testing de Edge Cases

```php
public function test_sale_with_zero_quantity_fails()
{
    // Testear validaciones
}

public function test_sale_with_negative_price_fails()
{
    // Testear validaciones
}

public function test_concurrent_sales_dont_oversell_stock()
{
    // Testear race conditions
}
```

---

**Última actualización:** Octubre 2025
**Cobertura actual:** 75%
**Objetivo:** 85% para fin de año