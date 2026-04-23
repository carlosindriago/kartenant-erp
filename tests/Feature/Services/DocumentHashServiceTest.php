<?php

use App\Models\DocumentVerification;
use App\Services\DocumentHashService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Drop tables if they exist from other tests
    Schema::connection('landlord')->dropIfExists('document_verification_logs');
    Schema::connection('landlord')->dropIfExists('document_verifications');

    // Crear tablas manualmente para tests aislados
    Schema::connection('landlord')->create('document_verifications', function (Blueprint $table) {
        $table->id();
        $table->string('hash', 64)->unique();
        $table->string('document_type', 50);
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->unsignedBigInteger('generated_by')->nullable();
        $table->timestamp('generated_at');
        $table->json('metadata')->nullable();
        $table->unsignedInteger('verification_count')->default(0);
        $table->timestamp('last_verified_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->boolean('is_valid')->default(true);
        $table->timestamps();
    });

    Schema::connection('landlord')->create('document_verification_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('verification_id');
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamp('verified_at');
        $table->string('result', 20);
        $table->timestamps();
    });

    $this->hashService = app(DocumentHashService::class);
});

afterEach(function () {
    Schema::connection('landlord')->dropIfExists('document_verification_logs');
    Schema::connection('landlord')->dropIfExists('document_verifications');
});

test('genera hash SHA-256 correctamente', function () {
    $content = [
        'report_id' => 123,
        'total' => 10000,
        'items' => 5,
    ];

    $hash = $this->hashService->generateHash($content);

    expect($hash)
        ->toBeString()
        ->toHaveLength(64)
        ->toMatch('/^[a-f0-9]{64}$/');
});

test('genera el mismo hash para el mismo contenido', function () {
    $content = ['data' => 'test', 'value' => 123];

    $hash1 = $this->hashService->generateHash($content);
    $hash2 = $this->hashService->generateHash($content);

    expect($hash1)->toBe($hash2);
});

test('genera diferente hash para diferente contenido', function () {
    $content1 = ['data' => 'test1'];
    $content2 = ['data' => 'test2'];

    $hash1 = $this->hashService->generateHash($content1);
    $hash2 = $this->hashService->generateHash($content2);

    expect($hash1)->not->toBe($hash2);
});

test('crea registro de verificación en la base de datos', function () {
    $hash = 'a'.str_repeat('b', 63);

    $verification = $this->hashService->createVerification(
        $hash,
        'sale_report'
    );

    expect($verification)
        ->toBeInstanceOf(DocumentVerification::class)
        ->and($verification->hash)->toBe($hash)
        ->and($verification->document_type)->toBe('sale_report')
        ->and($verification->is_valid)->toBeTrue()
        ->and($verification->verification_count)->toBe(0);

    $this->assertDatabaseHas('document_verifications', [
        'hash' => $hash,
        'document_type' => 'sale_report',
    ]);
});

test('genera y registra verificación en un solo paso', function () {
    $content = ['report_id' => 456, 'total' => 5000];

    $result = $this->hashService->generateAndRegister(
        $content,
        'inventory_report'
    );

    expect($result)
        ->toHaveKeys(['hash', 'verification'])
        ->and($result['hash'])->toHaveLength(64)
        ->and($result['verification'])->toBeInstanceOf(DocumentVerification::class);

    $this->assertDatabaseHas('document_verifications', [
        'hash' => $result['hash'],
        'document_type' => 'inventory_report',
    ]);
});

test('verifica hash existente correctamente', function () {
    $content = ['test' => 'data'];
    $result = $this->hashService->generateAndRegister($content, 'test_report');

    $verification = $this->hashService->verifyHash(
        $result['hash'],
        '127.0.0.1',
        'Test Agent'
    );

    expect($verification)
        ->toHaveKeys(['result', 'message', 'verification'])
        ->and($verification['result'])->toBe('valid')
        ->and($verification['message'])->toContain('legítimo');
});

test('retorna not_found para hash inexistente', function () {
    $fakeHash = str_repeat('a', 64);

    $verification = $this->hashService->verifyHash($fakeHash);

    expect($verification)
        ->toHaveKey('result')
        ->and($verification['result'])->toBe('not_found')
        ->and($verification['verification'])->toBeNull();
});

test('incrementa contador de verificaciones', function () {
    $content = ['test' => 'data'];
    $result = $this->hashService->generateAndRegister($content, 'test_report');

    expect($result['verification']->verification_count)->toBe(0);

    $this->hashService->verifyHash($result['hash']);
    $result['verification']->refresh();
    expect($result['verification']->verification_count)->toBe(1);

    $this->hashService->verifyHash($result['hash']);
    $result['verification']->refresh();
    expect($result['verification']->verification_count)->toBe(2);
});

test('registra log de verificación con IP y user agent', function () {
    $content = ['test' => 'data'];
    $result = $this->hashService->generateAndRegister($content, 'test_report');

    $this->hashService->verifyHash(
        $result['hash'],
        '192.168.1.1',
        'Mozilla/5.0'
    );

    $this->assertDatabaseHas('document_verification_logs', [
        'verification_id' => $result['verification']->id,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'result' => 'valid',
    ]);
});

test('invalida documento correctamente', function () {
    $content = ['test' => 'data'];
    $result = $this->hashService->generateAndRegister($content, 'test_report');

    expect($result['verification']->is_valid)->toBeTrue();

    $success = $this->hashService->invalidateDocument($result['hash'], 'Documento corregido');

    expect($success)->toBeTrue();

    $result['verification']->refresh();
    expect($result['verification']->is_valid)->toBeFalse();

    $verification = $this->hashService->verifyHash($result['hash']);
    expect($verification['result'])->toBe('invalid');
});

test('sanitiza metadata correctamente', function () {
    $metadata = [
        'periodo' => 'Enero 2025',
        'items_count' => 10,
        'client_name' => 'Juan Pérez',
        'client_email' => 'juan@example.com',
        'amounts' => [100, 200],
        'totals' => 300,
    ];

    $sanitized = $this->hashService->sanitizeMetadata($metadata);

    expect($sanitized)
        ->toHaveKeys(['periodo', 'items_count'])
        ->not->toHaveKeys(['client_name', 'client_email', 'amounts', 'totals']);
});

test('genera URL de verificación correcta', function () {
    $hash = str_repeat('a', 64);
    $url = $this->hashService->getVerificationUrl($hash);

    expect($url)
        ->toBeString()
        ->toContain('/verify/')
        ->toContain($hash);
});

test('retorna estadísticas del sistema', function () {
    // Crear algunos documentos
    $this->hashService->generateAndRegister(['test' => '1'], 'report1');
    $this->hashService->generateAndRegister(['test' => '2'], 'report2');

    $stats = $this->hashService->getStatistics();

    expect($stats)
        ->toHaveKeys([
            'total_documents',
            'valid_documents',
            'invalid_documents',
            'expired_documents',
            'total_verifications',
            'verifications_today',
        ])
        ->and($stats['total_documents'])->toBeGreaterThanOrEqual(2)
        ->and($stats['valid_documents'])->toBeGreaterThanOrEqual(2);
});
