<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BackupLog Model
 *
 * Tracks all backup operations for landlord and tenant databases
 * Stored in landlord database
 */
class BackupLog extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'tenant_id',
        'database_name',
        'status',
        'file_path',
        'file_size',
        'started_at',
        'completed_at',
        'error_message',
        'backup_type',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Relationship with Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if backup is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if backup was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if backup failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Scope: Get latest backup for a database
     */
    public function scopeLatestForDatabase($query, string $databaseName)
    {
        return $query->where('database_name', $databaseName)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Scope: Get latest successful backup for a database
     */
    public function scopeLatestSuccessfulForDatabase($query, string $databaseName)
    {
        return $query->where('database_name', $databaseName)
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Scope: Get failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get successful backups
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Get backups from today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
