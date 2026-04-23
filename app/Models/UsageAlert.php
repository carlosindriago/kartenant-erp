<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsageAlert extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';

    protected $fillable = [
        'tenant_id',
        'tenant_usage_id',
        'alert_type',
        'metric_type',
        'current_value',
        'limit_value',
        'percentage',
        'delivery_channels',
        'delivery_status',
        'message',
        'metadata',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'delivery_channels' => 'array',
        'delivery_status' => 'array',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenantUsage(): BelongsTo
    {
        return $this->belongsTo(TenantUsage::class);
    }

    // Scopes
    public function scopeWarning($query)
    {
        return $query->where('alert_type', 'warning');
    }

    public function scopeOverdraft($query)
    {
        return $query->where('alert_type', 'overdraft');
    }

    public function scopeCritical($query)
    {
        return $query->where('alert_type', 'critical');
    }

    public function scopeEmail($query)
    {
        return $query->whereJsonContains('delivery_channels', 'email');
    }

    public function scopeSlack($query)
    {
        return $query->whereJsonContains('delivery_channels', 'slack');
    }

    public function scopeInApp($query)
    {
        return $query->whereJsonContains('delivery_channels', 'in_app');
    }

    public function scopeDelivered($query, ?string $channel = null)
    {
        if ($channel) {
            return $query->where("delivery_status->{$channel}", 'sent');
        }

        return $query->whereJsonContains('delivery_status', 'sent');
    }

    public function scopeFailed($query, ?string $channel = null)
    {
        if ($channel) {
            return $query->where("delivery_status->{$channel}", 'failed');
        }

        return $query->whereJsonContains('delivery_status', 'failed');
    }

    // Business logic
    public function isDelivered(): bool
    {
        return in_array('sent', $this->delivery_status);
    }

    public function isFailed(): bool
    {
        return in_array('failed', $this->delivery_status);
    }

    public function isPending(): bool
    {
        return ! $this->isDelivered() && ! $this->isFailed();
    }

    public function markDelivered(string $channel): void
    {
        $status = $this->delivery_status ?? [];
        $status[$channel] = 'sent';
        $this->delivery_status = $status;
        $this->saveQuietly();
    }

    public function markFailed(string $channel, ?string $reason = null): void
    {
        $status = $this->delivery_status ?? [];
        $status[$channel] = 'failed';
        $this->delivery_status = $status;

        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata["{$channel}_error"] = $reason;
            $this->metadata = $metadata;
        }

        $this->saveQuietly();
    }

    public function getSeverityLevel(): string
    {
        return match ($this->alert_type) {
            'warning' => 'medium',
            'overdraft' => 'high',
            'critical' => 'critical',
            default => 'low',
        };
    }

    public function getMetricDisplayName(): string
    {
        return match ($this->metric_type) {
            'sales' => 'Ventas Mensuales',
            'products' => 'Productos',
            'users' => 'Usuarios',
            'storage' => 'Almacenamiento',
            'overall' => 'Uso General',
            default => ucfirst($this->metric_type),
        };
    }

    public function generateMessage(): string
    {
        $metricName = $this->getMetricDisplayName();
        $current = number_format($this->current_value);
        $limit = number_format($this->limit_value);
        $percentage = number_format($this->percentage, 1);

        return match ($this->alert_type) {
            'warning' => "⚠️ **Advertencia de Uso**: {$metricName} está al {$percentage}% ({$current}/{$limit}). Acercándose al límite del plan.",
            'overdraft' => "🔴 **Exceso de Uso**: {$metricName} ha excedido el límite en un {$percentage}% ({$current}/{$limit}). Se requiere actualizar el plan.",
            'critical' => "🚨 **Uso Crítico**: {$metricName} ha excedido el límite en un {$percentage}% ({$current}/{$limit}). Funciones limitadas hasta actualizar el plan.",
            default => "Notificación de uso: {$metricName} está al {$percentage}% ({$current}/{$limit}).",
        };
    }
}
