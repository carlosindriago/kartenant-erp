<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Customer;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected CashRegister $cashRegister;

    protected Customer $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Vendedor Test',
            'email' => 'vendedor@test.com',
        ]);

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $this->tenant->makeCurrent();

        // Crear caja registradora abierta
        $this->cashRegister = CashRegister::create([
            'register_number' => 'REG-TEST-001',
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100.00,
            'status' => 'open',
        ]);

        // Crear cliente
        $this->customer = Customer::create([
            'name' => 'Cliente Test',
            'email' => 'cliente@test.com',
        ]);

        // Crear producto
        $this->product = Product::create([
            'name' => 'Producto Venta',
            'sku' => 'VENTA-001',
            'price' => 50.00,
            'stock' => 100,
        ]);
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_sale()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => 'FAC-20251018-0001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100.00,
            'tax_amount' => 21.00,
            'discount_amount' => 0,
            'total' => 121.00,
            'payment_method' => 'cash',
            'amount_paid' => 150.00,
            'change_amount' => 29.00,
        ]);

        $this->assertDatabaseHas('sales', [
            'invoice_number' => 'FAC-20251018-0001',
            'total' => 121.00,
            'status' => 'completed',
        ]);

        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(121.00, $sale->total);
    }

    /** @test */
    public function it_generates_unique_invoice_numbers()
    {
        $invoiceNumber1 = Sale::generateInvoiceNumber();

        Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => $invoiceNumber1,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $invoiceNumber2 = Sale::generateInvoiceNumber();

        $this->assertNotEquals($invoiceNumber1, $invoiceNumber2);
        $this->assertStringStartsWith('FAC-', $invoiceNumber1);
        $this->assertStringStartsWith('FAC-', $invoiceNumber2);
    }

    /** @test */
    public function it_belongs_to_cash_register()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $this->assertInstanceOf(CashRegister::class, $sale->cashRegister);
        $this->assertEquals($this->cashRegister->id, $sale->cashRegister->id);
    }

    /** @test */
    public function it_belongs_to_customer()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $this->assertInstanceOf(Customer::class, $sale->customer);
        $this->assertEquals($this->customer->id, $sale->customer->id);
    }

    /** @test */
    public function it_can_access_user_via_trait()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        // Usar trait HasCrossDatabaseUserRelations
        $user = $sale->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->user->id, $user->id);
        $this->assertEquals($this->user->name, $user->name);
    }

    /** @test */
    public function it_has_sale_items()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
            'subtotal' => 100.00,
        ]);

        $this->assertCount(1, $sale->items);
        $this->assertInstanceOf(SaleItem::class, $sale->items->first());
    }

    /** @test */
    public function it_can_be_completed()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $this->assertTrue($sale->isCompleted());
        $this->assertFalse($sale->isCancelled());
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $sale->update([
            'status' => 'cancelled',
            'cancelled_by' => $this->user->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Error en el pedido',
        ]);

        $this->assertTrue($sale->isCancelled());
        $this->assertFalse($sale->isCompleted());
        $this->assertEquals('Error en el pedido', $sale->cancellation_reason);
    }

    /** @test */
    public function it_can_access_cancelled_by_user_via_trait()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->user->id,
            'cancelled_at' => now(),
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $cancelledBy = $sale->cancelledBy;

        $this->assertInstanceOf(User::class, $cancelledBy);
        $this->assertEquals($this->user->id, $cancelledBy->id);
    }

    /** @test */
    public function it_supports_cash_payment_method()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
            'amount_paid' => 100,
            'change_amount' => 0,
        ]);

        $this->assertEquals('cash', $sale->payment_method);
    }

    /** @test */
    public function it_supports_card_payment_method()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'card',
            'transaction_reference' => 'TXN-12345',
        ]);

        $this->assertEquals('card', $sale->payment_method);
        $this->assertEquals('TXN-12345', $sale->transaction_reference);
    }

    /** @test */
    public function it_supports_transfer_payment_method()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'transfer',
            'transaction_reference' => 'TRANSFER-98765',
        ]);

        $this->assertEquals('transfer', $sale->payment_method);
    }

    /** @test */
    public function it_calculates_change_correctly()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
            'amount_paid' => 150,
            'change_amount' => 50,
        ]);

        $this->assertEquals(50, $sale->change_amount);
    }

    /** @test */
    public function it_can_have_discount()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'discount_amount' => 10,
            'tax_amount' => 18.90, // (100 - 10) * 0.21
            'total' => 108.90,
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(10, $sale->discount_amount);
        $this->assertEquals(108.90, $sale->total);
    }

    /** @test */
    public function it_can_have_notes()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
            'notes' => 'Cliente requiere factura A',
        ]);

        $this->assertEquals('Cliente requiere factura A', $sale->notes);
    }

    /** @test */
    public function it_logs_activity_on_creation()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Sale::class,
            'subject_id' => $sale->id,
            'event' => 'created',
        ]);
    }

    /** @test */
    public function it_can_check_if_sale_can_be_cancelled()
    {
        // Venta reciente (puede cancelarse)
        $recentSale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $this->assertTrue($recentSale->canBeCancelled());

        // Venta antigua (no puede cancelarse)
        $oldSale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);
        $oldSale->created_at = now()->subDays(10);
        $oldSale->save();

        $this->assertFalse($oldSale->canBeCancelled());
    }

    /** @test */
    public function it_has_returns_relationship()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        // No tiene devoluciones inicialmente
        $this->assertFalse($sale->hasReturns());
        $this->assertEquals(0, $sale->totalReturned);
    }

    /** @test */
    public function it_can_generate_verification_content()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => 'FAC-TEST-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 121,
            'payment_method' => 'cash',
        ]);

        $verificationContent = $sale->getVerificationContent();

        $this->assertEquals('sale', $verificationContent['type']);
        $this->assertEquals('FAC-TEST-001', $verificationContent['invoice_number']);
        $this->assertEquals('121', $verificationContent['total']);
        $this->assertEquals('cash', $verificationContent['payment_method']);
    }

    /** @test */
    public function it_returns_correct_verification_document_type()
    {
        $sale = Sale::create([
            'cash_register_id' => $this->cashRegister->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $this->assertEquals('sale_receipt', $sale->getVerificationDocumentType());
    }
}
