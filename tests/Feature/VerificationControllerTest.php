<?php

use App\Services\DocumentHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('muestra página de verificación', function () {
    $response = $this->get(route('verify.index'));

    $response->assertStatus(200);
    $response->assertSee('Verificar Documento');
    $response->assertSee('Código de Verificación');
});

test('verifica documento válido por hash', function () {
    $hashService = app(DocumentHashService::class);
    $result = $hashService->generateAndRegister(
        ['test' => 'data'],
        'test_report'
    );

    $response = $this->get(route('verify.hash', ['hash' => $result['hash']]));

    $response->assertStatus(200);
    $response->assertSee('Documento Legítimo');
    $response->assertSee('test_report');
});

test('muestra error para hash no encontrado', function () {
    $fakeHash = str_repeat('a', 64);

    $response = $this->get(route('verify.hash', ['hash' => $fakeHash]));

    $response->assertStatus(200);
    $response->assertSee('Documento No Verificable');
    $response->assertSee('no fue encontrado');
});

test('muestra error para hash con formato inválido', function () {
    $invalidHash = 'invalid-hash';

    $response = $this->get(route('verify.hash', ['hash' => $invalidHash]));

    $response->assertStatus(200);
    $response->assertSee('formato válido');
});

test('muestra documento expirado', function () {
    $hashService = app(DocumentHashService::class);
    $result = $hashService->generateAndRegister(
        ['test' => 'data'],
        'test_report',
        null,
        null,
        null,
        now()->subDay()
    );

    $response = $this->get(route('verify.hash', ['hash' => $result['hash']]));

    $response->assertStatus(200);
    $response->assertSee('Documento Expirado');
});

test('muestra documento invalidado', function () {
    $hashService = app(DocumentHashService::class);
    $result = $hashService->generateAndRegister(
        ['test' => 'data'],
        'test_report'
    );

    $hashService->invalidateDocument($result['hash'], 'Test invalidation');

    $response = $this->get(route('verify.hash', ['hash' => $result['hash']]));

    $response->assertStatus(200);
    $response->assertSee('Documento Invalidado');
});

test('API retorna datos correctos para hash válido', function () {
    $hashService = app(DocumentHashService::class);
    $result = $hashService->generateAndRegister(
        ['test' => 'data'],
        'test_report',
        null,
        null,
        ['periodo' => 'Enero 2025']
    );

    $response = $this->postJson(route('verify.api'), [
        'hash' => $result['hash'],
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'valid' => true,
        'result' => 'valid',
    ]);
    $response->assertJsonStructure([
        'valid',
        'result',
        'message',
        'document' => [
            'type',
            'generated_at',
            'verification_count',
            'is_valid',
            'is_expired',
            'metadata',
        ],
    ]);
});

test('API retorna 404 para hash no encontrado', function () {
    $fakeHash = str_repeat('a', 64);

    $response = $this->postJson(route('verify.api'), [
        'hash' => $fakeHash,
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'valid' => false,
        'result' => 'not_found',
    ]);
});

test('API valida formato de hash', function () {
    $response = $this->postJson(route('verify.api'), [
        'hash' => 'invalid',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['hash']);
});

test('incrementa contador de verificaciones al verificar', function () {
    $hashService = app(DocumentHashService::class);
    $result = $hashService->generateAndRegister(
        ['test' => 'data'],
        'test_report'
    );

    expect($result['verification']->verification_count)->toBe(0);

    $this->get(route('verify.hash', ['hash' => $result['hash']]));
    $result['verification']->refresh();

    expect($result['verification']->verification_count)->toBe(1);
});

test('registra IP y user agent en log de verificación', function () {
    $hashService = app(DocumentHashService::class);
    $result = $hashService->generateAndRegister(
        ['test' => 'data'],
        'test_report'
    );

    $this->withHeaders([
        'User-Agent' => 'Test Browser/1.0',
    ])->get(route('verify.hash', ['hash' => $result['hash']]));

    $this->assertDatabaseHas('document_verification_logs', [
        'verification_id' => $result['verification']->id,
        'user_agent' => 'Test Browser/1.0',
        'result' => 'valid',
    ]);
});
