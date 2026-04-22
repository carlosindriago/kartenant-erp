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

use App\Models\DocumentVerification;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class DocumentHashService
{
    /**
     * Genera hash SHA-256 del contenido del documento
     * 
     * @param array $content Contenido del documento a hashear
     * @return string Hash SHA-256 (64 caracteres hexadecimales)
     */
    public function generateHash(array $content): string
    {
        // Ordenar el array por claves para consistencia
        ksort($content);
        
        // Convertir a JSON canonizado
        $jsonContent = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Generar hash SHA-256
        $hash = hash('sha256', $jsonContent);
        
        Log::info('Hash generado', [
            'hash' => $hash,
            'content_keys' => array_keys($content),
        ]);
        
        return $hash;
    }
    
    /**
     * Crea un registro de verificación en la base de datos
     * 
     * @param string $hash Hash del documento
     * @param string $documentType Tipo de documento (sale_report, inventory_report, etc.)
     * @param Tenant|null $tenant Tenant que genera el documento
     * @param int|null $userId Usuario que genera el documento
     * @param array|null $metadata Metadata adicional (sanitizada)
     * @param \DateTime|null $expiresAt Fecha de expiración opcional
     * @return DocumentVerification
     */
    public function createVerification(
        string $hash,
        string $documentType,
        ?Tenant $tenant = null,
        ?int $userId = null,
        ?array $metadata = null,
        ?\DateTime $expiresAt = null
    ): DocumentVerification {
        $verification = DocumentVerification::create([
            'hash' => $hash,
            'document_type' => $documentType,
            'tenant_id' => $tenant?->id,
            'generated_by' => $userId,
            'generated_at' => now(),
            'metadata' => $metadata,
            'expires_at' => $expiresAt,
            'is_valid' => true,
            'verification_count' => 0,
        ]);
        
        Log::info('Verificación creada', [
            'hash' => $hash,
            'document_type' => $documentType,
            'tenant_id' => $tenant?->id,
            'verification_id' => $verification->id,
        ]);
        
        return $verification;
    }
    
    /**
     * Genera hash y crea registro de verificación en un solo paso
     * 
     * @param array $content Contenido del documento
     * @param string $documentType Tipo de documento
     * @param Tenant|null $tenant Tenant que genera
     * @param int|null $userId Usuario que genera
     * @param array|null $metadata Metadata adicional
     * @param \DateTime|null $expiresAt Fecha de expiración
     * @return array ['hash' => string, 'verification' => DocumentVerification]
     */
    public function generateAndRegister(
        array $content,
        string $documentType,
        ?Tenant $tenant = null,
        ?int $userId = null,
        ?array $metadata = null,
        ?\DateTime $expiresAt = null
    ): array {
        $hash = $this->generateHash($content);
        
        $verification = $this->createVerification(
            $hash,
            $documentType,
            $tenant,
            $userId,
            $metadata,
            $expiresAt
        );
        
        return [
            'hash' => $hash,
            'verification' => $verification,
        ];
    }
    
    /**
     * Verifica si un hash existe y es válido
     * 
     * @param string $hash Hash a verificar
     * @param string|null $ipAddress IP del verificador
     * @param string|null $userAgent User agent del verificador
     * @return array Resultado de la verificación
     */
    public function verifyHash(
        string $hash,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        // Buscar el hash en la base de datos
        $verification = DocumentVerification::where('hash', $hash)->first();
        
        if (!$verification) {
            Log::warning('Hash no encontrado', [
                'hash' => $hash,
                'ip' => $ipAddress,
            ]);
            
            return [
                'result' => 'not_found',
                'message' => 'Este documento no fue generado por el sistema o el código de verificación es incorrecto.',
                'verification' => null,
            ];
        }
        
        // Verificar el documento y registrar el intento
        $result = $verification->verify($ipAddress, $userAgent);
        
        Log::info('Verificación realizada', [
            'hash' => $hash,
            'result' => $result['result'],
            'ip' => $ipAddress,
        ]);
        
        return $result;
    }
    
    /**
     * Invalida un documento manualmente
     * 
     * @param string $hash Hash del documento a invalidar
     * @param string|null $reason Razón de la invalidación
     * @return bool True si se invalidó correctamente
     */
    public function invalidateDocument(string $hash, ?string $reason = null): bool
    {
        $verification = DocumentVerification::where('hash', $hash)->first();
        
        if (!$verification) {
            return false;
        }
        
        $verification->invalidate($reason);
        
        Log::warning('Documento invalidado', [
            'hash' => $hash,
            'reason' => $reason,
        ]);
        
        return true;
    }
    
    /**
     * Obtiene estadísticas de verificación
     * 
     * @return array Estadísticas generales
     */
    public function getStatistics(): array
    {
        return [
            'total_documents' => DocumentVerification::count(),
            'valid_documents' => DocumentVerification::valid()->count(),
            'invalid_documents' => DocumentVerification::where('is_valid', false)->count(),
            'expired_documents' => DocumentVerification::valid()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count(),
            'total_verifications' => DocumentVerification::sum('verification_count'),
            'verifications_today' => DocumentVerification::whereDate('last_verified_at', today())->count(),
        ];
    }
    
    /**
     * Sanitiza metadata para almacenamiento público
     * Remueve información sensible que no debe ser accesible públicamente
     * 
     * @param array $metadata Metadata original
     * @return array Metadata sanitizada
     */
    public function sanitizeMetadata(array $metadata): array
    {
        // Lista de campos sensibles a remover
        $sensitiveFields = [
            'client_name',
            'client_email',
            'client_phone',
            'client_address',
            'client_document',
            'amounts',
            'prices',
            'totals',
            'subtotals',
            'user_email',
            'user_phone',
            'payment_details',
            'account_numbers',
        ];
        
        $sanitized = $metadata;
        
        foreach ($sensitiveFields as $field) {
            unset($sanitized[$field]);
        }
        
        return $sanitized;
    }
    
    /**
     * Genera URL de verificación pública
     * 
     * @param string $hash Hash del documento
     * @return string URL completa de verificación
     */
    public function getVerificationUrl(string $hash): string
    {
        return url("/verify/{$hash}");
    }
    
    /**
     * Limpia documentos expirados antiguos (opcional, para mantenimiento)
     * 
     * @param int $daysOld Días de antigüedad para considerar "antiguos"
     * @return int Cantidad de documentos limpiados
     */
    public function cleanExpiredDocuments(int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $count = DocumentVerification::where('is_valid', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $cutoffDate)
            ->delete();
        
        Log::info('Documentos expirados limpiados', [
            'count' => $count,
            'cutoff_date' => $cutoffDate,
        ]);
        
        return $count;
    }
}
