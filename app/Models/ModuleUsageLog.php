<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleUsageLog extends Model
{
    protected $connection = 'landlord';

    protected $table = 'module_usage_logs';

    protected $fillable = [
        'tenant_id',
        'module_id',
        'user_id',
        'event_type',
        'feature_accessed',
        'action_performed',
        'description',
        'usage_data',
        'limits_data',
        'usage_cost',
        'execution_time_ms',
        'memory_usage_mb',
        'performance_metrics',
        'was_error',
        'error_code',
        'error_message',
        'error_details',
        'ip_address',
        'user_agent',
        'request_path',
        'request_data',
    ];

    protected $casts = [
        'usage_data' => 'array',
        'limits_data' => 'array',
        'usage_cost' => 'decimal:4',
        'execution_time_ms' => 'integer',
        'memory_usage_mb' => 'integer',
        'performance_metrics' => 'array',
        'was_error' => 'boolean',
        'error_details' => 'array',
        'request_data' => 'array',
    ];

    public $timestamps = true;

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Event Type Constants
    public const EVENT_ACCESS = 'access';

    public const EVENT_ACTION = 'action';

    public const EVENT_ERROR = 'error';

    public const EVENT_LIMIT_REACHED = 'limit_reached';

    public const EVENT_INSTALL = 'install';

    public const EVENT_UNINSTALL = 'uninstall';

    public const EVENT_CONFIGURATION = 'configuration';

    public const EVENT_USAGE = 'usage';

    // Helper Methods
    public function getEventLabel(): string
    {
        return match ($this->event_type) {
            self::EVENT_ACCESS => 'Acceso',
            self::EVENT_ACTION => 'Acción',
            self::EVENT_ERROR => 'Error',
            self::EVENT_LIMIT_REACHED => 'Límite Alcanzado',
            self::EVENT_INSTALL => 'Instalación',
            self::EVENT_UNINSTALL => 'Desinstalación',
            self::EVENT_CONFIGURATION => 'Configuración',
            self::EVENT_USAGE => 'Uso',
            default => ucfirst($this->event_type),
        };
    }

    public function isError(): bool
    {
        return $this->was_error;
    }

    public function getFormattedExecutionTime(): string
    {
        if (! $this->execution_time_ms) {
            return 'N/A';
        }

        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms.' ms';
        }

        return number_format($this->execution_time_ms / 1000, 2).' s';
    }

    public function getFormattedMemoryUsage(): string
    {
        if (! $this->memory_usage_mb) {
            return 'N/A';
        }

        return $this->memory_usage_mb.' MB';
    }

    public function getFormattedCost(): string
    {
        if (! $this->usage_cost) {
            return '$0.0000';
        }

        return '$'.number_format($this->usage_cost, 4);
    }

    public function hasFeatureAccessed(): bool
    {
        return ! empty($this->feature_accessed);
    }

    public function hasActionPerformed(): bool
    {
        return ! empty($this->action_performed);
    }

    public function hasUsageData(): bool
    {
        return ! empty($this->usage_data);
    }

    public function getUsageMetric(string $metric): mixed
    {
        return data_get($this->usage_data, $metric);
    }

    public function hasPerformanceData(): bool
    {
        return ! empty($this->performance_metrics);
    }

    public function getPerformanceMetric(string $metric): mixed
    {
        return data_get($this->performance_metrics, $metric);
    }

    // Static Methods for Analytics
    public static function logAccess(
        Tenant $tenant,
        Module $module,
        ?User $user = null,
        ?string $feature = null,
        array $data = []
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_ACCESS,
            'feature_accessed' => $feature,
            'description' => "Acceso al módulo {$module->name}".($feature ? " - {$feature}" : ''),
            'usage_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }

    public static function logAction(
        Tenant $tenant,
        Module $module,
        string $action,
        ?User $user = null,
        ?string $feature = null,
        array $data = []
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_ACTION,
            'feature_accessed' => $feature,
            'action_performed' => $action,
            'description' => "Acción '{$action}' en módulo {$module->name}",
            'usage_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }

    public static function logError(
        Tenant $tenant,
        Module $module,
        string $errorCode,
        string $errorMessage,
        array $errorDetails = [],
        ?User $user = null
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_ERROR,
            'description' => "Error en módulo {$module->name}: {$errorMessage}",
            'was_error' => true,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }

    public static function logLimitReached(
        Tenant $tenant,
        Module $module,
        string $limit,
        mixed $currentValue,
        mixed $limitValue,
        ?User $user = null
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_LIMIT_REACHED,
            'description' => "Límite alcanzado en módulo {$module->name}: {$limit} ({$currentValue}/{$limitValue})",
            'usage_data' => [
                'limit_type' => $limit,
                'current_value' => $currentValue,
                'limit_value' => $limitValue,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }

    public static function logInstall(
        Tenant $tenant,
        Module $module,
        ?User $user = null,
        array $configuration = []
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_INSTALL,
            'description' => "Instalación del módulo {$module->name}",
            'usage_data' => ['configuration' => $configuration],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }

    public static function logUninstall(
        Tenant $tenant,
        Module $module,
        ?User $user = null,
        string $reason = ''
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_UNINSTALL,
            'description' => "Desinstalación del módulo {$module->name}".($reason ? " - {$reason}" : ''),
            'usage_data' => ['reason' => $reason],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }

    public static function logConfiguration(
        Tenant $tenant,
        Module $module,
        array $changes,
        ?User $user = null
    ): self {
        return self::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'user_id' => $user?->id,
            'event_type' => self::EVENT_CONFIGURATION,
            'description' => "Configuración actualizada en módulo {$module->name}",
            'usage_data' => ['changes' => $changes],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
        ]);
    }
}
