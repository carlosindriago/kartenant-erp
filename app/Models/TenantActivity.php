<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantActivity extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Activity types
     */
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_SUSPENDED = 'suspended';
    const ACTION_ACTIVATED = 'activated';
    const ACTION_TRIAL_STARTED = 'trial_started';
    const ACTION_TRIAL_EXPIRED = 'trial_expired';
    const ACTION_BACKUP_CREATED = 'backup_created';
    const ACTION_BACKUP_RESTORED = 'backup_restored';
    const ACTION_SETTINGS_UPDATED = 'settings_updated';
    const ACTION_USER_ADDED = 'user_added';
    const ACTION_USER_REMOVED = 'user_removed';

    /**
     * Get the tenant that owns the activity.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all available activity actions
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_LOGIN,
            self::ACTION_LOGOUT,
            self::ACTION_CREATED,
            self::ACTION_UPDATED,
            self::ACTION_DELETED,
            self::ACTION_SUSPENDED,
            self::ACTION_ACTIVATED,
            self::ACTION_TRIAL_STARTED,
            self::ACTION_TRIAL_EXPIRED,
            self::ACTION_BACKUP_CREATED,
            self::ACTION_BACKUP_RESTORED,
            self::ACTION_SETTINGS_UPDATED,
            self::ACTION_USER_ADDED,
            self::ACTION_USER_REMOVED,
        ];
    }

    /**
     * Get action label in Spanish for UI display
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_LOGIN => 'Inicio de Sesión',
            self::ACTION_LOGOUT => 'Cierre de Sesión',
            self::ACTION_CREATED => 'Creado',
            self::ACTION_UPDATED => 'Actualizado',
            self::ACTION_DELETED => 'Eliminado',
            self::ACTION_SUSPENDED => 'Suspendido',
            self::ACTION_ACTIVATED => 'Activado',
            self::ACTION_TRIAL_STARTED => 'Prueba Iniciada',
            self::ACTION_TRIAL_EXPIRED => 'Prueba Expirada',
            self::ACTION_BACKUP_CREATED => 'Backup Creado',
            self::ACTION_BACKUP_RESTORED => 'Backup Restaurado',
            self::ACTION_SETTINGS_UPDATED => 'Configuración Actualizada',
            self::ACTION_USER_ADDED => 'Usuario Agregado',
            self::ACTION_USER_REMOVED => 'Usuario Removido',
            default => 'Desconocido',
        };
    }

    /**
     * Get action color for UI
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            self::ACTION_LOGIN => 'success',
            self::ACTION_LOGOUT => 'gray',
            self::ACTION_CREATED => 'success',
            self::ACTION_UPDATED => 'info',
            self::ACTION_DELETED => 'danger',
            self::ACTION_SUSPENDED => 'warning',
            self::ACTION_ACTIVATED => 'success',
            self::ACTION_TRIAL_STARTED => 'info',
            self::ACTION_TRIAL_EXPIRED => 'warning',
            self::ACTION_BACKUP_CREATED => 'success',
            self::ACTION_BACKUP_RESTORED => 'info',
            self::ACTION_SETTINGS_UPDATED => 'info',
            self::ACTION_USER_ADDED => 'success',
            self::ACTION_USER_REMOVED => 'warning',
            default => 'gray',
        };
    }

    /**
     * Log tenant activity
     */
    public static function log(
        Tenant $tenant,
        string $action,
        string $description,
        ?User $user = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()?->ip(),
            'user_agent' => $userAgent ?? request()?->userAgent(),
        ]);
    }

    /**
     * Scope to get activities by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get activities by tenant
     */
    public function scopeForTenant($query, Tenant $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }

    /**
     * Scope to get activities by user
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get today's activities
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get activities in the last N hours
     */
    public function scopeLastHours($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}