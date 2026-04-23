<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Supplier;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class SupplierTest extends TestCase
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

    /** @test */
    public function it_can_create_a_supplier_with_all_fields()
    {
        $supplier = Supplier::create([
            'name' => 'Ferretería Central',
            'contact_name' => 'Carlos Gómez',
            'email' => 'carlos@ferreteria.com',
            'phone' => '555-1234',
            'cuit' => '20-12345678-9',
            'address' => 'Av. Principal 123',
            'notes' => 'Proveedor principal de herramientas',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Ferretería Central',
            'contact_name' => 'Carlos Gómez',
            'email' => 'carlos@ferreteria.com',
            'phone' => '555-1234',
            'cuit' => '20-12345678-9',
        ]);

        $this->assertEquals('Ferretería Central', $supplier->name);
        $this->assertEquals('Proveedor principal de herramientas', $supplier->notes);
    }

    /** @test */
    public function it_can_create_a_supplier_with_only_required_fields()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor Simple',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Proveedor Simple',
        ]);

        $this->assertNull($supplier->email);
        $this->assertNull($supplier->phone);
        $this->assertNull($supplier->contact_name);
    }

    /** @test */
    public function it_has_stock_movements_relationship()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor Test',
        ]);

        $product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'PROD-001',
            'price' => 100,
            'stock' => 10,
        ]);

        $service = app(StockMovementService::class);

        // Crear 3 movimientos para este proveedor
        $service->registerEntry($product, 10, 'Compra 1', $this->user, $supplier->id);
        $service->registerEntry($product, 20, 'Compra 2', $this->user, $supplier->id);
        $service->registerEntry($product, 15, 'Compra 3', $this->user, $supplier->id);

        $this->assertCount(3, $supplier->stockMovements);
        $this->assertInstanceOf(StockMovement::class, $supplier->stockMovements->first());
    }

    /** @test */
    public function it_can_update_supplier_information()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor Original',
            'email' => 'original@test.com',
        ]);

        $supplier->update([
            'name' => 'Proveedor Actualizado',
            'email' => 'nuevo@test.com',
            'phone' => '555-9999',
        ]);

        $this->assertEquals('Proveedor Actualizado', $supplier->name);
        $this->assertEquals('nuevo@test.com', $supplier->email);
        $this->assertEquals('555-9999', $supplier->phone);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Proveedor Actualizado',
            'email' => 'nuevo@test.com',
        ]);
    }

    /** @test */
    public function it_can_delete_supplier()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor a Eliminar',
        ]);

        $supplierId = $supplier->id;

        $supplier->delete();

        $this->assertDatabaseMissing('suppliers', [
            'id' => $supplierId,
        ]);
    }

    /** @test */
    public function it_nullifies_supplier_id_on_stock_movements_when_deleted()
    {
        $supplier = Supplier::create(['name' => 'Proveedor Test']);

        $product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'PROD-001',
            'price' => 100,
            'stock' => 10,
        ]);

        $service = app(StockMovementService::class);
        $movement = $service->registerEntry($product, 10, 'Compra', $this->user, $supplier->id);

        $this->assertEquals($supplier->id, $movement->supplier_id);

        // Eliminar proveedor
        $supplier->delete();

        // Verificar que el movimiento ahora tiene supplier_id en null
        $movement->refresh();
        $this->assertNull($movement->supplier_id);
    }

    /** @test */
    public function it_logs_activity_when_created()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor con Log',
            'email' => 'log@test.com',
        ]);

        // Verificar que existe actividad registrada
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Supplier::class,
            'subject_id' => $supplier->id,
            'event' => 'created',
        ]);
    }

    /** @test */
    public function it_logs_activity_when_updated()
    {
        $supplier = Supplier::create([
            'name' => 'Original',
        ]);

        $supplier->update([
            'name' => 'Actualizado',
            'email' => 'nuevo@test.com',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Supplier::class,
            'subject_id' => $supplier->id,
            'event' => 'updated',
        ]);
    }

    /** @test */
    public function it_can_search_suppliers_by_name()
    {
        Supplier::create(['name' => 'Ferretería ABC']);
        Supplier::create(['name' => 'Tornillos XYZ']);
        Supplier::create(['name' => 'Materiales ABC']);

        $results = Supplier::where('name', 'like', '%ABC%')->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_search_suppliers_by_email()
    {
        Supplier::create(['name' => 'Proveedor 1', 'email' => 'contact@provider1.com']);
        Supplier::create(['name' => 'Proveedor 2', 'email' => 'info@provider2.com']);

        $result = Supplier::where('email', 'contact@provider1.com')->first();

        $this->assertEquals('Proveedor 1', $result->name);
    }

    /** @test */
    public function it_stores_cuit_correctly()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor CUIT',
            'cuit' => '20-12345678-9',
        ]);

        $this->assertEquals('20-12345678-9', $supplier->cuit);
    }

    /** @test */
    public function it_stores_multiline_address()
    {
        $address = "Calle Principal 123\nPiso 4, Depto B\nCiudad, Provincia";

        $supplier = Supplier::create([
            'name' => 'Proveedor con Dirección',
            'address' => $address,
        ]);

        $this->assertEquals($address, $supplier->address);
    }

    /** @test */
    public function it_stores_notes_correctly()
    {
        $notes = "Proveedor preferido para compras grandes.\nDescuento del 10% en pedidos superiores a $10,000.";

        $supplier = Supplier::create([
            'name' => 'Proveedor con Notas',
            'notes' => $notes,
        ]);

        $this->assertEquals($notes, $supplier->notes);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor con Timestamps',
        ]);

        $this->assertNotNull($supplier->created_at);
        $this->assertNotNull($supplier->updated_at);
    }

    /** @test */
    public function multiple_suppliers_can_have_same_contact_name()
    {
        Supplier::create([
            'name' => 'Empresa A',
            'contact_name' => 'Juan Pérez',
        ]);

        Supplier::create([
            'name' => 'Empresa B',
            'contact_name' => 'Juan Pérez',
        ]);

        $suppliers = Supplier::where('contact_name', 'Juan Pérez')->get();

        $this->assertCount(2, $suppliers);
    }

    /** @test */
    public function it_can_get_all_suppliers_ordered_by_name()
    {
        Supplier::create(['name' => 'Zebra Supplies']);
        Supplier::create(['name' => 'Alpha Materials']);
        Supplier::create(['name' => 'Beta Tools']);

        $suppliers = Supplier::orderBy('name')->get();

        $this->assertEquals('Alpha Materials', $suppliers[0]->name);
        $this->assertEquals('Beta Tools', $suppliers[1]->name);
        $this->assertEquals('Zebra Supplies', $suppliers[2]->name);
    }
}
