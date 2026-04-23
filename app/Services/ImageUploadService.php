<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Throwable;

class ImageUploadService
{
    private const MAX_LOGO_SIZE = 2048; // 2MB in KB

    private const MAX_BACKGROUND_SIZE = 5120; // 5MB in KB

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Upload and process logo image.
     */
    public function uploadLogo(UploadedFile $file, Tenant $tenant): string
    {
        Log::info('Starting logo upload', [
            'tenant_id' => $tenant->id,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        $this->validateImage($file, 'logo');

        $processedImage = $this->processImage($file, 'logo');
        $filename = $this->generateSecureFilename($file, 'logo');
        $path = $this->getStoragePath('logo', $tenant);

        // Store the processed image
        $fullPath = $path.'/'.$filename;
        Storage::disk('tenant_uploads')->put($fullPath, $processedImage);

        Log::info('Logo uploaded successfully', [
            'tenant_id' => $tenant->id,
            'path' => $fullPath,
            'size' => strlen($processedImage),
        ]);

        return $fullPath;
    }

    /**
     * Upload and process background image.
     */
    public function uploadBackground(UploadedFile $file, Tenant $tenant): string
    {
        Log::info('Starting background upload', [
            'tenant_id' => $tenant->id,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        $this->validateImage($file, 'background');

        $processedImage = $this->processImage($file, 'background');
        $filename = $this->generateSecureFilename($file, 'background');
        $path = $this->getStoragePath('background', $tenant);

        // Store the processed image
        $fullPath = $path.'/'.$filename;
        Storage::disk('tenant_uploads')->put($fullPath, $processedImage);

        Log::info('Background uploaded successfully', [
            'tenant_id' => $tenant->id,
            'path' => $fullPath,
            'size' => strlen($processedImage),
        ]);

        return $fullPath;
    }

    /**
     * Delete an image file.
     */
    public function deleteImage(string $path, Tenant $tenant): bool
    {
        try {
            // Verify the file belongs to the tenant
            if (! $this->isTenantFile($path, $tenant)) {
                Log::warning('Attempted to delete file from different tenant', [
                    'tenant_id' => $tenant->id,
                    'path' => $path,
                ]);

                return false;
            }

            $exists = Storage::disk('tenant_uploads')->exists($path);

            if ($exists) {
                Storage::disk('tenant_uploads')->delete($path);

                Log::info('Image deleted successfully', [
                    'tenant_id' => $tenant->id,
                    'path' => $path,
                ]);

                return true;
            }

            Log::warning('File not found for deletion', [
                'tenant_id' => $tenant->id,
                'path' => $path,
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('Error deleting image', [
                'tenant_id' => $tenant->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate image file type and size.
     */
    private function validateImage(UploadedFile $file, string $type): void
    {
        // Check file size
        $maxSize = $type === 'logo' ? self::MAX_LOGO_SIZE * 1024 : self::MAX_BACKGROUND_SIZE * 1024;

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                'El archivo es demasiado grande. Máximo permitido: '.
                ($maxSize / 1024 / 1024).'MB'
            );
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \InvalidArgumentException(
                'Formato de archivo no permitido. Use: '.implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // Check MIME type
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException(
                'Tipo de archivo no válido. Solo se permiten imágenes.'
            );
        }

        // Check magic numbers (additional security)
        $this->validateFileSignature($file);

        // Scan for malware
        $this->scanForMalware($file);
    }

    /**
     * Validate file signature to prevent disguised files.
     */
    private function validateFileSignature(UploadedFile $file): void
    {
        $handle = fopen($file->getPathname(), 'rb');
        $signature = fread($handle, 12);
        fclose($handle);

        $signatures = [
            'jpeg' => [0xFF, 0xD8, 0xFF],
            'png' => [0x89, 0x50, 0x4E, 0x47],
            'webp' => [0x52, 0x49, 0x46, 0x46],
        ];

        $isValid = false;
        foreach ($signatures as $format => $bytes) {
            $sigString = '';
            foreach ($bytes as $byte) {
                $sigString .= chr($byte);
            }

            if (str_starts_with($signature, $sigString)) {
                $isValid = true;
                break;
            }
        }

        if (! $isValid) {
            Log::warning('Invalid file signature detected', [
                'file' => $file->getClientOriginalName(),
                'signature' => bin2hex($signature),
            ]);

            throw new \InvalidArgumentException('El archivo no parece ser una imagen válida.');
        }
    }

    /**
     * Scan file for malware using available methods.
     */
    private function scanForMalware(UploadedFile $file): void
    {
        // Try ClamAV if available
        if (extension_loaded('clamav')) {
            $result = cl_scanfile($file->getPathname());

            if ($result !== CL_CLEAN) {
                Log::error('Malware detected in uploaded file', [
                    'file' => $file->getClientOriginalName(),
                    'result' => $result,
                ]);

                throw new \RuntimeException('El archivo ha sido bloqueado por razones de seguridad.');
            }

            Log::info('File passed ClamAV scan', [
                'file' => $file->getClientOriginalName(),
            ]);

            return;
        }

        // Fallback: Basic security checks
        $this->performBasicSecurityScan($file);
    }

    /**
     * Basic security scan when ClamAV is not available.
     */
    private function performBasicSecurityScan(UploadedFile $file): void
    {
        $content = file_get_contents($file->getPathname());

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '<?php',
            '<script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror=',
            'eval(',
            'exec(',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                Log::warning('Suspicious pattern detected in uploaded file', [
                    'file' => $file->getClientOriginalName(),
                    'pattern' => $pattern,
                ]);

                throw new \RuntimeException('El archivo contiene contenido sospechoso y ha sido bloqueado.');
            }
        }

        Log::info('File passed basic security scan', [
            'file' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Process image: resize, compress, and optimize.
     */
    private function processImage(UploadedFile $file, string $type): string
    {
        try {
            $image = Image::make($file->getPathname());

            // Validate that it's actually an image
            if (! $image->width() || ! $image->height()) {
                throw new \InvalidArgumentException('El archivo no es una imagen válida.');
            }

            if ($type === 'logo') {
                // Resize logo to max 400x400 maintaining aspect ratio
                $image->resize(400, 400, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $quality = 90;
            } else {
                // Resize background to max 1920x1080 maintaining aspect ratio
                $image->resize(1920, 1080, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $quality = 85;
            }

            // Auto-orient image based on EXIF data
            $image->orientate();

            // Strip metadata for privacy
            $image->encode('webp', $quality);

            Log::info('Image processed successfully', [
                'type' => $type,
                'original_size' => $file->getSize(),
                'processed_size' => strlen($image->encoded),
                'dimensions' => $image->width().'x'.$image->height(),
            ]);

            return $image->encoded;

        } catch (Throwable $e) {
            Log::error('Error processing image', [
                'file' => $file->getClientOriginalName(),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Error al procesar la imagen: '.$e->getMessage());
        }
    }

    /**
     * Generate secure filename.
     */
    private function generateSecureFilename(UploadedFile $file, string $type): string
    {
        $tenantId = tenant()->id;
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "{$type}_{$tenantId}_{$timestamp}_{$random}.webp";
    }

    /**
     * Get storage path for tenant images.
     */
    private function getStoragePath(string $type, Tenant $tenant): string
    {
        return "tenants/{$tenant->id}/store-settings/{$type}s";
    }

    /**
     * Check if file belongs to the specified tenant.
     */
    private function isTenantFile(string $path, Tenant $tenant): bool
    {
        return str_starts_with($path, "tenants/{$tenant->id}/");
    }

    /**
     * Get public URL for stored image.
     */
    public function getPublicUrl(string $path): string
    {
        return Storage::disk('tenant_uploads')->url($path);
    }

    /**
     * Get storage usage statistics for tenant.
     */
    public function getStorageUsage(Tenant $tenant): array
    {
        $basePath = "tenants/{$tenant->id}/";

        try {
            $files = Storage::disk('tenant_uploads')->allFiles($basePath);
            $totalSize = 0;
            $fileCount = 0;

            foreach ($files as $file) {
                $totalSize += Storage::disk('tenant_uploads')->size($file);
                $fileCount++;
            }

            return [
                'total_size' => $totalSize,
                'file_count' => $fileCount,
                'total_size_human' => $this->formatBytes($totalSize),
            ];
        } catch (Throwable $e) {
            Log::error('Error calculating storage usage', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_size' => 0,
                'file_count' => 0,
                'total_size_human' => '0 B',
            ];
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
