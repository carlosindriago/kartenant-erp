<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenant->makeCurrent();
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    #[Test]
    public function it_can_create_a_product()
    {
        $product = Product::create([
            'name' => 'Tornillo 1/4"',
            'sku' => 'TORN-001',
            'price' => 15.50,
            'stock' => 100,
            'min_stock' => 20,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Tornillo 1/4"',
            'sku' => 'TORN-001',
            'price' => 15.50,
        ]);

        $this->assertEquals('Tornillo 1/4"', $product->name);
        $this->assertEquals(100, $product->stock);
    }

    #[Test]
    public function it_can_update_product_stock()
    {
        $product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'PROD-001',
            'price' => 100,
            'stock' => 50,
        ]);

        $product->update(['stock' => 75]);

        $this->assertEquals(75, $product->stock);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 75,
        ]);
    }

    #[Test]
    public function it_detects_low_stock()
    {
        $product = Product::create([
            'name' => 'Producto Bajo Stock',
            'sku' => 'LOW-001',
            'price' => 50,
            'stock' => 5,
            'min_stock' => 10,
        ]);

        $this->assertTrue($product->stock < $product->min_stock);
    }

    #[Test]
    public function it_has_stock_movements_relationship()
    {
        $product = Product::create([
            'name' => 'Producto con Movimientos',
            'sku' => 'PROD-MOV',
            'price' => 100,
            'stock' => 50,
        ]);

        $service = app(StockMovementService::class);

        $service->registerEntry($product, 20, 'Entrada 1', $this->user);
        $service->registerExit($product, 10, 'Salida 1', $this->user);
        $service->registerEntry($product, 15, 'Entrada 2', $this->user);

        $this->assertCount(3, $product->stockMovements);
        $this->assertInstanceOf(StockMovement::class, $product->stockMovements->first());
    }

    #[Test]
    public function it_can_calculate_total_entries()
    {
        $product = Product::create([
            'name' => 'Producto Entradas',
            'sku' => 'PROD-ENT',
            'price' => 100,
            'stock' => 50,
        ]);

        $service = app(StockMovementService::class);

        $service->registerEntry($product, 20, 'Entrada 1', $this->user);
        $service->registerEntry($product, 30, 'Entrada 2', $this->user);
        $service->registerEntry($product, 10, 'Entrada 3', $this->user);

        $totalEntries = $product->stockMovements()
            ->where('type', 'entrada')
            ->sum('quantity');

        $this->assertEquals(60, $totalEntries);
    }

    #[Test]
    public function it_can_calculate_total_exits()
    {
        $product = Product::create([
            'name' => 'Producto Salidas',
            'sku' => 'PROD-SAL',
            'price' => 100,
            'stock' => 100,
        ]);

        $service = app(StockMovementService::class);

        $service->registerExit($product, 15, 'Salida 1', $this->user);
        $service->registerExit($product, 25, 'Salida 2', $this->user);
        $service->registerExit($product, 10, 'Salida 3', $this->user);

        $totalExits = $product->stockMovements()
            ->where('type', 'salida')
            ->sum('quantity');

        $this->assertEquals(50, $totalExits);
    }

    #[Test]
    public function it_can_update_price()
    {
        $product = Product::create([
            'name' => 'Producto Precio',
            'sku' => 'PROD-PRICE',
            'price' => 100.00,
            'stock' => 10,
        ]);

        $product->update(['price' => 125.50]);

        $this->assertEquals(125.50, $product->price);
    }

    #[Test]
    public function it_can_have_optional_description()
    {
        $product = Product::create([
            'name' => 'Producto con Descripción',
            'sku' => 'PROD-DESC',
            'price' => 50,
            'stock' => 20,
            'description' => 'Esta es una descripción detallada del producto.',
        ]);

        $this->assertEquals('Esta es una descripción detallada del producto.', $product->description);
    }

    #[Test]
    public function it_can_search_by_sku()
    {
        Product::create(['name' => 'Producto 1', 'sku' => 'ABC-001', 'price' => 10, 'stock' => 5]);
        Product::create(['name' => 'Producto 2', 'sku' => 'XYZ-002', 'price' => 20, 'stock' => 10]);
        Product::create(['name' => 'Producto 3', 'sku' => 'ABC-003', 'price' => 30, 'stock' => 15]);

        $results = Product::where('sku', 'like', 'ABC%')->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_can_search_by_name()
    {
        Product::create(['name' => 'Tornillo grande', 'sku' => 'TOR-001', 'price' => 10, 'stock' => 5]);
        Product::create(['name' => 'Tuerca pequeña', 'sku' => 'TUE-001', 'price' => 5, 'stock' => 10]);
        Product::create(['name' => 'Tornillo pequeño', 'sku' => 'TOR-002', 'price' => 8, 'stock' => 15]);

        $results = Product::where('name', 'like', '%Tornillo%')->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $product = Product::create([
            'name' => 'Producto Timestamps',
            'sku' => 'TIME-001',
            'price' => 100,
            'stock' => 50,
        ]);

        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
    }

    #[Test]
    public function it_prevents_duplicate_sku_in_same_tenant()
    {
        Product::create([
            'name' => 'Producto 1',
            'sku' => 'UNIQUE-001',
            'price' => 100,
            'stock' => 10,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::create([
            'name' => 'Producto 2',
            'sku' => 'UNIQUE-001', // SKU duplicado
            'price' => 200,
            'stock' => 20,
        ]);
    }

    #[Test]
    public function it_can_soft_delete_product()
    {
        $product = Product::create([
            'name' => 'Producto a Eliminar',
            'sku' => 'DEL-001',
            'price' => 50,
            'stock' => 10,
        ]);

        $productId = $product->id;

        $product->delete();

        // Si el modelo usa SoftDeletes
        $this->assertSoftDeleted('products', [
            'id' => $productId,
        ]);
    }

    #[Test]
    public function it_logs_activity_on_creation()
    {
        $product = Product::create([
            'name' => 'Producto Log',
            'sku' => 'LOG-001',
            'price' => 100,
            'stock' => 50,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'event' => 'created',
        ]);
    }

    #[Test]
    public function it_logs_activity_on_update()
    {
        $product = Product::create([
            'name' => 'Producto Original',
            'sku' => 'UPD-001',
            'price' => 100,
            'stock' => 50,
        ]);

        $product->update([
            'name' => 'Producto Actualizado',
            'price' => 150,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'event' => 'updated',
        ]);
    }

    #[Test]
    public function it_can_filter_products_with_low_stock()
    {
        Product::create(['name' => 'Stock Alto', 'sku' => 'HIGH-001', 'price' => 10, 'stock' => 100, 'min_stock' => 20]);
        Product::create(['name' => 'Stock Bajo 1', 'sku' => 'LOW-001', 'price' => 10, 'stock' => 5, 'min_stock' => 20]);
        Product::create(['name' => 'Stock Bajo 2', 'sku' => 'LOW-002', 'price' => 10, 'stock' => 10, 'min_stock' => 25]);

        $lowStockProducts = Product::whereColumn('stock', '<', 'min_stock')->get();

        $this->assertCount(2, $lowStockProducts);
    }

    #[Test]
    public function it_can_order_by_stock_ascending()
    {
        Product::create(['name' => 'Stock 100', 'sku' => 'S-100', 'price' => 10, 'stock' => 100]);
        Product::create(['name' => 'Stock 10', 'sku' => 'S-10', 'price' => 10, 'stock' => 10]);
        Product::create(['name' => 'Stock 50', 'sku' => 'S-50', 'price' => 10, 'stock' => 50]);

        $products = Product::orderBy('stock', 'asc')->get();

        $this->assertEquals(10, $products[0]->stock);
        $this->assertEquals(50, $products[1]->stock);
        $this->assertEquals(100, $products[2]->stock);
    }

    #[Test]
    public function it_can_calculate_stock_value()
    {
        $product = Product::create([
            'name' => 'Producto Valor',
            'sku' => 'VAL-001',
            'price' => 25.50,
            'stock' => 100,
        ]);

        $stockValue = $product->price * $product->stock;

        $this->assertEquals(2550.00, $stockValue);
    }
}
