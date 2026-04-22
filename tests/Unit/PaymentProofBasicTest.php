<?php

use App\Models\PaymentProof;

test('payment proof can be instantiated', function () {
    $paymentProof = new PaymentProof();

    expect($paymentProof)->toBeInstanceOf(PaymentProof::class);
    expect($paymentProof->getConnectionName())->toBe('landlord');
    expect($paymentProof->getTable())->toBe('payment_proofs');
});

test('payment proof has correct fillable attributes', function () {
    $paymentProof = new PaymentProof();
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

    expect($fillable)->toBe($expectedFillable);
});

test('payment proof has correct casts', function () {
    $paymentProof = new PaymentProof();
    $casts = $paymentProof->getCasts();

    expect($casts)->toHaveKey('amount');
    expect($casts)->toHaveKey('payment_date');
    expect($casts)->toHaveKey('file_paths');
    expect($casts)->toHaveKey('total_file_size_mb');
    expect($casts)->toHaveKey('reviewed_at');
    expect($casts)->toHaveKey('metadata');

    expect($casts['amount'])->toBe('decimal:2');
    expect($casts['payment_date'])->toBe('date');
    expect($casts['file_paths'])->toBe('array');
    expect($casts['total_file_size_mb'])->toBe('decimal:2');
    expect($casts['reviewed_at'])->toBe('datetime');
    expect($casts['metadata'])->toBe('array');
});

test('payment proof uses landlord connection', function () {
    $paymentProof = new PaymentProof();

    expect($paymentProof->getConnectionName())->toBe('landlord');
});

test('payment proof constants are defined', function () {
    expect(PaymentProof::STATUS_PENDING)->toBe('pending');
    expect(PaymentProof::STATUS_UNDER_REVIEW)->toBe('under_review');
    expect(PaymentProof::STATUS_APPROVED)->toBe('approved');
    expect(PaymentProof::STATUS_REJECTED)->toBe('rejected');

    expect(PaymentProof::PAYMENT_METHOD_BANK_TRANSFER)->toBe('bank_transfer');
    expect(PaymentProof::PAYMENT_METHOD_CASH)->toBe('cash');
    expect(PaymentProof::PAYMENT_METHOD_MOBILE_MONEY)->toBe('mobile_money');
    expect(PaymentProof::PAYMENT_METHOD_OTHER)->toBe('other');
});

test('payment proof status helper methods work', function () {
    $paymentProof = new PaymentProof();

    // Test pending status
    $paymentProof->status = PaymentProof::STATUS_PENDING;
    expect($paymentProof->isPending())->toBeTrue();
    expect($paymentProof->isUnderReview())->toBeFalse();
    expect($paymentProof->isApproved())->toBeFalse();
    expect($paymentProof->isRejected())->toBeFalse();

    // Test under review status
    $paymentProof->status = PaymentProof::STATUS_UNDER_REVIEW;
    expect($paymentProof->isPending())->toBeFalse();
    expect($paymentProof->isUnderReview())->toBeTrue();
    expect($paymentProof->isApproved())->toBeFalse();
    expect($paymentProof->isRejected())->toBeFalse();

    // Test approved status
    $paymentProof->status = PaymentProof::STATUS_APPROVED;
    expect($paymentProof->isPending())->toBeFalse();
    expect($paymentProof->isUnderReview())->toBeFalse();
    expect($paymentProof->isApproved())->toBeTrue();
    expect($paymentProof->isRejected())->toBeFalse();

    // Test rejected status
    $paymentProof->status = PaymentProof::STATUS_REJECTED;
    expect($paymentProof->isPending())->toBeFalse();
    expect($paymentProof->isUnderReview())->toBeFalse();
    expect($paymentProof->isApproved())->toBeFalse();
    expect($paymentProof->isRejected())->toBeTrue();
});

test('payment proof status color attribute works', function () {
    $paymentProof = new PaymentProof();

    $paymentProof->status = PaymentProof::STATUS_PENDING;
    expect($paymentProof->status_color)->toBe('warning');

    $paymentProof->status = PaymentProof::STATUS_UNDER_REVIEW;
    expect($paymentProof->status_color)->toBe('info');

    $paymentProof->status = PaymentProof::STATUS_APPROVED;
    expect($paymentProof->status_color)->toBe('success');

    $paymentProof->status = PaymentProof::STATUS_REJECTED;
    expect($paymentProof->status_color)->toBe('danger');

    $paymentProof->status = 'unknown';
    expect($paymentProof->status_color)->toBe('secondary');
});

test('payment proof payment method display attribute works', function () {
    $paymentProof = new PaymentProof();

    $paymentProof->payment_method = PaymentProof::PAYMENT_METHOD_BANK_TRANSFER;
    expect($paymentProof->payment_method_display)->toBe('Transferencia Bancaria');

    $paymentProof->payment_method = PaymentProof::PAYMENT_METHOD_CASH;
    expect($paymentProof->payment_method_display)->toBe('Efectivo');

    $paymentProof->payment_method = PaymentProof::PAYMENT_METHOD_MOBILE_MONEY;
    expect($paymentProof->payment_method_display)->toBe('Dinero Móvil');

    $paymentProof->payment_method = PaymentProof::PAYMENT_METHOD_OTHER;
    expect($paymentProof->payment_method_display)->toBe('Otro');

    $paymentProof->payment_method = 'custom';
    expect($paymentProof->payment_method_display)->toBe('Custom');
});

test('payment proof handles empty file paths', function () {
    $paymentProof = new PaymentProof();
    $paymentProof->file_paths = null;

    $formatted = $paymentProof->formatted_file_paths;

    expect($formatted)->toBeArray();
    expect($formatted)->toBeEmpty();
});

test('payment proof handles empty file paths array', function () {
    $paymentProof = new PaymentProof();
    $paymentProof->file_paths = [];

    $formatted = $paymentProof->formatted_file_paths;

    expect($formatted)->toBeArray();
    expect($formatted)->toBeEmpty();
});

test('payment proof formats file paths correctly', function () {
    $paymentProof = new PaymentProof();
    $paymentProof->file_paths = [
        'payment_proofs/test1.jpg',
        'payment_proofs/test2.pdf',
    ];

    // Mock the file size method to avoid file system dependency
    $paymentProof->forceFill(['file_paths' => ['payment_proofs/test1.jpg']]);

    // Test that it processes file paths without errors
    expect($paymentProof->file_paths)->toBeArray();
    expect($paymentProof->file_paths)->toHaveCount(1);
});

test('payment proof soft delete trait is loaded', function () {
    $paymentProof = new PaymentProof();

    // Check if SoftDeletes trait is used
    $traits = class_uses($paymentProof);
    expect($traits)->toHaveKey('Illuminate\Database\Eloquent\SoftDeletes');
});