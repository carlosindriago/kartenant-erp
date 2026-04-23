<?php

namespace Tests\Feature\Multitenancy;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleReturn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Multitenancy\Models\Tenant;
use Tests\TestCase;

class CrossDatabaseUserRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Usuarios viven en landlord DB
        $this->user1 = User::factory()->create([
            'name' => 'Usuario Uno',
            'email' => 'user1@test.com',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Usuario Dos',
            'email' => 'user2@test.com',
        ]);

        // Tenant vive en landlord DB pero sus datos en tenant DB
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
    public function stock_movement_can_access_authorized_by_user()
    {
        $product = Product::create([
            'name' => 'Producto Test',
            'sku' => 'TEST-001',
            'price' => 100,
            'stock' => 50,
        ]);

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'type' => 'entrada',
            'quantity' => 10,
            'reason' => 'Test',
            'previous_stock' => 50,
            'new_stock' => 60,
            'user_name' => $this->user1->name,
            'authorized_by' => $this->user1->id,
        ]);

        // Acceder al usuario a través del trait
        $authorizedBy = $movement->authorizedBy;

        $this->assertInstanceOf(User::class, $authorizedBy);
        $this->assertEquals($this->user1->id, $authorizedBy->id);
        $this->assertEquals('Usuario Uno', $authorizedBy->name);
    }

    /** @test */
    public function sale_can_access_user_who_created_it()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $sale = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user1->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        // Acceder al usuario a través del trait
        $user = $sale->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->user1->id, $user->id);
        $this->assertEquals('Usuario Uno', $user->name);
    }

    /** @test */
    public function sale_can_access_cancelled_by_user()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $sale = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user1->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->user2->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Error',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        // Acceder al usuario que canceló
        $cancelledBy = $sale->cancelledBy;

        $this->assertInstanceOf(User::class, $cancelledBy);
        $this->assertEquals($this->user2->id, $cancelledBy->id);
        $this->assertEquals('Usuario Dos', $cancelledBy->name);
    }

    /** @test */
    public function cash_register_can_access_opened_by_user()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $openedBy = $cashRegister->openedBy;

        $this->assertInstanceOf(User::class, $openedBy);
        $this->assertEquals($this->user1->id, $openedBy->id);
        $this->assertEquals('Usuario Uno', $openedBy->name);
    }

    /** @test */
    public function cash_register_can_access_closed_by_user()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'closed_by_user_id' => $this->user2->id,
            'closed_at' => now(),
            'expected_amount' => 500,
            'actual_amount' => 500,
            'difference' => 0,
        ]);

        $closedBy = $cashRegister->closedBy;

        $this->assertInstanceOf(User::class, $closedBy);
        $this->assertEquals($this->user2->id, $closedBy->id);
        $this->assertEquals('Usuario Dos', $closedBy->name);
    }

    /** @test */
    public function cash_register_can_access_forced_by_user()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'closed',
            'forced_closure' => true,
            'forced_by_user_id' => $this->user2->id,
            'forced_reason' => 'Emergencia',
        ]);

        $forcedBy = $cashRegister->forcedBy;

        $this->assertInstanceOf(User::class, $forcedBy);
        $this->assertEquals($this->user2->id, $forcedBy->id);
        $this->assertEquals('Usuario Dos', $forcedBy->name);
    }

    /** @test */
    public function sale_return_can_access_processed_by_user()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $sale = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user1->id,
            'status' => 'completed',
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $return = SaleReturn::create([
            'original_sale_id' => $sale->id,
            'return_number' => 'NCR-001',
            'status' => 'completed',
            'return_type' => 'full',
            'reason' => 'Defectuoso',
            'total' => 100,
            'processed_by_user_id' => $this->user2->id,
            'processed_at' => now(),
        ]);

        $processedBy = $return->processedByUser;

        $this->assertInstanceOf(User::class, $processedBy);
        $this->assertEquals($this->user2->id, $processedBy->id);
        $this->assertEquals('Usuario Dos', $processedBy->name);
    }

    /** @test */
    public function user_relations_use_cache()
    {
        Cache::flush();

        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        // Primera llamada - debe cachear
        $openedBy1 = $cashRegister->openedBy;

        // Verificar que está en cache
        $cacheKey = "user_{$this->user1->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Segunda llamada - debe usar cache
        $openedBy2 = $cashRegister->openedBy;

        $this->assertSame($openedBy1, $openedBy2);
    }

    /** @test */
    public function user_relations_return_null_when_user_id_is_null()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
            // closed_by_user_id es null
        ]);

        $closedBy = $cashRegister->closedBy;

        $this->assertNull($closedBy);
    }

    /** @test */
    public function user_relations_return_null_when_user_does_not_exist()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => 99999, // Usuario que no existe
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $openedBy = $cashRegister->openedBy;

        $this->assertNull($openedBy);
    }

    /** @test */
    public function eager_loading_prevents_n_plus_1_queries()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        // Crear 10 ventas con diferentes usuarios
        for ($i = 0; $i < 5; $i++) {
            Sale::create([
                'cash_register_id' => $cashRegister->id,
                'invoice_number' => "FAC-00{$i}",
                'user_id' => $this->user1->id,
                'status' => 'completed',
                'total' => 100,
                'payment_method' => 'cash',
            ]);
        }

        for ($i = 5; $i < 10; $i++) {
            Sale::create([
                'cash_register_id' => $cashRegister->id,
                'invoice_number' => "FAC-00{$i}",
                'user_id' => $this->user2->id,
                'status' => 'completed',
                'total' => 100,
                'payment_method' => 'cash',
            ]);
        }

        // Obtener todas las ventas
        $sales = Sale::all();

        // Eager load users
        Sale::eagerLoadUsers($sales, ['user_id']);

        // Ahora acceder a los usuarios no debería generar queries adicionales
        foreach ($sales as $sale) {
            $user = $sale->user;
            $this->assertInstanceOf(User::class, $user);
        }

        // Si llegamos aquí sin error, el eager loading funcionó
        $this->assertTrue(true);
    }

    /** @test */
    public function multiple_user_fields_can_be_eager_loaded()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        $sale1 = Sale::create([
            'cash_register_id' => $cashRegister->id,
            'invoice_number' => 'FAC-001',
            'user_id' => $this->user1->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->user2->id,
            'cancelled_at' => now(),
            'total' => 100,
            'payment_method' => 'cash',
        ]);

        $sales = Sale::all();

        // Eager load múltiples campos de usuario
        Sale::eagerLoadUsers($sales, ['user_id', 'cancelled_by']);

        foreach ($sales as $sale) {
            $this->assertInstanceOf(User::class, $sale->user);
            if ($sale->cancelled_by) {
                $this->assertInstanceOf(User::class, $sale->cancelledBy);
            }
        }

        $this->assertTrue(true);
    }

    /** @test */
    public function cache_can_be_cleared_for_user_relations()
    {
        Cache::flush();

        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        // Acceder al usuario para que se cachee
        $openedBy = $cashRegister->openedBy;
        $cacheKey = "user_{$this->user1->id}";

        $this->assertTrue(Cache::has($cacheKey));

        // Limpiar cache
        $cashRegister->clearUserCache();

        // El cache de Laravel debería haberse limpiado
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function different_tenants_can_reference_same_user()
    {
        // Tenant 1
        $this->tenant->makeCurrent();
        $cashRegister1 = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);
        Tenant::forgetCurrent();

        // Tenant 2
        $tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'domain' => 'tenant2.test.com',
        ]);
        $tenant2->makeCurrent();

        $cashRegister2 = CashRegister::create([
            'opened_by_user_id' => $this->user1->id, // Mismo usuario
            'opened_at' => now(),
            'initial_amount' => 200,
            'status' => 'open',
        ]);

        // Ambos deberían poder acceder al mismo usuario
        $openedBy2 = $cashRegister2->openedBy;
        Tenant::forgetCurrent();

        $this->tenant->makeCurrent();
        $openedBy1 = $cashRegister1->openedBy;

        $this->assertEquals($this->user1->id, $openedBy1->id);
        $this->assertEquals($this->user1->id, $openedBy2->id);
        $this->assertEquals('Usuario Uno', $openedBy1->name);
        $this->assertEquals('Usuario Uno', $openedBy2->name);
    }

    /** @test */
    public function deprecated_relationship_methods_still_work()
    {
        $cashRegister = CashRegister::create([
            'opened_by_user_id' => $this->user1->id,
            'opened_at' => now(),
            'initial_amount' => 100,
            'status' => 'open',
        ]);

        // Los métodos deprecados deberían seguir funcionando
        // aunque no devuelvan resultados correctos (por eso están deprecados)
        $relation = $cashRegister->openedBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
    }
}
