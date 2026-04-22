<?php

namespace Tests\Feature\Multitenancy;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Customer;
use App\Modules\POS\Models\Sale;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant1;
    protected Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant 1',
            'domain' => 'tenant1.test.com',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'domain' => 'tenant2.test.com',
        ]);
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    /** @test */
    public function products_are_isolated_between_tenants()
    {
        // Crear producto en tenant 1
        $this->tenant1->makeCurrent();
        $product1 = Product::create([
            'name' => 'Producto Tenant 1',
            'sku' => 'T1-001',
            'price' => 100,
            'stock' => 50,
        ]);
        Tenant::forgetCurrent();

        // Crear producto en tenant 2
        $this->tenant2->makeCurrent();
        $product2 = Product::create([
            'name' => 'Producto Tenant 2',
            'sku' => 'T2-001',
            'price' => 200,
            'stock' => 30,
        ]);

        // Verificar que tenant 2 solo ve su producto
        $products = Product::all();
        $this->assertCount(1, $products);
        $this->assertEquals('Producto Tenant 2', $products->first()->name);
        $this->assertNotContains('Producto Tenant 1', $products->pluck('name'));

        Tenant::forgetCurrent();

        // Verificar que tenant 1 solo ve su producto
        $this->tenant1->makeCurrent();
        $products = Product::all();
        $this->assertCount(1, $products);
        $this->assertEquals('Producto Tenant 1', $products->first()->name);
    }

    /** @test */
    public function sales_are_isolated_between_tenants()
    {
        // Crear venta en tenant 1
        $this->tenant1->makeCurrent();
        $cashRegister1 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $sale1 = Sale::create([
            'cash_register_id' => $cashRegister1->id,
            'invoice_number' => 'T1-FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);
        Tenant::forgetCurrent();

        // Crear venta en tenant 2
        $this->tenant2->makeCurrent();
        $cashRegister2 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 150,
            'status' => 'open',
        ]);

        $sale2 = Sale::create([
            'cash_register_id' => $cashRegister2->id,
            'invoice_number' => 'T2-FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 200,
            'total' => 200,
            'payment_method' => 'card',
        ]);

        // Verificar aislamiento
        $sales = Sale::all();
        $this->assertCount(1, $sales);
        $this->assertEquals('T2-FAC-001', $sales->first()->invoice_number);
    }

    /** @test */
    public function stock_movements_are_isolated_between_tenants()
    {
        $service = app(StockMovementService::class);

        // Crear movimiento en tenant 1
        $this->tenant1->makeCurrent();
        $product1 = Product::create([
            'name' => 'Producto T1',
            'sku' => 'T1-PROD',
            'price' => 100,
            'stock' => 50,
        ]);

        $movement1 = $service->registerEntry($product1, 20, 'Entrada T1', $this->user);
        Tenant::forgetCurrent();

        // Crear movimiento en tenant 2
        $this->tenant2->makeCurrent();
        $product2 = Product::create([
            'name' => 'Producto T2',
            'sku' => 'T2-PROD',
            'price' => 200,
            'stock' => 30,
        ]);

        $movement2 = $service->registerEntry($product2, 30, 'Entrada T2', $this->user);

        // Verificar aislamiento
        $movements = StockMovement::all();
        $this->assertCount(1, $movements);
        $this->assertEquals('Entrada T2', $movements->first()->reason);
        $this->assertEquals($product2->id, $movements->first()->product_id);
    }

    /** @test */
    public function suppliers_are_isolated_between_tenants()
    {
        // Crear proveedor en tenant 1
        $this->tenant1->makeCurrent();
        $supplier1 = Supplier::create([
            'name' => 'Proveedor Tenant 1',
            'email' => 't1@supplier.com',
        ]);
        Tenant::forgetCurrent();

        // Crear proveedor en tenant 2
        $this->tenant2->makeCurrent();
        $supplier2 = Supplier::create([
            'name' => 'Proveedor Tenant 2',
            'email' => 't2@supplier.com',
        ]);

        // Verificar aislamiento
        $suppliers = Supplier::all();
        $this->assertCount(1, $suppliers);
        $this->assertEquals('Proveedor Tenant 2', $suppliers->first()->name);
    }

    /** @test */
    public function customers_are_isolated_between_tenants()
    {
        // Crear cliente en tenant 1
        $this->tenant1->makeCurrent();
        $customer1 = Customer::create([
            'name' => 'Cliente Tenant 1',
            'email' => 't1@customer.com',
        ]);
        Tenant::forgetCurrent();

        // Crear cliente en tenant 2
        $this->tenant2->makeCurrent();
        $customer2 = Customer::create([
            'name' => 'Cliente Tenant 2',
            'email' => 't2@customer.com',
        ]);

        // Verificar aislamiento
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals('Cliente Tenant 2', $customers->first()->name);
    }

    /** @test */
    public function cash_registers_are_isolated_between_tenants()
    {
        // Crear caja en tenant 1
        $this->tenant1->makeCurrent();
        $cashRegister1 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);
        Tenant::forgetCurrent();

        // Crear caja en tenant 2
        $this->tenant2->makeCurrent();
        $cashRegister2 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 200,
            'status' => 'open',
        ]);

        // Verificar aislamiento
        $registers = CashRegister::all();
        $this->assertCount(1, $registers);
        $this->assertEquals(200, $registers->first()->initial_amount);
    }

    /** @test */
    public function tenant_cannot_access_another_tenant_product_by_id()
    {
        // Crear producto en tenant 1
        $this->tenant1->makeCurrent();
        $product1 = Product::create([
            'name' => 'Producto Secreto T1',
            'sku' => 'SECRET-T1',
            'price' => 999,
            'stock' => 10,
        ]);
        $product1Id = $product1->id;
        Tenant::forgetCurrent();

        // Intentar acceder desde tenant 2
        $this->tenant2->makeCurrent();
        $foundProduct = Product::find($product1Id);

        $this->assertNull($foundProduct);
    }

    /** @test */
    public function tenant_cannot_access_another_tenant_sale_by_id()
    {
        // Crear venta en tenant 1
        $this->tenant1->makeCurrent();
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $sale1 = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'SECRET-SALE',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'cash',
        ]);
        $sale1Id = $sale1->id;
        Tenant::forgetCurrent();

        // Intentar acceder desde tenant 2
        $this->tenant2->makeCurrent();
        $foundSale = Sale::find($sale1Id);

        $this->assertNull($foundSale);
    }

    /** @test */
    public function switching_tenant_context_changes_accessible_data()
    {
        // Crear datos en tenant 1
        $this->tenant1->makeCurrent();
        Product::create(['name' => 'Producto T1', 'sku' => 'T1-001', 'price' => 10, 'stock' => 5]);
        Product::create(['name' => 'Producto T1 B', 'sku' => 'T1-002', 'price' => 20, 'stock' => 10]);
        Tenant::forgetCurrent();

        // Crear datos en tenant 2
        $this->tenant2->makeCurrent();
        Product::create(['name' => 'Producto T2', 'sku' => 'T2-001', 'price' => 30, 'stock' => 15]);
        Tenant::forgetCurrent();

        // Verificar tenant 1
        $this->tenant1->makeCurrent();
        $this->assertCount(2, Product::all());
        Tenant::forgetCurrent();

        // Verificar tenant 2
        $this->tenant2->makeCurrent();
        $this->assertCount(1, Product::all());
        Tenant::forgetCurrent();

        // Sin tenant activo, no debería haber datos accesibles
        $this->assertCount(0, Product::all());
    }

    /** @test */
    public function same_sku_can_exist_in_different_tenants()
    {
        $sku = 'SHARED-SKU-001';

        // Crear producto con mismo SKU en tenant 1
        $this->tenant1->makeCurrent();
        $product1 = Product::create([
            'name' => 'Producto T1',
            'sku' => $sku,
            'price' => 100,
            'stock' => 50,
        ]);
        Tenant::forgetCurrent();

        // Crear producto con mismo SKU en tenant 2
        $this->tenant2->makeCurrent();
        $product2 = Product::create([
            'name' => 'Producto T2',
            'sku' => $sku,
            'price' => 200,
            'stock' => 30,
        ]);

        // No debe lanzar excepción por SKU duplicado
        $this->assertEquals($sku, $product2->sku);

        // Verificar que son diferentes productos
        $this->assertNotEquals($product1->id, $product2->id);
        $this->assertEquals('Producto T2', $product2->name);
    }

    /** @test */
    public function invoice_numbers_are_independent_per_tenant()
    {
        // Tenant 1
        $this->tenant1->makeCurrent();
        $cashRegister1 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $invoice1 = Sale::generateInvoiceNumber();
        Sale::create([
            'cash_register_id' => $cashRegister1->id,
            'invoice_number' => $invoice1,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 100,
            'payment_method' => 'cash',
        ]);
        Tenant::forgetCurrent();

        // Tenant 2
        $this->tenant2->makeCurrent();
        $cashRegister2 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 150,
            'status' => 'open',
        ]);

        $invoice2 = Sale::generateInvoiceNumber();
        Sale::create([
            'cash_register_id' => $cashRegister2->id,
            'invoice_number' => $invoice2,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 200,
            'payment_method' => 'cash',
        ]);

        // Ambos tenants pueden tener numeración similar (independiente)
        // No debe lanzar error de duplicado
        $this->assertStringStartsWith('FAC-', $invoice2);
    }

    /** @test */
    public function tenant_data_persists_correctly_across_context_switches()
    {
        // Crear datos en tenant 1
        $this->tenant1->makeCurrent();
        $product1 = Product::create([
            'name' => 'Original T1',
            'sku' => 'ORIG-T1',
            'price' => 100,
            'stock' => 50,
        ]);
        $product1Id = $product1->id;
        Tenant::forgetCurrent();

        // Cambiar a tenant 2 y crear datos
        $this->tenant2->makeCurrent();
        Product::create([
            'name' => 'Product T2',
            'sku' => 'PROD-T2',
            'price' => 200,
            'stock' => 30,
        ]);
        Tenant::forgetCurrent();

        // Volver a tenant 1 y verificar que los datos persisten
        $this->tenant1->makeCurrent();
        $foundProduct = Product::find($product1Id);

        $this->assertNotNull($foundProduct);
        $this->assertEquals('Original T1', $foundProduct->name);
        $this->assertEquals(100, $foundProduct->price);
    }
}
