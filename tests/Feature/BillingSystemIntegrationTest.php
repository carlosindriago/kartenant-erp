<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\PaymentSettings;
use App\Models\PaymentProof;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\BillingService;
use App\Services\PaymentApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BillingSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use landlord connection for all tests
        config(['database.default' => 'landlord']);
    }

    /** @test */
    public function it_can_create_subscription_plan()
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Professional plan for growing businesses',
            'price_monthly' => 49.99,
            'price_yearly' => 499.99,
            'currency' => 'USD',
            'has_trial' => true,
            'trial_days' => 14,
            'max_users' => 50,
            'max_products' => 1000,
            'max_sales_per_month' => 500,
            'max_storage_mb' => 10240,
            'enabled_modules' => ['inventory', 'pos', 'clients'],
            'features' => ['api_access', 'custom_reports', 'priority_support'],
            'is_active' => true,
            'is_visible' => true,
            'is_featured' => true,
            'sort_order' => 2,
        ]);

        $this->assertInstanceOf(SubscriptionPlan::class, $plan);
        $this->assertEquals('Professional', $plan->name);
        $this->assertEquals('professional', $plan->slug);
        $this->assertEquals(49.99, $plan->price_monthly);
        $this->assertEquals(499.99, $plan->price_yearly);
        $this->assertTrue($plan->is_active);
        $this->assertTrue($plan->has_trial);
        $this->assertEquals(14, $plan->trial_days);
    }

    /** @test */
    public function it_can_create_tenant_subscription()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => $plan->price_monthly,
            'currency' => $plan->currency,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'next_billing_at' => now()->addMonth(),
            'auto_renew' => true,
        ]);

        $this->assertInstanceOf(TenantSubscription::class, $subscription);
        $this->assertEquals($tenant->id, $subscription->tenant_id);
        $this->assertEquals($plan->id, $subscription->subscription_plan_id);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals('monthly', $subscription->billing_cycle);
        $this->assertEquals($plan->price_monthly, $subscription->price);
        $this->assertTrue($subscription->auto_renew);
    }

    /** @test */
    public function it_can_create_payment_settings()
    {
        $settings = PaymentSettings::create([
            'bank_name' => 'Banco Nacional',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Empresa S.A.',
            'bank_routing_number' => '123456789',
            'bank_swift_code' => 'NACIOUS1XXX',
            'bank_iban' => 'US12345678901234567890123456',
            'payment_instructions' => 'Realice el depósito en la cuenta indicada y envíe el comprobante.',
            'payment_note' => 'El proceso de aprobación puede tomar hasta 48 horas.',
            'business_name' => 'Mi Empresa S.A.',
            'business_tax_id' => 'J-123456789',
            'business_address' => 'Calle Principal #123',
            'business_phone' => '+58-212-1234567',
            'business_email' => 'facturacion@miempresa.com',
            'default_currency' => 'USD',
            'locale' => 'es',
            'manual_approval_required' => true,
            'approval_timeout_hours' => 48,
            'auto_reminder_enabled' => true,
            'reminder_interval_hours' => 24,
            'max_file_size_mb' => 10,
            'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
            'invoice_prefix' => 'INV-',
            'receipt_prefix' => 'REC-',
            'tax_rate' => 0.1600,
            'tax_included' => false,
        ]);

        $this->assertInstanceOf(PaymentSettings::class, $settings);
        $this->assertEquals('Banco Nacional', $settings->bank_name);
        $this->assertEquals('1234567890', $settings->bank_account_number);
        $this->assertEquals('Empresa S.A.', $settings->bank_account_holder);
        $this->assertEquals(0.1600, $settings->tax_rate);
        $this->assertTrue($settings->manual_approval_required);
        $this->assertTrue($settings->is_bank_transfer_configured());
    }

    /** @test */
    public function it_can_create_payment_proof()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $paymentProof = PaymentProof::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'payment_method' => 'bank_transfer',
            'amount' => $subscription->price,
            'currency' => $subscription->currency,
            'payment_date' => now()->subDays(1),
            'reference_number' => 'TXN123456789',
            'payer_name' => 'Juan Pérez',
            'notes' => 'Pago transferencia bancaria',
            'file_paths' => ['payment-proofs/test.pdf', 'payment-proofs/receipt.jpg'],
            'file_type' => 'pdf',
            'total_file_size_mb' => 2.5,
            'status' => PaymentProof::STATUS_PENDING,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
        ]);

        $this->assertInstanceOf(PaymentProof::class, $paymentProof);
        $this->assertEquals($tenant->id, $paymentProof->tenant_id);
        $this->assertEquals($subscription->id, $paymentProof->subscription_id);
        $this->assertEquals('bank_transfer', $paymentProof->payment_method);
        $this->assertEquals($subscription->price, $paymentProof->amount);
        $this->assertTrue($paymentProof->isPending());
        $this->assertFalse($paymentProof->isApproved());
        $this->assertFalse($paymentProof->isRejected());
    }

    /** @test */
    public function it_can_create_invoice()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-20250101-0001',
            'status' => Invoice::STATUS_DRAFT,
            'type' => 'subscription',
            'billing_period_start' => now(),
            'billing_period_end' => now()->addMonth(),
            'due_date' => now()->addDays(30),
            'subtotal' => $subscription->price,
            'tax_amount' => $subscription->price * 0.16,
            'total_amount' => $subscription->price * 1.16,
            'currency' => $subscription->currency,
            'plan_name' => $plan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'plan_price' => $subscription->price,
            'customer_data' => [
                'name' => $tenant->name,
                'email' => $tenant->owner_email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
            ],
            'line_items' => [
                [
                    'description' => $plan->name . ' - ' . ucfirst($subscription->billing_cycle),
                    'quantity' => 1,
                    'unit_price' => $subscription->price,
                    'total' => $subscription->price,
                ],
            ],
        ]);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($tenant->id, $invoice->tenant_id);
        $this->assertEquals($subscription->id, $invoice->subscription_id);
        $this->assertEquals(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertEquals('subscription', $invoice->type);
        $this->assertTrue($invoice->isPending());
        $this->assertFalse($invoice->isPaid());
        $this->assertFalse($invoice->isOverdue());
    }

    /** @test */
    public function it_can_create_payment_transaction()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $transaction = PaymentTransaction::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'gateway_driver' => 'manual',
            'amount' => $subscription->price,
            'currency' => $subscription->currency,
            'status' => PaymentTransaction::STATUS_PENDING,
            'transaction_id' => 'MANUAL-' . uniqid(),
            'proof_of_payment' => json_encode(['file_paths' => ['payment-proofs/test.pdf']]),
            'metadata' => [
                'payment_method' => 'bank_transfer',
                'reference_number' => 'TXN123456789',
            ],
        ]);

        $this->assertInstanceOf(PaymentTransaction::class, $transaction);
        $this->assertEquals($tenant->id, $transaction->tenant_id);
        $this->assertEquals($subscription->id, $transaction->subscription_id);
        $this->assertEquals('manual', $transaction->gateway_driver);
        $this->assertEquals(PaymentTransaction::STATUS_PENDING, $transaction->status);
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isApproved());
        $this->assertFalse($transaction->isRejected());
    }

    /** @test */
    public function it_can_test_model_relationships()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);
        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
        ]);
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
        ]);
        $transaction = PaymentTransaction::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
        ]);

        // Test Tenant relationships
        $this->assertInstanceOf(TenantSubscription::class, $tenant->subscriptions()->first());
        $this->assertInstanceOf(PaymentProof::class, $tenant->paymentProofs()->first());
        $this->assertInstanceOf(Invoice::class, $tenant->invoices()->first());
        $this->assertInstanceOf(PaymentTransaction::class, $tenant->paymentTransactions()->first());

        // Test Subscription relationships
        $this->assertInstanceOf(Tenant::class, $subscription->tenant);
        $this->assertInstanceOf(SubscriptionPlan::class, $subscription->plan);
        $this->assertInstanceOf(PaymentProof::class, $subscription->paymentProofs()->first());
        $this->assertInstanceOf(Invoice::class, $subscription->invoices()->first());
        $this->assertInstanceOf(PaymentTransaction::class, $subscription->paymentTransactions()->first());

        // Test Plan relationships
        $this->assertInstanceOf(TenantSubscription::class, $plan->subscriptions()->first());

        // Test PaymentProof relationships
        $this->assertInstanceOf(Tenant::class, $paymentProof->tenant);
        $this->assertInstanceOf(TenantSubscription::class, $paymentProof->subscription);

        // Test Invoice relationships
        $this->assertInstanceOf(Tenant::class, $invoice->tenant);
        $this->assertInstanceOf(TenantSubscription::class, $invoice->subscription);

        // Test Transaction relationships
        $this->assertInstanceOf(Tenant::class, $transaction->tenant);
        $this->assertInstanceOf(TenantSubscription::class, $transaction->subscription);
    }

    /** @test */
    public function it_can_generate_invoice_number()
    {
        $number1 = Invoice::generateInvoiceNumber();
        $number2 = Invoice::generateInvoiceNumber();

        $this->assertNotEmpty($number1);
        $this->assertNotEmpty($number2);
        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('INV-', $number1);
        $this->assertStringStartsWith('INV-', $number2);
    }

    /** @test */
    public function it_can_calculate_subscription_end_date()
    {
        $plan = SubscriptionPlan::factory()->create();

        // Monthly subscription
        $monthlySubscription = TenantSubscription::factory()->create([
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);

        $expectedEndDate = now()->copy()->addMonth();
        $this->assertEquals($expectedEndDate->toDateString(), $monthlySubscription->ends_at->toDateString());

        // Yearly subscription
        $yearlySubscription = TenantSubscription::factory()->create([
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
        ]);

        $expectedEndDate = now()->copy()->addYear();
        $this->assertEquals($expectedEndDate->toDateString(), $yearlySubscription->ends_at->toDateString());
    }

    /** @test */
    public function it_can_validate_payment_proof_data()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $paymentProofService = app(\App\Services\PaymentProofService::class);

        // Valid data
        $validData = [
            'payment_method' => 'bank_transfer',
            'amount' => $subscription->price,
            'payment_date' => now()->toDateString(),
            'reference_number' => 'TXN123456789',
            'payer_name' => 'Juan Pérez',
            'notes' => 'Payment notes',
        ];

        $validation = $paymentProofService->validatePaymentProofData($validData, $subscription);
        $this->assertTrue($validation['valid']);
        $this->assertArrayHasKey('data', $validation);

        // Invalid amount
        $invalidData = $validData;
        $invalidData['amount'] = $subscription->price + 10;

        $validation = $paymentProofService->validatePaymentProofData($invalidData, $subscription);
        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('errors', $validation);

        // Invalid payment date (future)
        $invalidData = $validData;
        $invalidData['payment_date'] = now()->addDay()->toDateString();

        $validation = $paymentProofService->validatePaymentProofData($invalidData, $subscription);
        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('errors', $validation);
    }

    /** @test */
    public function it_can_test_subscription_service()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscriptionService = app(SubscriptionService::class);

        // Test subscription creation
        $subscription = $subscriptionService->createSubscription(
            $tenant,
            $plan,
            'monthly'
        );

        $this->assertInstanceOf(TenantSubscription::class, $subscription);
        $this->assertEquals($tenant->id, $subscription->tenant_id);
        $this->assertEquals($plan->id, $subscription->subscription_plan_id);
        $this->assertEquals('monthly', $subscription->billing_cycle);
        $this->assertEquals($plan->price_monthly, $subscription->price);
        $this->assertEquals($plan->currency, $subscription->currency);

        // Test plan change
        $newPlan = SubscriptionPlan::factory()->create([
            'price_monthly' => 99.99,
            'price_yearly' => 999.99,
        ]);

        $result = $subscriptionService->changePlan($subscription, $newPlan);
        $this->assertTrue($result);

        $subscription->refresh();
        $this->assertEquals($newPlan->id, $subscription->subscription_plan_id);
        $this->assertEquals($newPlan->price_monthly, $subscription->price);

        // Test subscription cancellation
        $result = $subscriptionService->cancelSubscription($subscription, 'Test cancellation');
        $this->assertTrue($result);

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    /** @test */
    public function it_can_test_billing_service()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $billingService = app(BillingService::class);

        // Test invoice generation
        $invoice = $billingService->generateSubscriptionInvoice($subscription);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($tenant->id, $invoice->tenant_id);
        $this->assertEquals($subscription->id, $invoice->subscription_id);
        $this->assertEquals('subscription', $invoice->type);
        $this->assertEquals($subscription->price, $invoice->subtotal);

        // Test tax calculation
        $amount = 100;
        $taxAmount = $billingService->calculateTax($amount);
        $expectedTax = $amount * 0.16; // Assuming 16% tax rate
        $this->assertEquals($expectedTax, $taxAmount);

        // Test total with tax
        $totalAmount = $billingService->getTotalWithTax($amount);
        $expectedTotal = $amount + $expectedTax;
        $this->assertEquals($expectedTotal, $totalAmount);

        // Test statistics
        $stats = $billingService->getBillingStatistics();
        $this->assertArrayHasKey('total_invoices', $stats);
        $this->assertArrayHasKey('paid_invoices', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('outstanding_amount', $stats);
    }

    /** @test */
    public function it_can_test_payment_approval_service()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);
        $paymentProof = PaymentProof::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->price,
        ]);

        $paymentApprovalService = app(PaymentApprovalService::class);

        // Test payment proof approval
        $result = $paymentApprovalService->approvePaymentProof($paymentProof, $superAdmin);
        $this->assertTrue($result);

        $paymentProof->refresh();
        $subscription->refresh();
        $tenant->refresh();

        $this->assertEquals(PaymentProof::STATUS_APPROVED, $paymentProof->status);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals('active', $tenant->status);
        $this->assertEquals($superAdmin->id, $paymentProof->reviewed_by);
        $this->assertNotNull($paymentProof->reviewed_at);

        // Test payment proof rejection
        $newPaymentProof = PaymentProof::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->price,
        ]);

        $result = $paymentApprovalService->rejectPaymentProof(
            $newPaymentProof,
            $superAdmin,
            'Invalid payment proof'
        );
        $this->assertTrue($result);

        $newPaymentProof->refresh();
        $this->assertEquals(PaymentProof::STATUS_REJECTED, $newPaymentProof->status);
        $this->assertEquals('Invalid payment proof', $newPaymentProof->rejection_reason);
        $this->assertEquals($superAdmin->id, $newPaymentProof->reviewed_by);
    }

    /** @test */
    public function it_can_test_payment_proof_service()
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $paymentProofService = app(\App\Services\PaymentProofService::class);

        // Test upload limits validation
        $result = $paymentProofService->validateUploadLimits(3, 5.0);
        $this->assertTrue($result['valid']);

        $result = $paymentProofService->validateUploadLimits(10, 50.0); // Too many files
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('No se pueden subir más de', $result['error']);

        // Test file type validation
        $file = new \Illuminate\Http\UploadedFile(
            base_path('tests/files/sample.pdf'),
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $validation = $paymentProofService->validateFile($file);
        $this->assertTrue($validation['valid']);

        // Test invalid file
        $invalidFile = new \Illuminate\Http\UploadedFile(
            base_path('tests/files/sample.exe'),
            'sample.exe',
            'application/octet-stream',
            null,
            true
        );

        $validation = $paymentProofService->validateFile($invalidFile);
        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('error', $validation);
    }

    /** @test */
    public function it_can_test_payment_transaction_service()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $paymentTransactionService = app(\App\Services\PaymentTransactionService::class);

        // Test transaction creation
        $transaction = $paymentTransactionService->createManualTransaction(
            $subscription,
            new \App\Models\PaymentProof([
                'amount' => $subscription->price,
                'currency' => $subscription->currency,
                'file_paths' => ['test.pdf'],
                'payment_method' => 'bank_transfer',
                'reference_number' => 'TXN123',
                'payment_date' => now(),
            ])
        );

        $this->assertInstanceOf(PaymentTransaction::class, $transaction);
        $this->assertEquals($tenant->id, $transaction->tenant_id);
        $this->assertEquals($subscription->id, $transaction->subscription_id);
        $this->assertEquals('manual', $transaction->gateway_driver);
        $this->assertEquals(PaymentTransaction::STATUS_PENDING, $transaction->status);

        // Test transaction approval
        $result = $paymentTransactionService->processApproval($transaction, $superAdmin);
        $this->assertTrue($result);

        $transaction->refresh();
        $this->assertEquals(PaymentTransaction::STATUS_APPROVED, $transaction->status);
        $this->assertEquals($superAdmin->id, $transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);

        // Test statistics
        $stats = $paymentTransactionService->getTransactionStatistics();
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('approved', $stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
        $this->assertArrayHasKey('approval_rate', $stats);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        Storage::disk('public')->deleteDirectory('payment-proofs');
        Storage::disk('public')->deleteDirectory('invoices');

        parent::tearDown();
    }
}