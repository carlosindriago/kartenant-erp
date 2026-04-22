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

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Tenant; 
use Filament\Models\Contracts\HasTenants; 
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Yebor974\Filament\RenewPassword\Contracts\RenewPasswordContract;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements HasTenants, RenewPasswordContract, FilamentUser
{
    // Fuerza que TODOS los usuarios residan en la DB landlord
    protected $connection = 'landlord';
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, RenewPassword;

    /**
     * The attributes that are mass assignable.
     *
     * SECURITY: Only basic user fields are mass assignable.
     * Administrative fields must be set through controlled methods
     * with proper authorization checks to prevent privilege escalation.
     *
     * @var list<string>
     */
    protected $fillable = [
        // Basic user information - safe for mass assignment
        'name',
        'email',
        'password',
    ];

    /**
     * Attributes that are NOT mass assignable and require special handling.
     *
     * These fields contain sensitive administrative or security data that
     * should only be modified through authorized methods with proper validation.
     *
     * @var array<string>
     */
    protected $guarded = [
        'id', // Primary key
        'is_super_admin', // CRITICAL: Prevents privilege escalation
        'is_active', // Administrative control
        'deactivation_reason', // Administrative audit data
        'deactivated_at', // Administrative audit data
        'deactivated_by', // Administrative audit data
        'reactivation_reason', // Administrative audit data
        'reactivated_at', // Administrative audit data
        'reactivated_by', // Administrative audit data
        'reactivation_code', // Security code
        'reactivation_code_expires_at', // Security timing
        'must_change_password', // Security enforcement
        'email_2fa_code', // 2FA security
        'email_2fa_expires_at', // 2FA timing
        'password_change_code', // Password security
        'password_change_code_expires_at', // Password timing
        'password_changed_at', // Security audit
        'force_renew_password', // Security enforcement
        'last_password_change_at', // Security audit
        'email_verified_at', // Security verification
        'remember_token', // Authentication token
        'created_at', // System timestamps
        'updated_at', // System timestamps
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'password' => 'hashed', // ← REMOVED: Causa conflicto con Hash::check()
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'reactivated_at' => 'datetime',
            'reactivation_code_expires_at' => 'datetime',
            'must_change_password' => 'boolean',
            'email_2fa_expires_at' => 'datetime',
            'password_change_code_expires_at' => 'datetime',
            'password_changed_at' => 'datetime',
            // Campos para RenewPassword plugin
            'force_renew_password' => 'boolean',
            'last_password_change_at' => 'datetime',
        ];
    }

    /**
     * Mutator para hashear contraseñas automáticamente
     * (Reemplazo del cast 'hashed' que causaba conflictos)
     */
    public function setPasswordAttribute($value)
    {
        // Si está vacío, establecer como null
        if (empty($value)) {
            $this->attributes['password'] = null;
            return;
        }
        
        // Verificar si ya es un hash válido (bcrypt, Argon2, etc.)
        if (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$') || str_starts_with($value, '$2b$')) {
            $this->attributes['password'] = $value;
            return;
        }
        
        // Hashear nuevo valor solo si no es un hash existente
        $this->attributes['password'] = \Hash::make($value);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }
    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        // Usamos el Model genérico como exige el contrato
        return $this->tenants()->where('tenants.id', $tenant->id)->exists();
    }

    public function userSecurityAnswers()
    {
        return $this->hasMany(UserSecurityAnswer::class);
    }

    public function hasSecurityQuestions(): bool
    {
        return $this->userSecurityAnswers()->exists();
    }
    
    /**
     * Usuario que desactivó este usuario
     */
    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }
    
    /**
     * Usuario que reactivó este usuario
     */
    public function reactivatedBy()
    {
        return $this->belongsTo(User::class, 'reactivated_by');
    }
    
    /**
     * Historial completo de cambios de estado
     */
    public function statusChanges()
    {
        return $this->hasMany(\App\Models\UserStatusChange::class, 'user_id')
            ->orderBy('changed_at', 'desc');
    }
    
    /**
     * Historial de desactivaciones
     */
    public function deactivationHistory()
    {
        return $this->hasMany(\App\Models\UserStatusChange::class, 'user_id')
            ->where('action', 'deactivated')
            ->orderBy('changed_at', 'desc');
    }
    
    /**
     * Historial de reactivaciones
     */
    public function activationHistory()
    {
        return $this->hasMany(\App\Models\UserStatusChange::class, 'user_id')
            ->where('action', 'activated')
            ->orderBy('changed_at', 'desc');
    }
    
    /**
     * Generar código de seguridad para desactivación/reactivación
     */
    public function generateSecurityCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'reactivation_code' => $code,
            'reactivation_code_expires_at' => now()->addMinutes(10),
        ]);
        
        return $code;
    }
    
    /**
     * Generar código de reactivación (alias para compatibilidad)
     */
    public function generateReactivationCode(): string
    {
        return $this->generateSecurityCode();
    }
    
    /**
     * Verificar código de seguridad
     */
    public function verifySecurityCode(string $code): bool
    {
        if (!$this->reactivation_code || !$this->reactivation_code_expires_at) {
            return false;
        }
        
        if ($this->reactivation_code_expires_at->isPast()) {
            return false;
        }
        
        return $this->reactivation_code === $code;
    }
    
    /**
     * Verificar código de reactivación (alias para compatibilidad)
     */
    public function verifyReactivationCode(string $code): bool
    {
        return $this->verifySecurityCode($code);
    }

    /**
     * Generate and store email 2FA code
     */
    public function generateEmail2FACode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'email_2fa_code' => $code,
            'email_2fa_expires_at' => now()->addMinutes(10),
        ]);
        
        return $code;
    }

    /**
     * Verify email 2FA code
     */
    public function verifyEmail2FACode(string $code): bool
    {
        if (!$this->email_2fa_code || !$this->email_2fa_expires_at) {
            return false;
        }
        
        if ($this->email_2fa_expires_at->isPast()) {
            return false;
        }
        
        return hash_equals($this->email_2fa_code, $code);
    }

    /**
     * Clear email 2FA code
     */
    public function clearEmail2FACode(): void
    {
        $this->update([
            'email_2fa_code' => null,
            'email_2fa_expires_at' => null,
        ]);
    }

    /**
     * Generate password change verification code
     */
    public function generatePasswordChangeCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'password_change_code' => $code,
            'password_change_code_expires_at' => now()->addMinutes(10),
        ]);
        
        return $code;
    }

    /**
     * Verify password change code
     */
    public function verifyPasswordChangeCode(string $code): bool
    {
        if (!$this->password_change_code || !$this->password_change_code_expires_at) {
            return false;
        }
        
        if ($this->password_change_code_expires_at->isPast()) {
            return false;
        }
        
        return hash_equals($this->password_change_code, $code);
    }

    /**
     * Clear password change code and mark password as changed
     */
    public function clearPasswordChangeCode(): void
    {
        $this->update([
            'password_change_code' => null,
            'password_change_code_expires_at' => null,
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);
    }

    /**
     * Check if user needs to change password on first login
     */
    public function needsPasswordChange(): bool
    {
        return (bool) $this->must_change_password;
    }

    /**
     * SECURE: Update super admin status with proper authorization
     * This method prevents mass assignment vulnerabilities
     */
    public function updateSuperAdminStatus(bool $isSuperAdmin, ?User $authorizedBy = null): bool
    {
        // Validate authorization
        if (!$authorizedBy || !$authorizedBy->is_super_admin) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only superadmins can modify superadmin status.');
        }

        // Prevent self-modification
        if ($authorizedBy->id === $this->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Cannot modify your own superadmin status.');
        }

        // Prevent removing last superadmin
        if ($this->is_super_admin && !$isSuperAdmin) {
            $superadminCount = static::where('is_super_admin', true)->count();
            if ($superadminCount <= 1) {
                throw new \InvalidArgumentException('Cannot remove superadmin status from the last superadmin.');
            }
        }

        // Update using direct database query to bypass mass assignment protection
        $this->forceFill(['is_super_admin' => $isSuperAdmin])->saveQuietly();

        // Log the change for audit
        \App\Models\UserAuditLog::create([
            'user_id' => $this->id,
            'performed_by' => $authorizedBy->id,
            'action' => 'super_admin_status_changed',
            'old_value' => $this->getOriginal('is_super_admin'),
            'new_value' => $isSuperAdmin,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * SECURE: Update user active status with proper authorization
     */
    public function updateActiveStatus(bool $isActive, string $reason = null, ?User $authorizedBy = null): bool
    {
        // Validate authorization
        if (!$authorizedBy || (!$authorizedBy->is_super_admin && !$authorizedBy->hasPermissionTo('admin.users.update', 'superadmin'))) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Insufficient permissions to modify user status.');
        }

        // Prevent self-deactivation
        if ($authorizedBy->id === $this->id && !$isActive) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Cannot deactivate your own account.');
        }

        // Prevent deactivating last superadmin
        if ($this->is_super_admin && $this->is_active && !$isActive) {
            $activeSuperadminCount = static::where('is_super_admin', true)
                ->where('is_active', true)
                ->count();
            if ($activeSuperadminCount <= 1) {
                throw new \InvalidArgumentException('Cannot deactivate the last active superadmin.');
            }
        }

        // Update with audit trail
        $updateData = [
            'is_active' => $isActive,
        ];

        if (!$isActive && $reason) {
            $updateData['deactivation_reason'] = $reason;
            $updateData['deactivated_at'] = now();
            $updateData['deactivated_by'] = $authorizedBy->id;
        } elseif ($isActive) {
            $updateData['reactivation_reason'] = $reason;
            $updateData['reactivated_at'] = now();
            $updateData['reactivated_by'] = $authorizedBy->id;
            $updateData['reactivation_code'] = null;
            $updateData['reactivation_code_expires_at'] = null;
        }

        $this->forceFill($updateData)->saveQuietly();

        // Log the change for audit
        \App\Models\UserAuditLog::create([
            'user_id' => $this->id,
            'performed_by' => $authorizedBy->id,
            'action' => $isActive ? 'user_activated' : 'user_deactivated',
            'old_value' => $this->getOriginal('is_active'),
            'new_value' => $isActive,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * SECURE: Update security settings with proper authorization
     */
    public function updateSecuritySettings(array $settings, ?User $authorizedBy = null): bool
    {
        // Validate authorization
        if (!$authorizedBy || !$authorizedBy->is_super_admin) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only superadmins can modify user security settings.');
        }

        // Define allowed security settings
        $allowedSettings = [
            'must_change_password',
            'force_renew_password',
            'email_2fa_code',
            'email_2fa_expires_at',
        ];

        // Filter and validate settings
        $updateData = [];
        foreach ($settings as $key => $value) {
            if (!in_array($key, $allowedSettings)) {
                throw new \InvalidArgumentException("Setting '{$key}' is not allowed.");
            }
            $updateData[$key] = $value;
        }

        if (empty($updateData)) {
            return true; // No changes to make
        }

        // Store original values for audit
        $originalValues = [];
        foreach (array_keys($updateData) as $key) {
            $originalValues[$key] = $this->getOriginal($key);
        }

        // Update using forceFill to bypass mass assignment protection
        $this->forceFill($updateData)->saveQuietly();

        // Log the changes for audit
        \App\Models\UserAuditLog::create([
            'user_id' => $this->id,
            'performed_by' => $authorizedBy->id,
            'action' => 'security_settings_updated',
            'old_value' => json_encode($originalValues),
            'new_value' => json_encode($updateData),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * SECURE: Bulk update multiple fields with comprehensive validation
     */
    public function secureUpdate(array $data, ?User $authorizedBy = null): bool
    {
        if (!$authorizedBy) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Authorization required for updates.');
        }

        // Separate safe fields from administrative fields
        $safeFields = ['name', 'email', 'password'];
        $adminFields = array_diff(array_keys($data), $safeFields);

        // Handle safe fields (users can update their own)
        $safeData = array_intersect_key($data, array_flip($safeFields));
        if (!empty($safeData)) {
            if ($authorizedBy->id !== $this->id && !$authorizedBy->is_super_admin) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Cannot modify other users basic information.');
            }

            $this->update($safeData);
        }

        // Handle administrative fields
        $adminData = array_intersect_key($data, array_flip($adminFields));
        if (!empty($adminData)) {
            if (!$authorizedBy->is_super_admin) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Only superadmins can modify administrative fields.');
            }

            // Process each administrative field with its security method
            if (isset($adminData['is_super_admin'])) {
                $this->updateSuperAdminStatus($adminData['is_super_admin'], $authorizedBy);
                unset($adminData['is_super_admin']);
            }

            if (isset($adminData['is_active'])) {
                $this->updateActiveStatus(
                    $adminData['is_active'],
                    $adminData['deactivation_reason'] ?? null,
                    $authorizedBy
                );
                unset($adminData['is_active'], $adminData['deactivation_reason']);
            }

            if (!empty($adminData)) {
                $this->updateSecuritySettings($adminData, $authorizedBy);
            }
        }

        return true;
    }

    /**
     * Determine if the user can access the Filament panel.
     * This is required by the FilamentUser contract.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow access to admin panel for superadmins
        if ($panel->getId() === 'admin') {
            return $this->is_super_admin === true;
        }

        // For other panels (like tenant), check if user is active
        return $this->is_active === true;
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        // Usar nuestra notificación personalizada que evita el problema de MailChannel
        $this->notify(new \App\Notifications\PasswordResetNotification($token));
    }
}
