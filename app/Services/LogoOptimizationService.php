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

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class LogoOptimizationService
{
    /**
     * Constantes de configuración
     */
    const MAX_FILE_SIZE = 2048; // KB (2MB)

    const MAX_WIDTH = 300; // pixels

    const MAX_HEIGHT = 100; // pixels

    const RECOMMENDED_WIDTH = 200;

    const RECOMMENDED_HEIGHT = 50;

    const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'];

    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'svg', 'webp'];

    /**
     * Valida y procesa el archivo de logo
     *
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function processLogo(UploadedFile $file, Tenant $tenant): array
    {
        // Validación 1: Tamaño de archivo
        $fileSizeKB = $file->getSize() / 1024;
        if ($fileSizeKB > self::MAX_FILE_SIZE) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'El archivo es demasiado grande. Máximo permitido: '.self::MAX_FILE_SIZE.' KB ('.round($fileSizeKB).' KB detectados)',
            ];
        }

        // Validación 2: Tipo de archivo
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        if (! in_array($mimeType, self::ALLOWED_MIMES) || ! in_array(strtolower($extension), self::ALLOWED_EXTENSIONS)) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Tipo de archivo no permitido. Formatos aceptados: '.implode(', ', self::ALLOWED_EXTENSIONS),
            ];
        }

        // SVG no necesita optimización, solo guardar
        if ($extension === 'svg') {
            return $this->saveSvgLogo($file, $tenant);
        }

        // Validación 3: Dimensiones de la imagen
        try {
            $image = Image::make($file);
            $width = $image->width();
            $height = $image->height();

            // Advertencia si las dimensiones no son las recomendadas
            $warnings = [];
            if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
                $warnings[] = 'La imagen será redimensionada a un máximo de '.self::MAX_WIDTH.'x'.self::MAX_HEIGHT.' px';
            }

            // Optimizar y guardar
            return $this->optimizeAndSave($image, $tenant, $extension, $warnings);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'path' => null,
                'message' => 'Error al procesar la imagen: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Optimiza y guarda la imagen
     *
     * @param  \Intervention\Image\Image  $image
     */
    protected function optimizeAndSave($image, Tenant $tenant, string $extension, array $warnings = []): array
    {
        // Redimensionar si excede las dimensiones máximas (manteniendo aspect ratio)
        if ($image->width() > self::MAX_WIDTH || $image->height() > self::MAX_HEIGHT) {
            $image->resize(self::MAX_WIDTH, self::MAX_HEIGHT, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize(); // Prevenir agrandamiento
            });
        }

        // Optimizar calidad
        $quality = 85; // Balance entre calidad y tamaño
        if ($extension === 'png') {
            // PNG usa compresión 0-9 (9 = máxima compresión)
            $image->encode('png', 9);
        } else {
            // JPG/WEBP usa calidad 0-100
            $image->encode($extension, $quality);
        }

        // Generar nombre único para el archivo
        $filename = $this->generateLogoFilename($tenant, $extension);
        $directory = 'logos/'.$tenant->id;

        // Eliminar logo anterior si existe
        $this->deleteOldLogo($tenant);

        // Guardar en storage/app/public/logos/{tenant_id}/
        $path = $directory.'/'.$filename;
        Storage::disk('public')->put($path, $image->encode());

        $message = 'Logo subido y optimizado correctamente';
        if (! empty($warnings)) {
            $message .= '. '.implode('. ', $warnings);
        }

        return [
            'success' => true,
            'path' => $path,
            'message' => $message,
            'dimensions' => [
                'width' => $image->width(),
                'height' => $image->height(),
            ],
            'size' => Storage::disk('public')->size($path),
        ];
    }

    /**
     * Guarda un logo SVG (no requiere optimización)
     */
    protected function saveSvgLogo(UploadedFile $file, Tenant $tenant): array
    {
        $filename = $this->generateLogoFilename($tenant, 'svg');
        $directory = 'logos/'.$tenant->id;

        // Eliminar logo anterior si existe
        $this->deleteOldLogo($tenant);

        // Guardar SVG
        $path = $directory.'/'.$filename;
        Storage::disk('public')->put($path, file_get_contents($file));

        return [
            'success' => true,
            'path' => $path,
            'message' => 'Logo SVG subido correctamente',
            'size' => Storage::disk('public')->size($path),
        ];
    }

    /**
     * Genera un nombre único para el archivo de logo
     */
    protected function generateLogoFilename(Tenant $tenant, string $extension): string
    {
        $slug = Str::slug($tenant->domain);
        $timestamp = now()->format('YmdHis');

        return "{$slug}-{$timestamp}.{$extension}";
    }

    /**
     * Elimina el logo anterior del tenant
     */
    protected function deleteOldLogo(Tenant $tenant): bool
    {
        if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
            return Storage::disk('public')->delete($tenant->logo_path);
        }

        return true;
    }

    /**
     * Elimina el logo del tenant
     */
    public function deleteLogo(Tenant $tenant): bool
    {
        $deleted = $this->deleteOldLogo($tenant);

        if ($deleted) {
            $tenant->update([
                'logo_path' => null,
                'logo_type' => 'text',
            ]);
        }

        return $deleted;
    }

    /**
     * Obtiene las reglas de validación para el campo de logo
     */
    public static function getValidationRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
            'max:'.self::MAX_FILE_SIZE,
            'dimensions:max_width='.self::MAX_WIDTH.',max_height='.self::MAX_HEIGHT,
        ];
    }

    /**
     * Obtiene información sobre las restricciones del logo
     */
    public static function getRestrictions(): array
    {
        return [
            'max_size' => self::MAX_FILE_SIZE.' KB',
            'max_dimensions' => self::MAX_WIDTH.' x '.self::MAX_HEIGHT.' px',
            'recommended_dimensions' => self::RECOMMENDED_WIDTH.' x '.self::RECOMMENDED_HEIGHT.' px',
            'allowed_formats' => implode(', ', self::ALLOWED_EXTENSIONS),
        ];
    }
}
