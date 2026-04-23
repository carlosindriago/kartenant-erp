<?php

namespace Tests\Unit\Models;

use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up landlord database connection for testing
        Config::set('database.default', 'landlord');

        // Create fake storage for file testing
        Storage::fake('local');
    }

    /**
     * =============================================
     * BASIC MODEL FUNCTIONALITY TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_can_be_instantiated()
    {
        $paymentProof = new PaymentProof;

        $this->assertInstanceOf(PaymentProof::class, $paymentProof);
        $this->assertEquals('landlord', $paymentProof->getConnectionName());
        $this->assertEquals('payment_proofs', $paymentProof->getTable());
    }

    /** @test */
    public function payment_proof_has_correct_fillable_attributes()
    {
        $paymentProof = new PaymentProof;
        $fillable = $paymentProof->getFillable();

        $expectedFillable = [
            'tenant_id',
            'subscription_id',
            'payment_transaction_id',
            'payment_method',
            'amount',
            'currency',
            'payment_date',
            'reference_number',
            'payer_name',
            'notes',
            'file_paths',
            'file_type',
            'total_file_size_mb',
            'status',
            'rejection_reason',
            'reviewed_by',
            'reviewed_at',
            'review_notes',
            'metadata',
            'ip_address',
            'user_agent',
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    /** @test */
    public function payment_proof_has_correct_casts()
    {
        $paymentProof = new PaymentProof;
        $casts = $paymentProof->getCasts();

        $this->assertArrayHasKey('amount', $casts);
        $this->assertArrayHasKey('payment_date', $casts);
        $this->assertArrayHasKey('file_paths', $casts);
        $this->assertArrayHasKey('total_file_size_mb', $casts);
        $this->assertArrayHasKey('reviewed_at', $casts);
        $this->assertArrayHasKey('metadata', $casts);

        $this->assertEquals('decimal:2', $casts['amount']);
        $this->assertEquals('date', $casts['payment_date']);
        $this->assertEquals('array', $casts['file_paths']);
        $this->assertEquals('decimal:2', $casts['total_file_size_mb']);
        $this->assertEquals('datetime', $casts['reviewed_at']);
        $this->assertEquals('array', $casts['metadata']);
    }

    /** @test */
    public function payment_proof_uses_landlord_connection()
    {
        $paymentProof = new PaymentProof;

        $this->assertEquals('landlord', $paymentProof->getConnectionName());
    }

    /** @test */
    public function payment_proof_uses_soft_deletes()
    {
        $paymentProof = PaymentProof::factory()->create();

        $this->assertNotNull($paymentProof->deleted_at);
        $this->assertFalse($paymentProof->trashed());

        $paymentProof->delete();

        $this->assertNotNull($paymentProof->deleted_at);
        $this->assertTrue($paymentProof->trashed());

        // Should not appear in regular queries
        $this->assertEquals(0, PaymentProof::count());

        // Should appear in withTrashed queries
        $this->assertEquals(1, PaymentProof::withTrashed()->count());
    }

    /**
     * =============================================
     * ATTRIBUTE VALIDATION TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_can_mass_assign_fillable_attributes()
    {
        $attributes = [
            'tenant_id' => Tenant::factory()->create()->id,
            'subscription_id' => TenantSubscription::factory()->create()->id,
            'payment_method' => PaymentProof::PAYMENT_METHOD_BANK_TRANSFER,
            'amount' => 100.50,
            'currency' => 'ARS',
            'payment_date' => '2024-01-15',
            'reference_number' => 'REF-123456',
            'payer_name' => 'John Doe',
            'notes' => 'Test payment proof',
            'file_paths' => ['test/file1.jpg', 'test/file2.pdf'],
            'file_type' => 'mixed',
            'total_file_size_mb' => 2.5,
            'status' => PaymentProof::STATUS_PENDING,
            'metadata' => ['key' => 'value'],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
        ];

        $paymentProof = PaymentProof::create($attributes);

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'tenant_id' => $attributes['tenant_id'],
            'amount' => $attributes['amount'],
            'status' => $attributes['status'],
        ]);
    }

    /** @test */
    public function payment_proof_validates_required_fields()
    {
        $this->expectException(QueryException::class);

        // Attempt to create without required fields
        PaymentProof::create([]);
    }

    /** @test */
    public function payment_proof_handles_file_uploads()
    {
        $filePaths = [
            'payment_proofs/'.uniqid().'.jpg',
            'payment_proofs/'.uniqid().'.pdf',
        ];

        $paymentProof = PaymentProof::factory()->create([
            'file_paths' => $filePaths,
            'file_type' => 'mixed',
            'total_file_size_mb' => 3.7,
        ]);

        $this->assertEquals($filePaths, $paymentProof->file_paths);
        $this->assertEquals('mixed', $paymentProof->file_type);
        $this->assertEquals(3.7, $paymentProof->total_file_size_mb);
    }

    /** @test */
    public function payment_proof_manages_status_transitions()
    {
        $user = User::factory()->create();
        $paymentProof = PaymentProof::factory()->pending()->create();

        // Test initial state
        $this->assertTrue($paymentProof->isPending());
        $this->assertFalse($paymentProof->isUnderReview());
        $this->assertFalse($paymentProof->isApproved());
        $this->assertFalse($paymentProof->isRejected());

        // Test start review
        $this->assertTrue($paymentProof->startReview($user));
        $paymentProof->refresh();

        $this->assertFalse($paymentProof->isPending());
        $this->assertTrue($paymentProof->isUnderReview());
        $this->assertFalse($paymentProof->isApproved());
        $this->assertFalse($paymentProof->isRejected());

        // Test approve
        $this->assertTrue($paymentProof->approve($user, 'Payment verified'));
        $paymentProof->refresh();

        $this->assertFalse($paymentProof->isPending());
        $this->assertFalse($paymentProof->isUnderReview());
        $this->assertTrue($paymentProof->isApproved());
        $this->assertFalse($paymentProof->isRejected());
        $this->assertEquals('Payment verified', $paymentProof->review_notes);

        // Test reject
        $paymentProof2 = PaymentProof::factory()->pending()->create();
        $this->assertTrue($paymentProof2->reject($user, 'Invalid amount'));
        $paymentProof2->refresh();

        $this->assertFalse($paymentProof2->isPending());
        $this->assertFalse($paymentProof2->isUnderReview());
        $this->assertFalse($paymentProof2->isApproved());
        $this->assertTrue($paymentProof2->isRejected());
        $this->assertEquals('Invalid amount', $paymentProof2->rejection_reason);
    }

    /**
     * =============================================
     * RELATIONSHIP TESTING
     * =============================================
     */

    /** @test */
    public function payment_proof_belongs_to_tenant()
    {
        $tenant = Tenant::factory()->create();
        $paymentProof = PaymentProof::factory()->forTenant($tenant)->create();

        $this->assertInstanceOf(Tenant::class, $paymentProof->tenant);
        $this->assertEquals($tenant->id, $paymentProof->tenant->id);
    }

    /** @test */
    public function payment_proof_belongs_to_subscription()
    {
        $subscription = TenantSubscription::factory()->create();
        $paymentProof = PaymentProof::factory()->forSubscription($subscription)->create();

        $this->assertInstanceOf(TenantSubscription::class, $paymentProof->subscription);
        $this->assertEquals($subscription->id, $paymentProof->subscription->id);
    }

    /** @test */
    public function payment_proof_belongs_to_payment_transaction()
    {
        $transaction = PaymentTransaction::factory()->create();
        $paymentProof = PaymentProof::factory()->withPaymentTransaction($transaction)->create();

        $this->assertInstanceOf(PaymentTransaction::class, $paymentProof->paymentTransaction);
        $this->assertEquals($transaction->id, $paymentProof->paymentTransaction->id);
    }

    /** @test */
    public function payment_proof_belongs_to_reviewer()
    {
        $reviewer = User::factory()->create();
        $paymentProof = PaymentProof::factory()->approved($reviewer)->create();

        $this->assertInstanceOf(User::class, $paymentProof->reviewer);
        $this->assertEquals($reviewer->id, $paymentProof->reviewer->id);
    }

    /** @test */
    public function payment_proof_handles_null_relationships()
    {
        $paymentProof = PaymentProof::factory()->create([
            'payment_transaction_id' => null,
            'reviewed_by' => null,
        ]);

        $this->assertNull($paymentProof->paymentTransaction);
        $this->assertNull($paymentProof->reviewer);
    }

    /**
     * =============================================
     * DATABASE INTEGRATION TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_can_be_saved_to_landlord_database()
    {
        $paymentProof = PaymentProof::factory()->create();

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'tenant_id' => $paymentProof->tenant_id,
            'amount' => $paymentProof->amount,
        ]);
    }

    /** @test */
    public function payment_proof_soft_delete_functionality()
    {
        $paymentProof = PaymentProof::factory()->create();

        // Normal count
        $this->assertEquals(1, PaymentProof::count());

        // Soft delete
        $paymentProof->delete();

        // Should not appear in normal count
        $this->assertEquals(0, PaymentProof::count());

        // Should appear withTrashed
        $this->assertEquals(1, PaymentProof::withTrashed()->count());

        // Can restore
        $paymentProof->restore();
        $this->assertEquals(1, PaymentProof::count());

        // Force delete
        $paymentProof->forceDelete();
        $this->assertEquals(0, PaymentProof::withTrashed()->count());
    }

    /** @test */
    public function payment_proof_scopes_work_correctly()
    {
        $pending1 = PaymentProof::factory()->pending()->create();
        $pending2 = PaymentProof::factory()->pending()->create();
        $underReview = PaymentProof::factory()->underReview()->create();
        $approved = PaymentProof::factory()->approved()->create();
        $rejected = PaymentProof::factory()->rejected()->create();

        // Test status scopes
        $this->assertEquals(2, PaymentProof::pending()->count());
        $this->assertEquals(1, PaymentProof::underReview()->count());
        $this->assertEquals(1, PaymentProof::approved()->count());
        $this->assertEquals(1, PaymentProof::rejected()->count());

        // Test tenant scope
        $tenant = $pending1->tenant;
        $this->assertEquals(1, PaymentProof::forTenant($tenant->id)->count());

        // Test payment method scope
        $this->assertEquals(1, PaymentProof::forMethod($pending1->payment_method)->count());
    }

    /** @test */
    public function payment_proof_query_builder_integration()
    {
        PaymentProof::factory()->count(5)->create(['amount' => 100.00]);
        PaymentProof::factory()->count(3)->create(['amount' => 200.00]);

        // Test complex queries
        $highValue = PaymentProof::where('amount', '>', 150)->get();
        $this->assertEquals(3, $highValue->count());

        $avgAmount = PaymentProof::avg('amount');
        $this->assertEquals(125.00, $avgAmount);

        // Test ordering
        $latest = PaymentProof::latest()->first();
        $this->assertInstanceOf(PaymentProof::class, $latest);
    }

    /**
     * =============================================
     * ATTRIBUTE ACCESSORS TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_gets_formatted_file_paths_attribute()
    {
        $filePaths = [
            'payment_proofs/test1.jpg',
            'payment_proofs/test2.pdf',
        ];

        // Create fake files
        foreach ($filePaths as $path) {
            Storage::disk('local')->put($path, 'test content');
        }

        $paymentProof = PaymentProof::factory()->create(['file_paths' => $filePaths]);
        $formatted = $paymentProof->formatted_file_paths;

        $this->assertIsArray($formatted);
        $this->assertCount(2, $formatted);

        foreach ($formatted as $file) {
            $this->assertArrayHasKey('original_name', $file);
            $this->assertArrayHasKey('full_path', $file);
            $this->assertArrayHasKey('url', $file);
            $this->assertArrayHasKey('size', $file);
        }
    }

    /** @test */
    public function payment_proof_gets_status_color_attribute()
    {
        $pending = PaymentProof::factory()->pending()->create();
        $underReview = PaymentProof::factory()->underReview()->create();
        $approved = PaymentProof::factory()->approved()->create();
        $rejected = PaymentProof::factory()->rejected()->create();

        $this->assertEquals('warning', $pending->status_color);
        $this->assertEquals('info', $underReview->status_color);
        $this->assertEquals('success', $approved->status_color);
        $this->assertEquals('danger', $rejected->status_color);
    }

    /** @test */
    public function payment_proof_gets_payment_method_display_attribute()
    {
        $bankTransfer = PaymentProof::factory()->bankTransfer()->create();
        $cash = PaymentProof::factory()->cash()->create();
        $mobileMoney = PaymentProof::factory()->mobileMoney()->create();
        $other = PaymentProof::factory()->other()->create();

        $this->assertEquals('Transferencia Bancaria', $bankTransfer->payment_method_display);
        $this->assertEquals('Efectivo', $cash->payment_method_display);
        $this->assertEquals('Dinero Móvil', $mobileMoney->payment_method_display);
        $this->assertEquals('Otro', $other->payment_method_display);
    }

    /**
     * =============================================
     * EDGE CASES AND ERROR CONDITIONS TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_handles_empty_file_paths()
    {
        $paymentProof = PaymentProof::factory()->withoutFiles()->create();

        $this->assertIsArray($paymentProof->file_paths);
        $this->assertEmpty($paymentProof->file_paths);
        $this->assertNull($paymentProof->file_type);
        $this->assertEquals(0, $paymentProof->total_file_size_mb);

        $formatted = $paymentProof->formatted_file_paths;
        $this->assertIsArray($formatted);
        $this->assertEmpty($formatted);
    }

    /** @test */
    public function payment_proof_handles_missing_file_paths()
    {
        $paymentProof = PaymentProof::factory()->create([
            'file_paths' => ['nonexistent/file.jpg'],
        ]);

        $formatted = $paymentProof->formatted_file_paths;
        $this->assertIsArray($formatted);
        $this->assertNotEmpty($formatted);

        // File size should be null for non-existent files
        $this->assertNull($formatted[0]['size']);
    }

    /** @test */
    public function payment_proof_handles_double_status_transitions()
    {
        $user = User::factory()->create();
        $paymentProof = PaymentProof::factory()->pending()->create();

        // Try to approve already approved payment
        $this->assertTrue($paymentProof->approve($user));
        $this->assertFalse($paymentProof->approve($user)); // Should not allow double approval

        // Try to review approved payment
        $this->assertFalse($paymentProof->startReview($user));
    }

    /** @test */
    public function payment_proof_handles_null_reviewer_in_status_methods()
    {
        $paymentProof = PaymentProof::factory()->create([
            'status' => PaymentProof::STATUS_APPROVED,
            'reviewed_by' => null,
        ]);

        // Should handle null reviewer gracefully
        $this->expectException(\Exception::class);
        $paymentProof->approve(null);
    }

    /** @test */
    public function payment_proof_factory_creates_valid_data()
    {
        $paymentProof = PaymentProof::factory()->create();

        $this->assertNotNull($paymentProof->id);
        $this->assertNotNull($paymentProof->tenant_id);
        $this->assertNotNull($paymentProof->subscription_id);
        $this->assertNotNull($paymentProof->payment_method);
        $this->assertNotNull($paymentProof->amount);
        $this->assertNotNull($paymentProof->currency);
        $this->assertNotNull($paymentProof->payment_date);
        $this->assertNotNull($paymentProof->reference_number);
        $this->assertNotNull($paymentProof->payer_name);
        $this->assertNotNull($paymentProof->status);
    }

    /**
     * =============================================
     * PERFORMANCE TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_performance_test_large_dataset()
    {
        $startTime = microtime(true);

        // Create 100 payment proofs
        PaymentProof::factory()->count(100)->create();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete within reasonable time (5 seconds)
        $this->assertLessThan(5.0, $executionTime);

        // Query performance test
        $startTime = microtime(true);
        $paymentProofs = PaymentProof::with(['tenant', 'subscription'])->get();
        $endTime = microtime(true);

        $this->assertLessThan(1.0, $endTime - $startTime);
        $this->assertEquals(100, $paymentProofs->count());
    }

    /** @test */
    public function payment_proof_relationship_loading_performance()
    {
        $paymentProof = PaymentProof::factory()->create();

        // Test eager loading
        $startTime = microtime(true);
        $loaded = PaymentProof::with(['tenant', 'subscription', 'paymentTransaction', 'reviewer'])
            ->find($paymentProof->id);
        $endTime = microtime(true);

        $this->assertInstanceOf(PaymentProof::class, $loaded);
        $this->assertLessThan(0.1, $endTime - $startTime);
    }

    /**
     * =============================================
     * INTEGRATION TESTS
     * =============================================
     */

    /** @test */
    public function payment_proof_integration_with_tenant_subscription()
    {
        $tenant = Tenant::factory()->create();
        $subscription = TenantSubscription::factory()->forTenant($tenant)->create();
        $transaction = PaymentTransaction::factory()->forSubscription($subscription)->create();
        $paymentProof = PaymentProof::factory()
            ->forTenant($tenant)
            ->forSubscription($subscription)
            ->withPaymentTransaction($transaction)
            ->create();

        // Test all relationships work together
        $this->assertEquals($tenant->id, $paymentProof->tenant->id);
        $this->assertEquals($subscription->id, $paymentProof->subscription->id);
        $this->assertEquals($transaction->id, $paymentProof->paymentTransaction->id);

        // Test consistency across related models
        $this->assertEquals($tenant->id, $subscription->tenant_id);
        $this->assertEquals($subscription->id, $transaction->subscription_id);
        $this->assertEquals($subscription->id, $paymentProof->subscription_id);
    }

    /** @test */
    public function payment_proof_works_with_all_payment_methods()
    {
        $methods = [
            PaymentProof::PAYMENT_METHOD_BANK_TRANSFER,
            PaymentProof::PAYMENT_METHOD_CASH,
            PaymentProof::PAYMENT_METHOD_MOBILE_MONEY,
            PaymentProof::PAYMENT_METHOD_OTHER,
        ];

        foreach ($methods as $method) {
            $paymentProof = PaymentProof::factory()->create(['payment_method' => $method]);
            $this->assertEquals($method, $paymentProof->payment_method);
            $this->assertNotNull($paymentProof->payment_method_display);
        }
    }

    /** @test */
    public function payment_proof_handles_all_status_transitions()
    {
        $statuses = [
            PaymentProof::STATUS_PENDING,
            PaymentProof::STATUS_UNDER_REVIEW,
            PaymentProof::STATUS_APPROVED,
            PaymentProof::STATUS_REJECTED,
        ];

        foreach ($statuses as $status) {
            $paymentProof = PaymentProof::factory()->create(['status' => $status]);
            $this->assertEquals($status, $paymentProof->status);
            $this->assertNotNull($paymentProof->status_color);
        }
    }
}
