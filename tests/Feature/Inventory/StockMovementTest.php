<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Supplier;
use App\Services\StockMovementService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Product $product;

    protected StockMovementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario en landlord DB
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Crear tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        // Hacer actual al tenant
        $this->tenant->makeCurrent();

        // Crear producto en tenant DB
        $this->product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'TEST-001',
            'price' => 100.00,
            'stock' => 50,
            'min_stock' => 10,
        ]);

        $this->service = app(StockMovementService::class);
    }

    protected function tearDown(): void
    {
        // Limpiar tenant
        Tenant::forgetCurrent();

        parent::tearDown();
    }

    /** @test */
    public function it_can_register_stock_entry_without_supplier()
    {
        $initialStock = $this->product->stock;

        $movement = $this->service->registerEntry(
            product: $this->product,
            quantity: 20,
            reason: 'Compra de prueba',
            registeredBy: $this->user,
            supplierId: null,
            reference: 'REF-001'
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals('entrada', $movement->type);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals($initialStock, $movement->previous_stock);
        $this->assertEquals($initialStock + 20, $movement->new_stock);
        $this->assertEquals('Compra de prueba', $movement->reason);
        $this->assertEquals('REF-001', $movement->reference);
        $this->assertNull($movement->supplier_id);

        // Verificar que el stock del producto se actualizó
        $this->product->refresh();
        $this->assertEquals($initialStock + 20, $this->product->stock);
    }

    /** @test */
    public function it_can_register_stock_entry_with_supplier()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor Test',
            'contact_name' => 'Juan Pérez',
            'email' => 'juan@proveedor.com',
            'phone' => '555-1234',
        ]);

        $initialStock = $this->product->stock;

        $movement = $this->service->registerEntry(
            product: $this->product,
            quantity: 30,
            reason: 'Compra a proveedor',
            registeredBy: $this->user,
            supplierId: $supplier->id,
            reference: 'FAC-001'
        );

        $this->assertEquals($supplier->id, $movement->supplier_id);
        $this->assertEquals('Proveedor Test', $movement->supplier->name);

        $this->product->refresh();
        $this->assertEquals($initialStock + 30, $this->product->stock);
    }

    /** @test */
    public function it_can_register_stock_exit()
    {
        $initialStock = $this->product->stock;

        $movement = $this->service->registerExit(
            product: $this->product,
            quantity: 15,
            reason: 'Venta',
            registeredBy: $this->user,
            reference: 'VENTA-001'
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals('salida', $movement->type);
        $this->assertEquals(15, $movement->quantity);
        $this->assertEquals($initialStock, $movement->previous_stock);
        $this->assertEquals($initialStock - 15, $movement->new_stock);

        // Verificar que el stock del producto se actualizó
        $this->product->refresh();
        $this->assertEquals($initialStock - 15, $this->product->stock);
    }

    /** @test */
    public function it_prevents_negative_stock_on_exit()
    {
        $this->product->update(['stock' => 5]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->service->registerExit(
            product: $this->product,
            quantity: 10,
            reason: 'Venta',
            registeredBy: $this->user
        );
    }

    /** @test */
    public function it_generates_unique_document_numbers_for_entries()
    {
        $movement1 = $this->service->registerEntry(
            product: $this->product,
            quantity: 10,
            reason: 'Compra 1',
            registeredBy: $this->user
        );

        $movement2 = $this->service->registerEntry(
            product: $this->product,
            quantity: 20,
            reason: 'Compra 2',
            registeredBy: $this->user
        );

        $this->assertNotEquals($movement1->document_number, $movement2->document_number);
        $this->assertStringStartsWith('ENT-', $movement1->document_number);
        $this->assertStringStartsWith('ENT-', $movement2->document_number);
    }

    /** @test */
    public function it_generates_unique_document_numbers_for_exits()
    {
        $movement1 = $this->service->registerExit(
            product: $this->product,
            quantity: 5,
            reason: 'Venta 1',
            registeredBy: $this->user
        );

        $movement2 = $this->service->registerExit(
            product: $this->product,
            quantity: 10,
            reason: 'Venta 2',
            registeredBy: $this->user
        );

        $this->assertNotEquals($movement1->document_number, $movement2->document_number);
        $this->assertStringStartsWith('SAL-', $movement1->document_number);
        $this->assertStringStartsWith('SAL-', $movement2->document_number);
    }

    /** @test */
    public function it_can_filter_movements_by_type()
    {
        // Crear movimientos de entrada
        $this->service->registerEntry($this->product, 10, 'Entrada 1', $this->user);
        $this->service->registerEntry($this->product, 20, 'Entrada 2', $this->user);

        // Crear movimientos de salida
        $this->service->registerExit($this->product, 5, 'Salida 1', $this->user);

        $entries = StockMovement::entries()->get();
        $exits = StockMovement::exits()->get();

        $this->assertCount(2, $entries);
        $this->assertCount(1, $exits);
    }

    /** @test */
    public function it_can_filter_recent_movements()
    {
        // Crear movimiento reciente
        $recent = $this->service->registerEntry($this->product, 10, 'Reciente', $this->user);

        // Crear movimiento antiguo (simular)
        $old = $this->service->registerEntry($this->product, 5, 'Antiguo', $this->user);
        $old->created_at = now()->subDays(40);
        $old->save();

        $recentMovements = StockMovement::recent(30)->get();

        $this->assertCount(1, $recentMovements);
        $this->assertEquals('Reciente', $recentMovements->first()->reason);
    }

    /** @test */
    public function it_stores_user_name_on_movement_creation()
    {
        $movement = $this->service->registerEntry(
            product: $this->product,
            quantity: 10,
            reason: 'Test',
            registeredBy: $this->user
        );

        $this->assertEquals($this->user->name, $movement->user_name);
    }

    /** @test */
    public function it_can_access_authorized_by_user_via_trait()
    {
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'type' => 'entrada',
            'quantity' => 10,
            'reason' => 'Test',
            'previous_stock' => $this->product->stock,
            'new_stock' => $this->product->stock + 10,
            'user_name' => $this->user->name,
            'authorized_by' => $this->user->id,
        ]);

        // Usar el trait HasCrossDatabaseUserRelations
        $authorizedBy = $movement->authorizedBy;

        $this->assertInstanceOf(User::class, $authorizedBy);
        $this->assertEquals($this->user->id, $authorizedBy->id);
        $this->assertEquals($this->user->name, $authorizedBy->name);
    }

    /** @test */
    public function it_can_generate_pdf_for_entry_movement()
    {
        $movement = $this->service->registerEntry(
            product: $this->product,
            quantity: 25,
            reason: 'Compra para PDF',
            registeredBy: $this->user
        );

        $movement->pdf_format = 'a4';
        $pdf = $movement->generatePdf();

        $this->assertInstanceOf(PDF::class, $pdf);
    }

    /** @test */
    public function it_can_generate_pdf_for_exit_movement()
    {
        $movement = $this->service->registerExit(
            product: $this->product,
            quantity: 10,
            reason: 'Venta para PDF',
            registeredBy: $this->user
        );

        $movement->pdf_format = 'thermal';
        $pdf = $movement->generatePdf();

        $this->assertInstanceOf(PDF::class, $pdf);
    }

    /** @test */
    public function it_tracks_stock_changes_correctly_for_multiple_movements()
    {
        $initialStock = 50;
        $this->product->update(['stock' => $initialStock]);

        // Entrada de 30
        $this->service->registerEntry($this->product, 30, 'Entrada 1', $this->user);
        $this->product->refresh();
        $this->assertEquals(80, $this->product->stock);

        // Salida de 20
        $this->service->registerExit($this->product, 20, 'Salida 1', $this->user);
        $this->product->refresh();
        $this->assertEquals(60, $this->product->stock);

        // Entrada de 10
        $this->service->registerEntry($this->product, 10, 'Entrada 2', $this->user);
        $this->product->refresh();
        $this->assertEquals(70, $this->product->stock);

        // Verificar historial
        $movements = StockMovement::where('product_id', $this->product->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(3, $movements);
        $this->assertEquals(50, $movements[0]->previous_stock);
        $this->assertEquals(80, $movements[0]->new_stock);
        $this->assertEquals(80, $movements[1]->previous_stock);
        $this->assertEquals(60, $movements[1]->new_stock);
        $this->assertEquals(60, $movements[2]->previous_stock);
        $this->assertEquals(70, $movements[2]->new_stock);
    }

    /** @test */
    public function it_belongs_to_product()
    {
        $movement = $this->service->registerEntry($this->product, 10, 'Test', $this->user);

        $this->assertInstanceOf(Product::class, $movement->product);
        $this->assertEquals($this->product->id, $movement->product->id);
    }

    /** @test */
    public function it_belongs_to_supplier_when_provided()
    {
        $supplier = Supplier::create([
            'name' => 'Supplier Test',
            'email' => 'supplier@test.com',
        ]);

        $movement = $this->service->registerEntry(
            product: $this->product,
            quantity: 10,
            reason: 'Test',
            registeredBy: $this->user,
            supplierId: $supplier->id
        );

        $this->assertInstanceOf(Supplier::class, $movement->supplier);
        $this->assertEquals($supplier->id, $movement->supplier->id);
    }
}
