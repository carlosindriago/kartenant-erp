<?php

use App\Models\DocumentVerification;
use App\Models\DocumentVerificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('crea documento de verificación correctamente', function () {
    $verification = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test_report',
        'generated_at' => now(),
        'is_valid' => true,
    ]);
    
    expect($verification)->toBeInstanceOf(DocumentVerification::class);
    expect($verification->hash)->toHaveLength(64);
    expect($verification->is_valid)->toBeTrue();
});

test('scope valid filtra solo documentos válidos', function () {
    DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => true,
    ]);
    
    DocumentVerification::create([
        'hash' => str_repeat('b', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => false,
    ]);
    
    $valid = DocumentVerification::valid()->get();
    
    expect($valid)->toHaveCount(1);
    expect($valid->first()->hash)->toBe(str_repeat('a', 64));
});

test('scope byType filtra por tipo de documento', function () {
    DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'sale_report',
        'generated_at' => now(),
    ]);
    
    DocumentVerification::create([
        'hash' => str_repeat('b', 64),
        'document_type' => 'inventory_report',
        'generated_at' => now(),
    ]);
    
    $sales = DocumentVerification::byType('sale_report')->get();
    
    expect($sales)->toHaveCount(1);
    expect($sales->first()->document_type)->toBe('sale_report');
});

test('scope notExpired filtra documentos no expirados', function () {
    DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
    
    DocumentVerification::create([
        'hash' => str_repeat('b', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'expires_at' => now()->subDay(),
    ]);
    
    $notExpired = DocumentVerification::notExpired()->get();
    
    expect($notExpired)->toHaveCount(1);
});

test('isExpired retorna true para documentos expirados', function () {
    $verification = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'expires_at' => now()->subDay(),
    ]);
    
    expect($verification->isExpired())->toBeTrue();
});

test('isExpired retorna false para documentos sin expiración', function () {
    $verification = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'expires_at' => null,
    ]);
    
    expect($verification->isExpired())->toBeFalse();
});

test('isCurrentlyValid verifica estado completo', function () {
    $valid = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => true,
        'expires_at' => now()->addDay(),
    ]);
    
    $invalid = DocumentVerification::create([
        'hash' => str_repeat('b', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => false,
    ]);
    
    $expired = DocumentVerification::create([
        'hash' => str_repeat('c', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => true,
        'expires_at' => now()->subDay(),
    ]);
    
    expect($valid->isCurrentlyValid())->toBeTrue();
    expect($invalid->isCurrentlyValid())->toBeFalse();
    expect($expired->isCurrentlyValid())->toBeFalse();
});

test('verify incrementa contador y crea log', function () {
    $verification = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => true,
        'verification_count' => 0,
    ]);
    
    $result = $verification->verify('127.0.0.1', 'Test Agent');
    
    expect($result['result'])->toBe('valid');
    
    $verification->refresh();
    expect($verification->verification_count)->toBe(1);
    expect($verification->verificationLogs)->toHaveCount(1);
});

test('invalidate marca documento como inválido', function () {
    $verification = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'is_valid' => true,
    ]);
    
    $verification->invalidate('Test reason');
    
    expect($verification->is_valid)->toBeFalse();
    expect($verification->metadata['invalidation_reason'])->toBe('Test reason');
});

test('getSanitizedMetadata remueve datos sensibles', function () {
    $verification = DocumentVerification::create([
        'hash' => str_repeat('a', 64),
        'document_type' => 'test',
        'generated_at' => now(),
        'metadata' => [
            'periodo' => 'Enero 2025',
            'items_count' => 10,
            'client_name' => 'Test Client',
            'client_email' => 'test@example.com',
            'amounts' => [100, 200],
        ],
    ]);
    
    $sanitized = $verification->getSanitizedMetadata();
    
    expect($sanitized)
        ->toHaveKeys(['periodo', 'items_count'])
        ->not->toHaveKeys(['client_name', 'client_email', 'amounts']);
});
