<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trait HasInternalVerification
 *
 * Proporciona funcionalidad de verificación interna para documentos
 * que requieren autenticación y permisos específicos para ser verificados.
 *
 * Características:
 * - Requiere autenticación para verificar
 * - Valida permisos específicos del usuario
 * - Registra auditoría de accesos en activities
 * - Soporta verificación por roles (gerente, supervisor, admin)
 * - Genera QR codes y PDFs con verificación
 */
trait HasInternalVerification
{
    /**
     * Define si este documento puede ser verificado públicamente
     * Los documentos internos siempre retornan false
     */
    public function isPubliclyVerifiable(): bool
    {
        return false;
    }

    /**
     * Asegura que el documento tenga un hash de verificación
     * Si no existe, lo genera
     */
    public function ensureVerificationHash(): void
    {
        if (! $this->verification_hash) {
            $this->verification_hash = $this->generateVerificationHash();
            $this->verification_generated_at = now();
            $this->saveQuietly();
        }
    }

    /**
     * Genera un hash único para este documento
     */
    protected function generateVerificationHash(): string
    {
        return hash('sha256', implode('|', [
            get_class($this),
            $this->id ?? time(),
            $this->created_at ?? now(),
            config('app.key'),
            random_bytes(16),
        ]));
    }

    /**
     * Genera el PDF del documento
     * Debe ser implementado por cada modelo
     */
    abstract public function generatePdf(): \Barryvdh\DomPDF\PDF;

    /**
     * Obtiene el nombre del documento para mostrar
     * Debe ser implementado por cada modelo
     */
    abstract public function getDocumentName(): string;

    /**
     * Descarga el PDF del documento
     */
    public function downloadPdf()
    {
        $this->ensureVerificationHash();

        $pdf = $this->generatePdf();
        $filename = str_replace(' ', '-', strtolower($this->getDocumentName())).'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Obtiene el permiso requerido para verificar este documento
     * Debe ser sobrescrito en cada modelo
     */
    abstract public function getVerificationPermission(): string;

    /**
     * Verifica si un usuario puede verificar este documento
     */
    public function canBeVerifiedBy(User $user): bool
    {
        // Verificar si el usuario tiene el permiso específico
        $permission = $this->getVerificationPermission();

        if ($user->hasPermissionTo($permission)) {
            return true;
        }

        // Verificar si el usuario es gerente/supervisor/admin del tenant
        if ($user->hasAnyRole(['admin', 'gerente', 'supervisor'])) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene la ruta de verificación interna
     */
    public function getInternalVerificationRoute(): string
    {
        if (! $this->verification_hash) {
            return '';
        }

        // Obtener el tenant actual
        $tenant = \Spatie\Multitenancy\Models\Tenant::current();

        if (! $tenant) {
            \Log::warning('No hay tenant actual al generar URL de verificación interna', [
                'model' => get_class($this),
                'id' => $this->id ?? null,
            ]);

            return '';
        }

        // Construir el dominio completo del tenant
        $tenantDomain = $tenant->domain;

        // Si el dominio del tenant no contiene un punto, es un subdominio
        // Necesitamos agregar el dominio base de la aplicación
        if (strpos($tenantDomain, '.') === false) {
            // Obtener el dominio base desde la configuración o desde el request actual
            $appUrl = config('app.url');
            $parsedUrl = parse_url($appUrl);
            $baseDomain = $parsedUrl['host'] ?? request()->getHost();

            // Si baseDomain también es solo un subdominio (por ejemplo, del request actual),
            // extraer el dominio principal
            $hostParts = explode('.', $baseDomain);
            if (count($hostParts) >= 2) {
                // Tomar las últimas 2 partes (ej: kartenant.test)
                $baseDomain = implode('.', array_slice($hostParts, -2));
            }

            $fullDomain = "{$tenantDomain}.{$baseDomain}";
        } else {
            // El dominio ya es completo
            $fullDomain = $tenantDomain;
        }

        $protocol = config('app.env') === 'production' ? 'https' : request()->getScheme();

        return "{$protocol}://{$fullDomain}/app/internal-verify/{$this->verification_hash}";
    }

    /**
     * Registra el acceso de verificación en activities
     */
    public function logVerificationAccess(User $user): void
    {
        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->withProperties([
                'action' => 'internal_verification_access',
                'verification_hash' => $this->verification_hash,
                'document_type' => get_class($this),
                'document_id' => $this->id,
                'user_role' => $user->roles->pluck('name')->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Documento verificado internamente');
    }

    /**
     * Obtiene el historial de verificaciones de este documento
     */
    public function getVerificationAuditTrail(): Collection
    {
        return DB::table('activity_log')
            ->where('subject_type', get_class($this))
            ->where('subject_id', $this->id)
            ->where('description', 'Documento verificado internamente')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activity) {
                $properties = json_decode($activity->properties, true);

                return [
                    'verified_at' => $activity->created_at,
                    'verified_by_id' => $activity->causer_id,
                    'verified_by_name' => $this->getUserName($activity->causer_id),
                    'user_role' => $properties['user_role'] ?? [],
                    'ip_address' => $properties['ip_address'] ?? null,
                    'user_agent' => $properties['user_agent'] ?? null,
                ];
            });
    }

    /**
     * Obtiene el nombre del usuario que verificó
     */
    private function getUserName(?int $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        return $user ? $user->name : 'Usuario eliminado';
    }

    /**
     * Obtiene el tipo de formato de PDF configurado
     * Por defecto: térmico 80mm
     */
    public function getPdfFormat(): string
    {
        // Puede ser sobrescrito en cada modelo o configurado por tenant
        return $this->pdf_format ?? 'thermal'; // 'thermal' o 'a4'
    }

    /**
     * Obtiene el QR code para verificación interna en formato data URI
     * Alias para compatibilidad con PDFs
     */
    public function getQrCodeDataUri(): ?string
    {
        return $this->getInternalVerificationQRCode();
    }

    /**
     * Obtiene el QR code para verificación interna
     * Similar al público pero con mensaje diferente
     */
    public function getInternalVerificationQRCode(): ?string
    {
        if (! $this->verification_hash) {
            return null;
        }

        $url = $this->getInternalVerificationRoute();

        try {
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(200)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($url);

            return 'data:image/png;base64,'.base64_encode($qrCode);
        } catch (\Exception $e) {
            \Log::error('Error generating internal verification QR code', [
                'error' => $e->getMessage(),
                'model' => get_class($this),
                'id' => $this->id,
            ]);

            return null;
        }
    }

    /**
     * Cuenta cuántas veces ha sido verificado este documento
     */
    public function getVerificationCount(): int
    {
        return DB::table('activity_log')
            ->where('subject_type', get_class($this))
            ->where('subject_id', $this->id)
            ->where('description', 'Documento verificado internamente')
            ->count();
    }

    /**
     * Obtiene la última verificación
     */
    public function getLastVerification(): ?array
    {
        $verifications = $this->getVerificationAuditTrail();

        return $verifications->first();
    }

    /**
     * Verifica si este documento ya fue verificado por un usuario específico
     */
    public function wasVerifiedBy(User $user): bool
    {
        return DB::table('activity_log')
            ->where('subject_type', get_class($this))
            ->where('subject_id', $this->id)
            ->where('causer_id', $user->id)
            ->where('description', 'Documento verificado internamente')
            ->exists();
    }
}
