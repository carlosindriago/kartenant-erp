<?php

use App\Services\QRCodeService;

beforeEach(function () {
    $this->qrService = app(QRCodeService::class);
    $this->testHash = str_repeat('a', 64);
});

test('genera QR en formato SVG', function () {
    $qr = $this->qrService->generateQRSVG($this->testHash);

    expect($qr)
        ->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

test('genera QR en formato base64', function () {
    $qr = $this->qrService->generateQRBase64($this->testHash);

    expect($qr)
        ->toBeString()
        ->toStartWith('data:image/png;base64,');
});

test('genera URL de verificación correcta', function () {
    $url = $this->qrService->getVerificationUrl($this->testHash);

    expect($url)
        ->toBeString()
        ->toContain('/verify/')
        ->toContain($this->testHash);
});

test('valida hash correctamente', function () {
    $validHash = str_repeat('a', 64);
    $invalidHash1 = 'invalid';
    $invalidHash2 = str_repeat('z', 63); // 63 caracteres
    $invalidHash3 = str_repeat('g', 64); // caracteres inválidos

    expect($this->qrService->validateHash($validHash))->toBeTrue();
    expect($this->qrService->validateHash($invalidHash1))->toBeFalse();
    expect($this->qrService->validateHash($invalidHash2))->toBeFalse();
    expect($this->qrService->validateHash($invalidHash3))->toBeFalse();
});

test('genera QR para impresión en alta resolución', function () {
    $qr = $this->qrService->generateQRForPrint($this->testHash, 600);

    expect($qr)
        ->toBeString()
        ->toContain('<svg');
});

test('genera y guarda QR en archivo', function () {
    $path = 'test-qr/test-qr.png';
    $fullPath = $this->qrService->generateAndSave($this->testHash, $path, 200, 'png');

    expect($fullPath)
        ->toBeString()
        ->toContain($path);

    expect(file_exists($fullPath))->toBeTrue();

    // Limpiar
    if (file_exists($fullPath)) {
        unlink($fullPath);
        rmdir(dirname($fullPath));
    }
});
