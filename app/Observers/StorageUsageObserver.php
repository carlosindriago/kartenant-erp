<?php

namespace App\Observers;

use App\Services\TenantUsageService;
use Illuminate\Support\Facades\Storage;

class StorageUsageObserver
{
    public function __construct(
        private TenantUsageService $usageService
    ) {}

    /**
     * Handle file upload events
     */
    public function fileUploaded(string $path, int $size, ?int $tenantId = null): void
    {
        if (!$tenantId) {
            $tenantId = $this->getCurrentTenantId();
        }

        if (!$tenantId) {
            return;
        }

        // Track storage usage increment
        $this->usageService->updateStorageUsage($tenantId, $size, true);
    }

    /**
     * Handle file deletion events
     */
    public function fileDeleted(string $path, ?int $tenantId = null): void
    {
        if (!$tenantId) {
            $tenantId = $this->getCurrentTenantId();
        }

        if (!$tenantId) {
            return;
        }

        // Try to get file size from storage metadata
        $size = $this->getFileSize($path, $tenantId);

        if ($size > 0) {
            // Track storage usage decrement
            $this->usageService->updateStorageUsage($tenantId, $size, false);
        }
    }

    /**
     * Get current tenant ID from context
     */
    private function getCurrentTenantId(): ?int
    {
        return tenant()?->id;
    }

    /**
     * Get file size from storage
     */
    private function getFileSize(string $path, int $tenantId): int
    {
        try {
            // Try to get file size based on tenant storage configuration
            $disk = config('filesystems.tenant_disk', 'tenant');

            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->size($path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail
            logger()->warning('Could not get file size for storage tracking', [
                'path' => $path,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    /**
     * Recalculate total storage usage for tenant
     */
    public function recalculateStorageUsage(int $tenantId): void
    {
        try {
            $disk = config('filesystems.tenant_disk', 'tenant');
            $totalSize = 0;

            // Get tenant-specific storage path
            $tenantPath = "tenants/{$tenantId}";

            if (Storage::disk($disk)->exists($tenantPath)) {
                $files = Storage::disk($disk)->allFiles($tenantPath);

                foreach ($files as $file) {
                    $totalSize += Storage::disk($disk)->size($file);
                }
            }

            // Update usage with new total
            $this->usageService->incrementUsage(
                $tenantId,
                'storage_used',
                $totalSize,
                'manual',
                'Storage',
                null,
                [
                    'action' => 'recalculation',
                    'total_files' => count($files ?? []),
                ]
            );

        } catch (\Exception $e) {
            logger()->error('Failed to recalculate storage usage', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get storage usage breakdown for tenant
     */
    public function getStorageBreakdown(int $tenantId): array
    {
        try {
            $disk = config('filesystems.tenant_disk', 'tenant');
            $tenantPath = "tenants/{$tenantId}";
            $breakdown = [];

            if (!Storage::disk($disk)->exists($tenantPath)) {
                return $breakdown;
            }

            $files = Storage::disk($disk)->allFiles($tenantPath);
            $totalSize = 0;

            foreach ($files as $file) {
                $size = Storage::disk($disk)->size($file);
                $extension = pathinfo($file, PATHINFO_EXTENSION) ?: 'unknown';

                if (!isset($breakdown[$extension])) {
                    $breakdown[$extension] = [
                        'count' => 0,
                        'size' => 0,
                        'size_mb' => 0,
                    ];
                }

                $breakdown[$extension]['count']++;
                $breakdown[$extension]['size'] += $size;
                $totalSize += $size;
            }

            // Convert to MB and add percentages
            foreach ($breakdown as $extension => &$data) {
                $data['size_mb'] = round($data['size'] / 1024 / 1024, 2);
                $data['percentage'] = $totalSize > 0 ? round(($data['size'] / $totalSize) * 100, 2) : 0;
            }

            // Sort by size descending
            uasort($breakdown, function ($a, $b) {
                return $b['size'] <=> $a['size'];
            });

            return $breakdown;

        } catch (\Exception $e) {
            logger()->error('Failed to get storage breakdown', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}