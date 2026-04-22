<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAuditLog extends Model
{
    use HasFactory;

    /**
     * The connection for this model (landlord database)
     */
    protected $connection = 'landlord';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'performed_by',
        'action',
        'old_value',
        'new_value',
        'reason',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that was affected by this audit log entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Create an audit log entry for user actions.
     */
    public static function logUserAction(
        User $targetUser,
        ?User $performedBy,
        string $action,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $reason = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'user_id' => $targetUser->id,
            'performed_by' => $performedBy?->id,
            'action' => $action,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope to get logs for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to get logs performed by a specific user.
     */
    public function scopePerformedBy($query, User $user)
    {
        return $query->where('performed_by', $user->id);
    }

    /**
     * Scope to get logs for a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get logs within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getActionDescriptionAttribute(): string
    {
        return match($this->action) {
            'user_created' => 'Usuario creado',
            'user_updated' => 'Usuario actualizado',
            'user_deleted' => 'Usuario eliminado',
            'user_activated' => 'Usuario activado',
            'user_deactivated' => 'Usuario desactivado',
            'super_admin_status_changed' => 'Estado de Super Admin modificado',
            'security_settings_updated' => 'Configuración de seguridad actualizada',
            'roles_assigned' => 'Roles asignados',
            'permissions_assigned' => 'Permisos asignados',
            'password_changed' => 'Contraseña cambiada',
            '2fa_enabled' => '2FA activado',
            '2fa_disabled' => '2FA desactivado',
            'login_attempt' => 'Intento de inicio de sesión',
            'failed_login' => 'Inicio de sesión fallido',
            'password_reset_requested' => 'Restablecimiento de contraseña solicitado',
            'password_reset_completed' => 'Restablecimiento de contraseña completado',
            default => $this->action,
        };
    }

    /**
     * Check if this was a privileged action.
     */
    public function isPrivilegedAction(): bool
    {
        $privilegedActions = [
            'super_admin_status_changed',
            'user_activated',
            'user_deactivated',
            'security_settings_updated',
            'roles_assigned',
            'permissions_assigned',
        ];

        return in_array($this->action, $privilegedActions);
    }

    /**
     * Get formatted metadata for display.
     */
    public function getFormattedMetadataAttribute(): string
    {
        if (empty($this->metadata)) {
            return '';
        }

        $formatted = [];
        foreach ($this->metadata as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'Sí' : 'No';
            }
            $formatted[] = "{$key}: {$value}";
        }

        return implode(', ', $formatted);
    }

    /**
     * Get formatted change summary.
     */
    public function getChangeSummaryAttribute(): string
    {
        if ($this->old_value === null && $this->new_value !== null) {
            return "Añadido: {$this->new_value}";
        }

        if ($this->old_value !== null && $this->new_value === null) {
            return "Eliminado: {$this->old_value}";
        }

        if ($this->old_value !== null && $this->new_value !== null) {
            return "Cambiado: '{$this->old_value}' → '{$this->new_value}'";
        }

        return '';
    }

    /**
     * Get alert level for this log entry.
     */
    public function getAlertLevelAttribute(): string
    {
        $criticalActions = [
            'user_deleted',
            'super_admin_status_changed',
            'user_deactivated',
        ];

        $highActions = [
            'security_settings_updated',
            'roles_assigned',
            'permissions_assigned',
        ];

        if (in_array($this->action, $criticalActions)) {
            return 'critical';
        }

        if (in_array($this->action, $highActions)) {
            return 'high';
        }

        return 'medium';
    }
}