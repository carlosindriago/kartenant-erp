<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Customer;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleItem;
use App\Modules\POS\Models\SaleReturn;
use App\Modules\POS\Models\SaleReturnItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class SaleReturnTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Sale $sale;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Usuario Test',
            'email' => 'user@test.com',
        ]);

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenant->makeCurrent();

        $this->product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'PROD-001',
            'price' => 100,
            'stock' => 50,
        ]);

        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $this->sale = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 200,
            'tax_amount' => 42,
            'total' => 242,
            'payment_method' => 'cash',
        ]);

        SaleItem::create([
            'sale_id' => $this->sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_sale_return()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Producto defectuoso',
            'subtotal' => 200,
            'tax_amount' => 42,
            'total' => 242,
            'refund_method' => 'cash',
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertDatabaseHas('sale_returns', [
            'original_sale_id' => $this->sale->id,
            'status' => 'completed',
            'total' => 242,
        ]);

        $this->assertEquals('full', $return->return_type);
        $this->assertEquals('Producto defectuoso', $return->reason);
    }

    /** @test */
    public function it_generates_unique_return_numbers()
    {
        $returnNumber1 = SaleReturn::generateReturnNumber();

        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => $returnNumber1,
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test 1',
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $returnNumber2 = SaleReturn::generateReturnNumber();

        $this->assertNotEquals($returnNumber1, $returnNumber2);
        $this->assertStringStartsWith('NCR-', $returnNumber1);
        $this->assertStringStartsWith('NCR-', $returnNumber2);
    }

    /** @test */
    public function it_belongs_to_original_sale()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertInstanceOf(Sale::class, $return->originalSale);
        $this->assertEquals($this->sale->id, $return->originalSale->id);
    }

    /** @test */
    public function it_can_access_processed_by_user_via_trait()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $processedBy = $return->processedByUser;

        $this->assertInstanceOf(User::class, $processedBy);
        $this->assertEquals($this->user->id, $processedBy->id);
        $this->assertEquals($this->user->name, $processedBy->name);
    }

    /** @test */
    public function it_has_return_items_relationship()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Devolución parcial',
            'subtotal' => 100,
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);

        $this->assertCount(1, $return->items);
        $this->assertInstanceOf(SaleReturnItem::class, $return->items->first());
    }

    /** @test */
    public function it_supports_full_return_type()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Cliente insatisfecho',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertEquals('full', $return->return_type);
    }

    /** @test */
    public function it_supports_partial_return_type()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Un item defectuoso',
            'total' => 121,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertEquals('partial', $return->return_type);
    }

    /** @test */
    public function it_supports_cash_refund_method()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test',
            'total' => 242,
            'refund_method' => 'cash',
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertEquals('cash', $return->refund_method);
    }

    /** @test */
    public function it_supports_card_refund_method()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test',
            'total' => 242,
            'refund_method' => 'card',
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertEquals('card', $return->refund_method);
    }

    /** @test */
    public function it_can_filter_completed_returns()
    {
        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-001',
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test 1',
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-002',
            'status' => 'pending',
            'return_type' => 'full',
            'reason' => 'Test 2',
            'total' => 150,
            'processed_by_user_id' => $this->user->id,
        ]);

        $completed = SaleReturn::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status);
    }

    /** @test */
    public function it_can_filter_pending_returns()
    {
        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-001',
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test 1',
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-002',
            'status' => 'pending',
            'return_type' => 'full',
            'reason' => 'Test 2',
            'total' => 150,
            'processed_by_user_id' => $this->user->id,
        ]);

        $pending = SaleReturn::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    /** @test */
    public function it_stores_return_reason()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Producto llegó defectuoso. Cliente solicita reembolso completo.',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertEquals(
            'Producto llegó defectuoso. Cliente solicita reembolso completo.',
            $return->reason
        );
    }

    /** @test */
    public function it_can_generate_verification_content()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-TEST-001',
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Producto defectuoso',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);

        $verificationContent = $return->getVerificationContent();

        $this->assertEquals('sale_return', $verificationContent['type']);
        $this->assertEquals('NCR-TEST-001', $verificationContent['return_number']);
        $this->assertEquals($this->sale->id, $verificationContent['original_sale_id']);
        $this->assertEquals('242', $verificationContent['total']);
        $this->assertEquals('full', $verificationContent['return_type']);
        $this->assertEquals('Producto defectuoso', $verificationContent['reason']);
        $this->assertEquals(1, $verificationContent['items_count']);
    }

    /** @test */
    public function it_returns_correct_verification_document_type()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertEquals('sale_return_receipt', $return->getVerificationDocumentType());
    }

    /** @test */
    public function sale_can_have_multiple_returns()
    {
        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-001',
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Devolución 1',
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-002',
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Devolución 2',
            'total' => 50,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertCount(2, $this->sale->returns);
        $this->assertTrue($this->sale->hasReturns());
    }

    /** @test */
    public function it_calculates_total_returned_amount()
    {
        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-001',
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Devolución 1',
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-002',
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Devolución 2',
            'total' => 50,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        // Refrescar sale para cargar relaciones
        $this->sale->refresh();

        $this->assertEquals(150, $this->sale->totalReturned);
    }

    /** @test */
    public function pending_returns_are_not_counted_in_total_returned()
    {
        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-001',
            'status' => 'completed',
            'return_type' => 'partial',
            'reason' => 'Completado',
            'total' => 100,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => 'NCR-002',
            'status' => 'pending',
            'return_type' => 'partial',
            'reason' => 'Pendiente',
            'total' => 50,
            'processed_by_user_id' => $this->user->id,
        ]);

        $this->sale->refresh();

        // Solo debe contar la completada
        $this->assertEquals(100, $this->sale->totalReturned);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $return = SaleReturn::create([
            'original_sale_id' => $this->sale->id,
            'return_number' => SaleReturn::generateReturnNumber(),
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Test',
            'total' => 242,
            'processed_by_user_id' => $this->user->id,
            'processed_at' => now(),
        ]);

        $this->assertNotNull($return->created_at);
        $this->assertNotNull($return->updated_at);
        $this->assertNotNull($return->processed_at);
    }
}
