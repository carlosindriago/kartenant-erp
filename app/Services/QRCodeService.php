<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Genera código QR para un hash de documento
     *
     * @param  string  $hash  Hash del documento
     * @param  int  $size  Tamaño del QR en píxeles (default: 200)
     * @param  string  $format  Formato: 'svg', 'png', 'eps', 'pdf' (default: 'svg')
     * @return string QR code generado
     */
    public function generateQR(string $hash, int $size = 200, string $format = 'svg'): string
    {
        $verificationUrl = $this->getVerificationUrl($hash);

        $qr = QrCode::format($format)
            ->size($size)
            ->errorCorrection('H') // Nivel alto de corrección de errores
            ->margin(1)
            ->generate($verificationUrl);

        return $qr;
    }

    /**
     * Genera código QR como imagen PNG en base64
     *
     * @param  string  $hash  Hash del documento
     * @param  int  $size  Tamaño en píxeles
     * @return string Base64 del PNG
     */
    public function generateQRBase64(string $hash, int $size = 200): string
    {
        $png = $this->generateQR($hash, $size, 'png');

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Genera código QR como SVG (recomendado para PDFs)
     *
     * @param  string  $hash  Hash del documento
     * @param  int  $size  Tamaño en píxeles
     * @return string SVG del QR
     */
    public function generateQRSVG(string $hash, int $size = 200): string
    {
        return $this->generateQR($hash, $size, 'svg');
    }

    /**
     * Genera código QR y lo guarda en un archivo
     *
     * @param  string  $hash  Hash del documento
     * @param  string  $path  Ruta donde guardar (relativa a storage/app)
     * @param  int  $size  Tamaño en píxeles
     * @param  string  $format  Formato del archivo
     * @return string Ruta completa del archivo guardado
     */
    public function generateAndSave(
        string $hash,
        string $path,
        int $size = 200,
        string $format = 'png'
    ): string {
        $verificationUrl = $this->getVerificationUrl($hash);

        $fullPath = storage_path('app/'.$path);

        // Crear directorio si no existe
        $dir = dirname($fullPath);
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        QrCode::format($format)
            ->size($size)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($verificationUrl, $fullPath);

        return $fullPath;
    }

    /**
     * Obtiene URL de verificación pública
     *
     * @param  string  $hash  Hash del documento
     * @return string URL completa
     */
    public function getVerificationUrl(string $hash): string
    {
        return url("/verify/{$hash}");
    }

    /**
     * Genera QR con logo personalizado (empresa)
     *
     * @param  string  $hash  Hash del documento
     * @param  string  $logoPath  Ruta al logo (public path)
     * @param  int  $size  Tamaño del QR
     * @return string QR code con logo
     */
    public function generateQRWithLogo(string $hash, string $logoPath, int $size = 200): string
    {
        $verificationUrl = $this->getVerificationUrl($hash);

        $qr = QrCode::format('png')
            ->size($size)
            ->errorCorrection('H') // Alto nivel requerido para logos
            ->margin(1)
            ->merge($logoPath, 0.3, true) // Logo ocupa 30% del QR
            ->generate($verificationUrl);

        return 'data:image/png;base64,'.base64_encode($qr);
    }

    /**
     * Genera HTML con QR embebido para emails o vistas
     *
     * @param  string  $hash  Hash del documento
     * @param  int  $size  Tamaño del QR
     * @param  string  $title  Título opcional
     * @return string HTML con QR
     */
    public function generateQRHtml(string $hash, int $size = 200, string $title = 'Verificar Documento'): string
    {
        $qrBase64 = $this->generateQRBase64($hash, $size);
        $verificationUrl = $this->getVerificationUrl($hash);

        return view('components.qr-verification', [
            'qr' => $qrBase64,
            'url' => $verificationUrl,
            'hash' => $hash,
            'title' => $title,
        ])->render();
    }

    /**
     * Genera QR optimizado para impresión
     * Alta resolución y formato vectorial
     *
     * @param  string  $hash  Hash del documento
     * @param  int  $size  Tamaño en píxeles (recomendado: 400-600 para impresión)
     * @return string SVG del QR (formato vectorial, escalable sin pérdida)
     */
    public function generateQRForPrint(string $hash, int $size = 500): string
    {
        return $this->generateQRSVG($hash, $size);
    }

    /**
     * Valida que un hash es válido para generar QR
     *
     * @param  string  $hash  Hash a validar
     */
    public function validateHash(string $hash): bool
    {
        // Hash SHA-256 debe tener 64 caracteres hexadecimales
        return preg_match('/^[a-f0-9]{64}$/', $hash) === 1;
    }
}
