<?php

use App\Models\PaymentProof;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\SubscriptionPlan;

test('tenant subscription model can be instantiated', function () {
    $subscription = new TenantSubscription();

    expect($subscription)->toBeInstanceOf(TenantSubscription::class);
    expect($subscription->getConnectionName())->toBe('landlord');
    expect($subscription->getTable())->toBe('tenant_subscriptions');
});

test('tenant subscription has correct fillable attributes', function () {
    $subscription = new TenantSubscription();
    $fillable = $subscription->getFillable();

    $expectedFillable = [
        'tenant_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'price',
        'currency',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'next_billing_at',
        'payment_method',
        'stripe_subscription_id',
        'stripe_customer_id',
        'auto_renew',
        'cancellation_reason',
        'usage_stats',
    ];

    expect($fillable)->toBe($expectedFillable);
});

test('tenant subscription has correct casts', function () {
    $subscription = new TenantSubscription();
    $casts = $subscription->getCasts();

    expect($casts)->toHaveKey('price');
    expect($casts)->toHaveKey('starts_at');
    expect($casts)->toHaveKey('ends_at');
    expect($casts)->toHaveKey('trial_ends_at');
    expect($casts)->toHaveKey('cancelled_at');
    expect($casts)->toHaveKey('next_billing_at');
    expect($casts)->toHaveKey('auto_renew');
    expect($casts)->toHaveKey('usage_stats');

    expect($casts['price'])->toBe('decimal:2');
    expect($casts['starts_at'])->toBe('datetime');
    expect($casts['ends_at'])->toBe('datetime');
    expect($casts['auto_renew'])->toBe('boolean');
    expect($casts['usage_stats'])->toBe('array');
});

test('payment transaction model can be instantiated', function () {
    $transaction = new PaymentTransaction();

    expect($transaction)->toBeInstanceOf(PaymentTransaction::class);
    expect($transaction->getConnectionName())->toBe('landlord');
    expect($transaction->getTable())->toBe('payment_transactions');
});

test('payment transaction has correct constants', function () {
    expect(PaymentTransaction::STATUS_PENDING)->toBe('pending');
    expect(PaymentTransaction::STATUS_APPROVED)->toBe('approved');
    expect(PaymentTransaction::STATUS_REJECTED)->toBe('rejected');
    expect(PaymentTransaction::STATUS_COMPLETED)->toBe('completed');
    expect(PaymentTransaction::STATUS_FAILED)->toBe('failed');
});

test('payment transaction has correct fillable attributes', function () {
    $transaction = new PaymentTransaction();
    $fillable = $transaction->getFillable();

    $expectedFillable = [
        'tenant_id',
        'subscription_id',
        'gateway_driver',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'proof_of_payment',
        'metadata',
        'approved_by',
        'approved_at',
    ];

    expect($fillable)->toBe($expectedFillable);
});

test('payment transaction has correct casts', function () {
    $transaction = new PaymentTransaction();
    $casts = $transaction->getCasts();

    expect($casts)->toHaveKey('amount');
    expect($casts)->toHaveKey('metadata');
    expect($casts)->toHaveKey('approved_at');

    expect($casts['amount'])->toBe('decimal:2');
    expect($casts['metadata'])->toBe('array');
    expect($casts['approved_at'])->toBe('datetime');
});

test('all billing models use landlord connection', function () {
    $paymentProof = new PaymentProof();
    $subscription = new TenantSubscription();
    $transaction = new PaymentTransaction();

    expect($paymentProof->getConnectionName())->toBe('landlord');
    expect($subscription->getConnectionName())->toBe('landlord');
    expect($transaction->getConnectionName())->toBe('landlord');
});

test('payment proof relationship methods exist', function () {
    $paymentProof = new PaymentProof();

    expect(method_exists($paymentProof, 'tenant'))->toBeTrue();
    expect(method_exists($paymentProof, 'subscription'))->toBeTrue();
    expect(method_exists($paymentProof, 'paymentTransaction'))->toBeTrue();
    expect(method_exists($paymentProof, 'reviewer'))->toBeTrue();
});

test('tenant subscription relationship methods exist', function () {
    $subscription = new TenantSubscription();

    expect(method_exists($subscription, 'tenant'))->toBeTrue();
});

test('payment transaction relationship methods exist', function () {
    $transaction = new PaymentTransaction();

    expect(method_exists($transaction, 'tenant'))->toBeTrue();
    expect(method_exists($transaction, 'subscription'))->toBeTrue();
});

test('payment proof scopes exist', function () {
    $paymentProof = new PaymentProof();

    expect(method_exists($paymentProof, 'scopePending'))->toBeTrue();
    expect(method_exists($paymentProof, 'scopeUnderReview'))->toBeTrue();
    expect(method_exists($paymentProof, 'scopeApproved'))->toBeTrue();
    expect(method_exists($paymentProof, 'scopeRejected'))->toBeTrue();
    expect(method_exists($paymentProof, 'scopeForTenant'))->toBeTrue();
    expect(method_exists($paymentProof, 'scopeForMethod'))->toBeTrue();
});

test('billing models use soft deletes where appropriate', function () {
    $paymentProof = new PaymentProof();
    $subscription = new TenantSubscription();
    $transaction = new PaymentTransaction();

    $paymentProofTraits = class_uses($paymentProof);
    $subscriptionTraits = class_uses($subscription);

    expect($paymentProofTraits)->toHaveKey('Illuminate\Database\Eloquent\SoftDeletes');
    expect($subscriptionTraits)->toHaveKey('Illuminate\Database\Eloquent\SoftDeletes');
    // PaymentTransaction doesn't use soft deletes by default
});

test('payment proof validation constants are complete', function () {
    // Status constants
    expect(PaymentProof::STATUS_PENDING)->toBe('pending');
    expect(PaymentProof::STATUS_UNDER_REVIEW)->toBe('under_review');
    expect(PaymentProof::STATUS_APPROVED)->toBe('approved');
    expect(PaymentProof::STATUS_REJECTED)->toBe('rejected');

    // Payment method constants
    expect(PaymentProof::PAYMENT_METHOD_BANK_TRANSFER)->toBe('bank_transfer');
    expect(PaymentProof::PAYMENT_METHOD_CASH)->toBe('cash');
    expect(PaymentProof::PAYMENT_METHOD_MOBILE_MONEY)->toBe('mobile_money');
    expect(PaymentProof::PAYMENT_METHOD_OTHER)->toBe('other');
});

test('billing models have proper table names', function () {
    $paymentProof = new PaymentProof();
    $subscription = new TenantSubscription();
    $transaction = new PaymentTransaction();

    expect($paymentProof->getTable())->toBe('payment_proofs');
    expect($subscription->getTable())->toBe('tenant_subscriptions');
    expect($transaction->getTable())->toBe('payment_transactions');
});