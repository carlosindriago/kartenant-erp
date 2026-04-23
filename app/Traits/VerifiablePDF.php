<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Traits;

use App\Models\Tenant;
use App\Services\DocumentHashService;
use App\Services\QRCodeService;
use Illuminate\Support\Facades\Log;

trait VerifiablePDF
{
    protected ?DocumentHashService $hashService = null;

    protected ?QRCodeService $qrService = null;

    protected function getHashService(): DocumentHashService
    {
        if (! $this->hashService) {
            $this->hashService = app(DocumentHashService::class);
        }

        return $this->hashService;
    }

    protected function getQRService(): QRCodeService
    {
        if (! $this->qrService) {
            $this->qrService = app(QRCodeService::class);
        }

        return $this->qrService;
    }

    protected function generateVerification(
        array $content,
        string $documentType,
        ?Tenant $tenant = null,
        ?int $userId = null,
        ?array $metadata = null,
        ?\DateTime $expiresAt = null
    ): array {
        $hashService = $this->getHashService();
        $qrService = $this->getQRService();

        if ($metadata) {
            $metadata = $hashService->sanitizeMetadata($metadata);
        }

        $result = $hashService->generateAndRegister(
            $content,
            $documentType,
            $tenant,
            $userId,
            $metadata,
            $expiresAt
        );

        $qrSvg = $qrService->generateQRSVG($result['hash']);
        $qrBase64 = $qrService->generateQRBase64($result['hash']);
        $url = $qrService->getVerificationUrl($result['hash']);

        Log::info('Verification generated for PDF', [
            'document_type' => $documentType,
            'hash' => $result['hash'],
            'tenant_id' => $tenant?->id,
        ]);

        return [
            'hash' => $result['hash'],
            'qr_svg' => $qrSvg,
            'qr_base64' => $qrBase64,
            'url' => $url,
            'verification' => $result['verification'],
        ];
    }

    protected function getVerificationFooterHtml(string $hash, string $qrBase64): string
    {
        $url = $this->getQRService()->getVerificationUrl($hash);

        return view('components.pdf-verification-footer', [
            'hash' => $hash,
            'qr' => $qrBase64,
            'url' => $url,
        ])->render();
    }

    protected function getShortHash(string $hash, int $length = 12): string
    {
        return substr($hash, 0, $length).'...'.substr($hash, -$length);
    }

    /**
     * Obtener QR code en base64 para incluir en PDF
     */
    public function getVerificationQRCode(): ?string
    {
        if (! $this->verification_hash) {
            return null;
        }

        try {
            return $this->getQRService()->generateQRBase64($this->verification_hash);
        } catch (\Exception $e) {
            Log::error('Error generating QR code', [
                'model' => get_class($this),
                'id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener URL de verificación pública
     */
    public function getVerificationUrl(): ?string
    {
        if (! $this->verification_hash) {
            return null;
        }

        try {
            return $this->getQRService()->getVerificationUrl($this->verification_hash);
        } catch (\Exception $e) {
            Log::error('Error generating verification URL', [
                'model' => get_class($this),
                'id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generar hash de verificación para el documento actual
     */
    public function generateVerificationHash(): bool
    {
        try {
            // Este método debe ser implementado por cada modelo
            // que use el trait, definiendo su contenido específico
            if (method_exists($this, 'getVerificationContent')) {
                $content = $this->getVerificationContent();
                $documentType = $this->getVerificationDocumentType();
                $tenant = \Spatie\Multitenancy\Models\Tenant::current();
                $userId = $this->getUserIdForVerification();
                $metadata = $this->getVerificationMetadata();

                $result = $this->generateVerification(
                    $content,
                    $documentType,
                    $tenant,
                    $userId,
                    $metadata
                );

                $this->updateQuietly([
                    'verification_hash' => $result['hash'],
                    'verification_generated_at' => now(),
                ]);

                return true;
            }

            Log::warning('Model does not implement getVerificationContent method', [
                'model' => get_class($this),
                'id' => $this->id,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error generating verification hash', [
                'model' => get_class($this),
                'id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Métodos helper que pueden ser sobreescritos por los modelos
     */
    protected function getUserIdForVerification(): ?int
    {
        return $this->user_id ?? null;
    }

    protected function getVerificationMetadata(): array
    {
        return [];
    }
}
