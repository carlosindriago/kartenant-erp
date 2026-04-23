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

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BugReport extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $connection = 'landlord';

    protected $fillable = [
        'ticket_number',
        'severity',
        'title',
        'description',
        'steps_to_reproduce',
        'status',
        'priority',
        'reporter_name',
        'reporter_email',
        'reporter_user_id',
        'reporter_ip',
        'tenant_id',
        'tenant_name',
        'url',
        'user_agent',
        'screenshots',
        'file',
        'assigned_to',
        'internal_notes',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'screenshots' => 'array',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Generate unique ticket number
     */
    public static function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'BUG-'.$date;

        // Find last ticket number for today
        $lastTicket = DB::connection('landlord')
            ->table('bug_reports')
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderBy('ticket_number', 'desc')
            ->value('ticket_number');

        if ($lastTicket) {
            // Extract number and increment
            $lastNumber = (int) substr($lastTicket, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.'-'.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate ticket number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bugReport) {
            if (empty($bugReport->ticket_number)) {
                $bugReport->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Helper methods
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'waiting_feedback' => 'gray',
            'resolved' => 'success',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'waiting_feedback' => 'Esperando Feedback',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            default => 'Desconocido',
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'critical' => '🔴 Crítico',
            'high' => '🟠 Alto',
            'medium' => '🟡 Medio',
            'low' => '🟢 Bajo',
            default => 'Desconocido',
        };
    }
}
