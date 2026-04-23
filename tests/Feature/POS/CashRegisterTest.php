<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class CashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Cajero Test',
            'email' => 'cajero@test.com',
        ]);

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
    public function it_can_open_a_cash_register()
    {
        $cashRegister = CashRegister::create([
            'register_number' => 'REG-20251018-0001',
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100.00,
            'status' => 'open',
            'opening_notes' => 'Apertura de turno matutino',
        ]);

        $this->assertDatabaseHas('cash_registers', [
            'register_number' => 'REG-20251018-0001',
            'status' => 'open',
            'initial_amount' => 100.00,
        ]);

        $this->assertTrue($cashRegister->isOpen());
        $this->assertFalse($cashRegister->isClosed());
    }

    /** @test */
    public function it_generates_unique_register_numbers()
    {
        $register1 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $register2 = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 150,
            'status' => 'open',
        ]);

        $this->assertNotEquals($register1->register_number, $register2->register_number);
        $this->assertStringStartsWith('REG-', $register1->register_number);
        $this->assertStringStartsWith('REG-', $register2->register_number);
    }

    /** @test */
    public function it_can_access_opened_by_user_via_trait()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $openedBy = $cashRegister->openedBy;

        $this->assertInstanceOf(User::class, $openedBy);
        $this->assertEquals($this->user->id, $openedBy->id);
        $this->assertEquals($this->user->name, $openedBy->name);
    }

    /** @test */
    public function it_can_close_a_cash_register()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $cashRegister->update([
            'status' => 'closed',
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
            'expected_amount' => 500,
            'actual_amount' => 500,
            'difference' => 0,
            'closing_notes' => 'Cierre normal sin diferencias',
        ]);

        $this->assertTrue($cashRegister->isClosed());
        $this->assertFalse($cashRegister->isOpen());
        $this->assertEquals(0, $cashRegister->difference);
    }

    /** @test */
    public function it_can_access_closed_by_user_via_trait()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
            'expected_amount' => 500,
            'actual_amount' => 500,
            'difference' => 0,
        ]);

        $closedBy = $cashRegister->closedBy;

        $this->assertInstanceOf(User::class, $closedBy);
        $this->assertEquals($this->user->id, $closedBy->id);
    }

    /** @test */
    public function it_calculates_expected_amount_correctly()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100.00,
            'status' => 'open',
        ]);

        $product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'TEST-001',
            'price' => 50,
            'stock' => 100,
        ]);

        // Crear ventas en efectivo
        $sale1 = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $sale2 = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-002',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 200,
            'total' => 200,
            'payment_method' => 'cash',
        ]);

        // Venta con tarjeta (no debe contarse)
        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-003',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 150,
            'total' => 150,
            'payment_method' => 'card',
        ]);

        $expectedAmount = $cashRegister->calculateExpectedAmount();

        // 100 (inicial) + 100 (venta1) + 200 (venta2) = 400
        $this->assertEquals(400, $expectedAmount);
    }

    /** @test */
    public function it_excludes_cancelled_sales_from_expected_amount()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        // Venta completada
        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        // Venta cancelada (no debe contarse)
        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-002',
            'user_id' => $this->user->id,
            'status' => 'cancelled',
            'subtotal' => 200,
            'total' => 200,
            'payment_method' => 'cash',
            'cancelled_by' => $this->user->id,
            'cancelled_at' => now(),
        ]);

        $expectedAmount = $cashRegister->calculateExpectedAmount();

        // Solo debe contar la venta completada: 100 + 100 = 200
        $this->assertEquals(200, $expectedAmount);
    }

    /** @test */
    public function it_gets_sales_summary_correctly()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        // 2 ventas en efectivo
        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-002',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 150,
            'payment_method' => 'cash',
        ]);

        // 1 venta con tarjeta
        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-003',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 200,
            'payment_method' => 'card',
        ]);

        // 1 venta cancelada
        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-004',
            'user_id' => $this->user->id,
            'status' => 'cancelled',
            'total' => 50,
            'payment_method' => 'cash',
            'cancelled_by' => $this->user->id,
            'cancelled_at' => now(),
        ]);

        $summary = $cashRegister->getSalesSummary();

        $this->assertEquals(3, $summary['total_sales']); // Solo completadas
        $this->assertEquals(450, $summary['total_amount']); // 100 + 150 + 200
        $this->assertEquals(250, $summary['cash_sales']); // 100 + 150
        $this->assertEquals(200, $summary['card_sales']);
        $this->assertEquals(1, $summary['cancelled_sales']);
        $this->assertEquals(50, $summary['cash_returns']);
    }

    /** @test */
    public function it_has_sales_relationship()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-002',
            'user_id' => $this->user->id,
            'status' => 'completed',
            'total' => 200,
            'payment_method' => 'cash',
        ]);

        $this->assertCount(2, $cashRegister->sales);
        $this->assertInstanceOf(Sale::class, $cashRegister->sales->first());
    }

    /** @test */
    public function it_can_filter_open_registers()
    {
        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 150,
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_user_id' => $this->user->id,
        ]);

        $openRegisters = CashRegister::open()->get();

        $this->assertCount(1, $openRegisters);
        $this->assertEquals('open', $openRegisters->first()->status);
    }

    /** @test */
    public function it_can_filter_closed_registers()
    {
        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 150,
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_user_id' => $this->user->id,
        ]);

        $closedRegisters = CashRegister::closed()->get();

        $this->assertCount(1, $closedRegisters);
        $this->assertEquals('closed', $closedRegisters->first()->status);
    }

    /** @test */
    public function it_can_get_current_open_register()
    {
        $openRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $current = CashRegister::getCurrentOpen();

        $this->assertNotNull($current);
        $this->assertEquals($openRegister->id, $current->id);
    }

    /** @test */
    public function it_returns_null_when_no_register_is_open()
    {
        $current = CashRegister::getCurrentOpen();

        $this->assertNull($current);
    }

    /** @test */
    public function it_can_check_if_user_has_open_register()
    {
        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $hasOpen = CashRegister::userHasOpenRegister($this->user->id);

        $this->assertTrue($hasOpen);
    }

    /** @test */
    public function it_can_get_user_open_register()
    {
        $register = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $userRegister = CashRegister::getUserOpenRegister($this->user->id);

        $this->assertNotNull($userRegister);
        $this->assertEquals($register->id, $userRegister->id);
    }

    /** @test */
    public function it_detects_discrepancy()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
            'expected_amount' => 500,
            'actual_amount' => 485,
            'difference' => -15,
        ]);

        $this->assertTrue($cashRegister->hasDiscrepancy());
    }

    /** @test */
    public function it_detects_no_discrepancy()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
            'expected_amount' => 500,
            'actual_amount' => 500,
            'difference' => 0,
        ]);

        $this->assertFalse($cashRegister->hasDiscrepancy());
    }

    /** @test */
    public function it_can_store_cash_breakdown()
    {
        $breakdown = [
            'bills_100' => 5,
            'bills_50' => 10,
            'bills_20' => 20,
            'coins_10' => 15,
            'coins_5' => 10,
        ];

        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
            'cash_breakdown' => $breakdown,
        ]);

        $this->assertEquals($breakdown, $cashRegister->cash_breakdown);
        $this->assertEquals(5, $cashRegister->cash_breakdown['bills_100']);
    }

    /** @test */
    public function it_can_handle_forced_closure()
    {
        $manager = User::factory()->create(['name' => 'Manager']);

        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'forced_closure' => true,
            'forced_by_user_id' => $manager->id,
            'forced_reason' => 'Cierre de emergencia por mantenimiento',
        ]);

        $this->assertTrue($cashRegister->forced_closure);
        $this->assertEquals('Cierre de emergencia por mantenimiento', $cashRegister->forced_reason);

        $forcedBy = $cashRegister->forcedBy;
        $this->assertInstanceOf(User::class, $forcedBy);
        $this->assertEquals($manager->id, $forcedBy->id);
    }

    /** @test */
    public function it_returns_correct_document_name_for_open_register()
    {
        $cashRegister = CashRegister::create([
            'register_number' => 'REG-TEST-001',
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $this->assertEquals('Apertura de Caja REG-TEST-001', $cashRegister->getDocumentName());
    }

    /** @test */
    public function it_returns_correct_document_name_for_closed_register()
    {
        $cashRegister = CashRegister::create([
            'register_number' => 'REG-TEST-001',
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
        ]);

        $this->assertEquals('Cierre de Caja REG-TEST-001', $cashRegister->getDocumentName());
    }

    /** @test */
    public function it_can_count_open_registers()
    {
        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 150,
            'status' => 'open',
        ]);

        $count = CashRegister::countOpenRegisters();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_checks_if_register_belongs_to_user()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $this->assertTrue($cashRegister->belongsToUser($this->user->id));

        $otherUser = User::factory()->create();
        $this->assertFalse($cashRegister->belongsToUser($otherUser->id));
    }
}
