<?php

namespace Tests\Feature\Api;

use App\Models\PaymentProof;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tenant Billing API Test
 *
 * Tests all billing API endpoints for proper functionality,
 * security, and tenant isolation
 */
class TenantBillingApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $user;

    private string $token;

    private TenantSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        // Create test user
        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant->id);

        // Create Sanctum token
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create subscription
        $this->subscription = TenantSubscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        // Create payment settings
        PaymentSettings::factory()->create();

        // Set tenant context
        $this->tenant->makeCurrent();

        Storage::fake('public');
    }

    protected function apiHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Test billing dashboard retrieval
     */
    public function test_can_get_billing_dashboard(): void
    {
        $response = $this->getJson('/api/v1/billing', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'subscription' => [
                        'id',
                        'status',
                        'plan_name',
                        'price',
                        'currency',
                        'starts_at',
                        'ends_at',
                        'is_trial',
                        'is_active',
                        'days_remaining',
                    ],
                    'payment_settings' => [
                        'max_file_size_mb',
                        'allowed_file_types',
                        'bank_account_info',
                        'payment_instructions',
                    ],
                    'recent_payments' => [
                        '*' => [
                            'id',
                            'amount',
                            'currency',
                            'payment_method',
                            'payment_date',
                            'status',
                            'status_display',
                            'payment_method_display',
                            'file_count',
                            'created_at',
                            'invoice_number',
                        ],
                    ],
                    'billing_stats' => [
                        'total_payments',
                        'pending_payments',
                        'approved_payments',
                        'rejected_payments',
                        'total_amount_paid',
                        'last_payment_date',
                        'subscription_status',
                        'subscription_ends_at',
                        'days_until_expiry',
                    ],
                ],
                'message',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscription.id', $this->subscription->id);
    }

    /**
     * Test billing dashboard without authentication
     */
    public function test_cannot_get_billing_dashboard_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/billing', [
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'application/json',
        ]);

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }

    /**
     * Test billing dashboard without tenant context
     */
    public function test_cannot_get_billing_dashboard_without_tenant_id(): void
    {
        $response = $this->getJson('/api/v1/billing', [
            'Authorization' => "Bearer {$this->token}",
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'TENANT_ID_MISSING');
    }

    /**
     * Test payment proof submission
     */
    public function test_can_submit_payment_proof(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg', 500); // 500KB
        $paymentData = [
            'files' => [$file],
            'payment_method' => 'bank_transfer',
            'amount' => $this->subscription->price,
            'payment_date' => now()->subDay()->format('Y-m-d'),
            'reference_number' => 'TRANSF-001',
            'payer_name' => 'Juan Pérez',
            'notes' => 'Test payment proof',
        ];

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment_proof' => [
                        'id',
                        'amount',
                        'currency',
                        'payment_method',
                        'payment_date',
                        'reference_number',
                        'payer_name',
                        'status',
                        'status_display',
                        'payment_method_display',
                        'notes',
                        'files' => [
                            '*' => [
                                'path',
                                'url',
                                'filename',
                                'size',
                                'last_modified',
                            ],
                        ],
                        'total_file_size_mb',
                        'created_at',
                    ],
                    'subscription' => [
                        'id',
                        'status',
                        'ends_at',
                    ],
                ],
                'message',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_proof.status', 'pending')
            ->assertJsonPath('data.payment_proof.tenant_id', $this->tenant->id);

        // Verify payment proof was created in landlord database
        $this->assertDatabaseHas('payment_proofs', [
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'amount' => $this->subscription->price,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ], 'landlord');

        // Verify file was stored
        $this->assertTrue(
            Storage::disk('public')->exists("payment-proofs/{$this->tenant->id}")
        );
    }

    /**
     * Test payment proof submission validation
     */
    public function test_payment_proof_submission_validation(): void
    {
        $response = $this->postJson('/api/v1/billing', [], $this->apiHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'files',
                'payment_method',
                'amount',
                'payment_date',
            ]);
    }

    /**
     * Test payment proof submission with amount mismatch
     */
    public function test_payment_proof_submission_amount_mismatch(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg', 500);
        $paymentData = [
            'files' => [$file],
            'payment_method' => 'bank_transfer',
            'amount' => $this->subscription->price * 2, // Double amount
            'payment_date' => now()->subDay()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'amount',
            ]);
    }

    /**
     * Test payment proof submission with duplicate data
     */
    public function test_payment_proof_submission_duplicate(): void
    {
        // Create existing payment proof
        PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'amount' => $this->subscription->price,
            'payment_date' => now()->subDay()->toDateString(),
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg', 500);
        $paymentData = [
            'files' => [$file],
            'payment_method' => 'bank_transfer',
            'amount' => $this->subscription->price,
            'payment_date' => now()->subDay()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'duplicate',
            ]);
    }

    /**
     * Test payment history retrieval
     */
    public function test_can_get_payment_history(): void
    {
        // Create test payment proofs
        PaymentProof::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/v1/billing/history?page=1&per_page=10', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'currency',
                        'payment_method',
                        'payment_date',
                        'reference_number',
                        'status',
                        'status_display',
                        'payment_method_display',
                        'notes',
                        'file_count',
                        'total_file_size_mb',
                        'created_at',
                        'invoice_number',
                        'subscription_id',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'to',
                    'per_page',
                    'last_page',
                    'total',
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next',
                    ],
                    'timestamp',
                    'version',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /**
     * Test payment history with filters
     */
    public function test_can_get_payment_history_with_filters(): void
    {
        // Create test payment proofs with different statuses
        PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
            'payment_date' => now()->subDays(5)->toDateString(),
        ]);

        PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'approved',
            'payment_method' => 'cash',
            'payment_date' => now()->subDays(10)->toDateString(),
        ]);

        $response = $this->getJson(
            '/api/v1/billing/history?status=pending&payment_method=bank_transfer',
            $this->apiHeaders()
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', function ($data) {
                return count($data) === 1 && $data[0]['status'] === 'pending';
            });
    }

    /**
     * Test payment proof details retrieval
     */
    public function test_can_get_payment_proof_details(): void
    {
        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'file_paths' => ['payment-proofs/test/file.jpg'],
        ]);

        $response = $this->getJson("/api/v1/billing/payment-proofs/{$paymentProof->id}", $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment_proof' => [
                        'id',
                        'amount',
                        'currency',
                        'payment_method',
                        'payment_date',
                        'reference_number',
                        'payer_name',
                        'status',
                        'status_display',
                        'payment_method_display',
                        'notes',
                        'rejection_reason',
                        'review_notes',
                        'reviewed_by',
                        'reviewed_at',
                        'files',
                        'total_file_size_mb',
                        'created_at',
                        'updated_at',
                    ],
                    'subscription',
                    'invoice',
                ],
                'message',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_proof.id', $paymentProof->id);
    }

    /**
     * Test payment proof details for non-existent proof
     */
    public function test_cannot_get_non_existent_payment_proof_details(): void
    {
        $response = $this->getJson('/api/v1/billing/payment-proofs/99999', $this->apiHeaders());

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * Test tenant isolation - cannot access other tenant's payment proofs
     */
    public function test_cannot_access_other_tenant_payment_proof(): void
    {
        // Create another tenant and payment proof
        $otherTenant = Tenant::factory()->create();
        $otherPaymentProof = PaymentProof::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->getJson("/api/v1/billing/payment-proofs/{$otherPaymentProof->id}", $this->apiHeaders());

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * Test payment proof deletion
     */
    public function test_can_delete_pending_payment_proof(): void
    {
        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $response = $this->deleteJson("/api/v1/billing/payment-proofs/{$paymentProof->id}", $this->apiHeaders());

        $response->assertNoContent();

        // Verify payment proof is soft deleted
        $this->assertSoftDeleted('payment_proofs', [
            'id' => $paymentProof->id,
            'tenant_id' => $this->tenant->id,
        ], 'landlord');
    }

    /**
     * Test cannot delete approved payment proof
     */
    public function test_cannot_delete_approved_payment_proof(): void
    {
        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'approved',
        ]);

        $response = $this->deleteJson("/api/v1/billing/payment-proofs/{$paymentProof->id}", $this->apiHeaders());

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    /**
     * Test file download
     */
    public function test_can_download_payment_proof_file(): void
    {
        $filePath = "payment-proofs/{$this->tenant->id}/test-receipt.jpg";
        Storage::disk('public')->put($filePath, 'fake image content');

        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'file_paths' => [$filePath],
        ]);

        $encodedPath = urlencode($filePath);
        $response = $this->getJson("/api/v1/billing/payment-proofs/{$paymentProof->id}/files/{$encodedPath}", $this->apiHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type')
            ->assertHeader('Content-Disposition');
    }

    /**
     * Test cannot download file from other tenant's payment proof
     */
    public function test_cannot_download_other_tenant_file(): void
    {
        $otherTenant = Tenant::factory()->create();
        $filePath = "payment-proofs/{$otherTenant->id}/test-receipt.jpg";
        Storage::disk('public')->put($filePath, 'fake image content');

        $otherPaymentProof = PaymentProof::factory()->create([
            'tenant_id' => $otherTenant->id,
            'file_paths' => [$filePath],
        ]);

        $encodedPath = urlencode($filePath);
        $response = $this->getJson("/api/v1/billing/payment-proofs/{$otherPaymentProof->id}/files/{$encodedPath}", $this->apiHeaders());

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * Test cannot download unauthorized file from payment proof
     */
    public function test_cannot_download_unauthorized_file(): void
    {
        $filePath = "payment-proofs/{$this->tenant->id}/test-receipt.jpg";
        $unauthorizedPath = "payment-proofs/{$this->tenant->id}/unauthorized-file.jpg";
        Storage::disk('public')->put($filePath, 'fake image content');
        Storage::disk('public')->put($unauthorizedPath, 'fake image content');

        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
            'file_paths' => [$filePath], // Only authorize one file
        ]);

        $encodedPath = urlencode($unauthorizedPath);
        $response = $this->getJson("/api/v1/billing/payment-proofs/{$paymentProof->id}/files/{$encodedPath}", $this->apiHeaders());

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    /**
     * Test file upload size limits
     */
    public function test_file_upload_size_validation(): void
    {
        // Create oversized file (6MB, default limit is 5MB)
        $largeFile = UploadedFile::fake()->image('large-receipt.jpg', 6000);

        $paymentData = [
            'files' => [$largeFile],
            'payment_method' => 'bank_transfer',
            'amount' => $this->subscription->price,
            'payment_date' => now()->subDay()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'files.0',
            ]);
    }

    /**
     * Test file type validation
     */
    public function test_file_type_validation(): void
    {
        // Create file with unsupported type
        $invalidFile = UploadedFile::fake()->create('document.exe', 100);

        $paymentData = [
            'files' => [$invalidFile],
            'payment_method' => 'bank_transfer',
            'amount' => $this->subscription->price,
            'payment_date' => now()->subDay()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'files.0',
            ]);
    }

    /**
     * Test pagination limits
     */
    public function test_pagination_limits(): void
    {
        // Create test payment proofs
        PaymentProof::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Test per_page limit (max 100)
        $response = $this->getJson('/api/v1/billing/history?per_page=150', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100); // Should be limited to 100
    }

    /**
     * Test date validation
     */
    public function test_payment_date_validation(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg', 500);

        $futureDate = now()->addDay()->format('Y-m-d');
        $tooOldDate = now()->subDays(100)->format('Y-m-d');

        // Test future date
        $paymentData = [
            'files' => [$file],
            'payment_method' => 'bank_transfer',
            'amount' => $this->subscription->price,
            'payment_date' => $futureDate,
        ];

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_date']);

        // Test too old date
        $paymentData['payment_date'] = $tooOldDate;

        $response = $this->postJson('/api/v1/billing', $paymentData, [
            'Authorization' => "Bearer {$this->token}",
            'X-Tenant-ID' => $this->tenant->id,
            'Content-Type' => 'multipart/form-data',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_date']);
    }

    /**
     * Test tenant context consistency
     */
    public function test_tenant_context_consistency(): void
    {
        // Ensure all database operations use correct connection
        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Verify record exists in landlord database
        $this->assertDatabaseHas('payment_proofs', [
            'id' => $paymentProof->id,
            'tenant_id' => $this->tenant->id,
        ], 'landlord');

        // Verify record does NOT exist in tenant database
        $this->assertDatabaseMissing('payment_proofs', [
            'id' => $paymentProof->id,
        ], 'tenant');

        // Test API access uses correct context
        $response = $this->getJson("/api/v1/billing/payment-proofs/{$paymentProof->id}", $this->apiHeaders());

        $response->assertOk();
    }
}
