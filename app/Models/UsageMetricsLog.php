<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageMetricsLog extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'tenant_id',
        'metric_type',
        'entity_type',
        'entity_id',
        'value',
        'source',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeSales($query)
    {
        return $query->where('metric_type', 'sale_created');
    }

    public function scopeProducts($query)
    {
        return $query->where('metric_type', 'product_created');
    }

    public function scopeUsers($query)
    {
        return $query->where('metric_type', 'user_created');
    }

    public function scopeStorage($query)
    {
        return $query->where('metric_type', 'storage_used');
    }

    public function scopeSystem($query)
    {
        return $query->where('source', 'system');
    }

    public function scopeObserver($query)
    {
        return $query->where('source', 'observer');
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->forPeriod(now()->year, now()->month);
    }

    public function scopeLastHours($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeLastDays($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Utility methods
    public static function logUsage(
        int $tenantId,
        string $metricType,
        int $value = 1,
        string $source = 'system',
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): self {
        return static::create([
            'tenant_id' => $tenantId,
            'metric_type' => $metricType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'value' => $value,
            'source' => $source,
            'metadata' => $metadata,
        ]);
    }

    public static function getUsageSummary(int $tenantId, int $year, int $month): array
    {
        return [
            'sales' => static::where('tenant_id', $tenantId)
                ->forPeriod($year, $month)
                ->sales()
                ->sum('value'),

            'products' => static::where('tenant_id', $tenantId)
                ->forPeriod($year, $month)
                ->products()
                ->count('entity_id'), // Count unique products

            'users' => static::where('tenant_id', $tenantId)
                ->forPeriod($year, $month)
                ->users()
                ->count('entity_id'), // Count unique users

            'storage' => static::where('tenant_id', $tenantId)
                ->forPeriod($year, $month)
                ->storage()
                ->sum('value'),
        ];
    }

    public static function getRealtimeCount(int $tenantId, string $metricType, int $hours = 1): int
    {
        return static::where('tenant_id', $tenantId)
            ->where('metric_type', $metricType)
            ->lastHours($hours)
            ->sum('value');
    }

    public static function getTopTenantsByMetric(string $metricType, int $days = 30, int $limit = 10): array
    {
        return static::where('metric_type', $metricType)
            ->lastDays($days)
            ->selectRaw('tenant_id, SUM(value) as total_usage')
            ->groupBy('tenant_id')
            ->orderByDesc('total_usage')
            ->limit($limit)
            ->with('tenant:id,name')
            ->get()
            ->toArray();
    }

    public function getMetricDisplayName(): string
    {
        return match ($this->metric_type) {
            'sale_created' => 'Venta Registrada',
            'product_created' => 'Producto Creado',
            'user_created' => 'Usuario Creado',
            'storage_used' => 'Uso de Almacenamiento',
            default => ucfirst(str_replace('_', ' ', $this->metric_type)),
        };
    }

    public function getSourceDisplayName(): string
    {
        return match ($this->source) {
            'system' => 'Sistema',
            'observer' => 'Automático',
            'manual' => 'Manual',
            'migration' => 'Migración',
            'api' => 'API',
            default => ucfirst($this->source),
        };
    }

    public function isHighValue(): bool
    {
        return $this->value > 1; // Any increment > 1 is considered high value
    }

    public function getFormattedValue(): string
    {
        if ($this->metric_type === 'storage_used') {
            // Convert bytes to MB for display
            return number_format($this->value / 1024 / 1024, 2).' MB';
        }

        return number_format($this->value);
    }
}
