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

use App\Models\BackupLog;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\PaymentMethod;
use App\Models\TenantActivity;
use App\Models\TenantModule;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use SoftDeletes;
    /**
     * Tenant statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_TRIAL = 'trial';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'domain',
        'database',
        'address',
        'phone',
        'cuit',
        'contact_name',
        'contact_email',
        'plan',
        'trial_ends_at',
        'timezone',
        'locale',
        'currency',
        'verification_access_type',
        'verification_allowed_roles',
        'verification_enabled',
        // Branding fields
        'logo_type',
        'company_display_name',
        'logo_path',
        'logo_background_color',
        'logo_text_color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'verification_allowed_roles' => 'array',
        'verification_enabled' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Subscription relations
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class)
            ->where('status', 'active')
            ->latest();
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TenantActivity::class);
    }

    public function recentActivities(): HasMany
    {
        return $this->activities()->latest()->limit(10);
    }

    /**
     * Backup logs relationship
     */
    public function backupLogs(): HasMany
    {
        return $this->hasMany(BackupLog::class);
    }

    /**
     * Modules relationship
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->withPivot([
                'price_override',
                'starts_at',
                'expires_at',
                'is_active',
                'configuration',
            ])
            ->withTimestamps()
            ->withTrashed();
    }

    public function activeModules(): BelongsToMany
    {
        return $this->modules()->wherePivot('is_active', true);
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function activeTenantModules(): HasMany
    {
        return $this->tenantModules()->where('is_active', true);
    }

    /**
     * Use the 'domain' attribute as the route key so generated URLs
     * use the tenant slug instead of the numeric ID.
     */
    public function getRouteKeyName(): string
    {
        return 'domain';
    }

    /**
     * Get the display name for the tenant
     * Returns company_display_name if set, otherwise falls back to name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_display_name ?? $this->name;
    }

    /**
     * Get the logo URL for the tenant
     * Returns full URL to logo image if logo_type is 'image' and logo_path exists
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_type === 'image' && $this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }

        return null;
    }

    /**
     * Check if tenant uses text logo
     */
    public function usesTextLogo(): bool
    {
        return $this->logo_type === 'text' || !$this->logo_path;
    }

    /**
     * Check if tenant uses image logo
     */
    public function usesImageLogo(): bool
    {
        return $this->logo_type === 'image' && !empty($this->logo_path);
    }

    /**
     * Get status color attribute
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_TRIAL => 'info',
            self::STATUS_SUSPENDED => 'warning',
            self::STATUS_EXPIRED => 'danger',
            self::STATUS_ARCHIVED => 'gray',
            self::STATUS_INACTIVE => 'secondary',
            default => 'gray',
        };
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant is in trial
     */
    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL;
    }

    /**
     * Check if tenant is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if tenant is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if tenant is archived
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if tenant is inactive
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if tenant is active (alias for isActive)
     */
    public function isTenantActive(): bool
    {
        return $this->isActive();
    }

    /**
     * Deactivate tenant safely
     */
    public function deactivate(): bool
    {
        try {
            $this->update(['status' => self::STATUS_INACTIVE]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Archive tenant (soft delete with status change)
     */
    public function archive(): bool
    {
        try {
            $this->update(['status' => self::STATUS_ARCHIVED]);
            $this->delete(); // Soft delete
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get archived tenant statistics
     */
    public function getArchivedStats(): array
    {
        $stats = [];

        // Days archived
        $stats['days_archived'] = $this->deleted_at
            ? $this->deleted_at->diffInDays(now())
            : 0;

        // Archive date
        $stats['archive_date'] = $this->deleted_at
            ? $this->deleted_at->format('d/m/Y')
            : 'N/A';

        // Original status (change archived to a readable format)
        $stats['original_status'] = match($this->status) {
            'archived' => 'Activo',
            default => ucfirst($this->status),
        };

        // User count
        $stats['user_count'] = $this->users()->count();

        // Database size (get approximate MB)
        try {
            $result = \Illuminate\Support\Facades\DB::select("
                SELECT pg_database_size(?) as size_bytes
                FROM pg_database WHERE datname = ?
            ", [$this->database, $this->database]);
            $sizeBytes = $result[0]->size_bytes ?? 0;
            $stats['data_size_mb'] = round($sizeBytes / 1024 / 1024, 2);
            $stats['storage_size'] = round($sizeBytes / 1024 / 1024, 2) . ' MB';
        } catch (\Exception $e) {
            $stats['data_size_mb'] = 0;
            $stats['storage_size'] = '0 MB';
        }

        // Backup count and last backup date
        try {
            $backupCount = \App\Models\BackupLog::where('tenant_id', $this->id)->count();
            $lastBackup = \App\Models\BackupLog::where('tenant_id', $this->id)
                ->latest('created_at')
                ->first();

            $stats['backup_count'] = $backupCount;
            $stats['last_backup_date'] = $lastBackup
                ? $lastBackup->created_at->format('d/m/Y')
                : null;
        } catch (\Exception $e) {
            $stats['backup_count'] = 0;
            $stats['last_backup_date'] = null;
        }

        // Last activity
        $lastActivity = $this->activities()->latest()->first();
        $stats['last_activity'] = $lastActivity
            ? $lastActivity->created_at->diffForHumans()
            : 'Sin actividad';

        // Check for conflicts (basic checks)
        $stats['has_conflicts'] = false; // Default to no conflicts
        // You can add more sophisticated conflict detection here

        return $stats;
    }

    /**
     * Get archive information
     */
    public function getArchiveInfoAttribute(): array
    {
        return [
            'archived_by' => auth('superadmin')->user()?->id,
            'archive_reason' => 'Archivado por administrador',
            'backup_path' => null,
            'backup_size' => 0,
            'ip_address' => request()->ip(),
        ];
    }

    /**
     * Check if tenant has a specific module
     */
    public function hasModule(string $moduleSlug): bool
    {
        // Cache per request for performance
        $cacheKey = "tenant_modules_{$this->id}";

        return cache()->remember($cacheKey, 300, function () use ($moduleSlug) {
            return $this->activeModules()->where('slug', $moduleSlug)->exists();
        });
    }

    /**
     * Get tenant module by slug
     */
    public function getTenantModule(string $moduleSlug): ?TenantModule
    {
        return $this->activeTenantModules()
            ->whereHas('module', fn($q) => $q->where('slug', $moduleSlug))
            ->first();
    }

    /**
     * Check if tenant has access to a specific feature
     */
    public function hasFeatureAccess(string $feature): bool
    {
        // TODO: Fix feature access logic - temporarily disabled for debugging
        return false;

        // Original logic (commented out for now)
        /*
        // Check subscription plan features first
        $activeSubscription = $this->activeSubscription;
        if ($activeSubscription && $activeSubscription->plan->hasFeature($feature)) {
            return true;
        }

        // Check module features
        return $this->activeModules()
            ->whereJsonContains('feature_flags', $feature)
            ->exists();
        */
    }

    /**
     * Check if tenant has access to a specific feature (alias for hasFeatureAccess)
     */
    public function hasFeature(string $feature): bool
    {
        return $this->hasFeatureAccess($feature);
    }

    /**
     * Add a module to the tenant
     */
    public function addModule(Module $module, array $options = []): TenantModule
    {
        $tenantModule = $this->tenantModules()->updateOrCreate(
            ['module_id' => $module->id],
            array_merge([
                'is_active' => true,
                'status' => TenantModule::STATUS_ACTIVE,
                'starts_at' => now(),
                'billing_cycle' => $module->billing_cycle,
                'auto_renew' => $module->auto_renew,
                'added_by' => auth()->id(),
            ], $options)
        );

        // Increment module installation count
        $module->incrementInstallations();

        // Log installation
        ModuleUsageLog::logInstall($this, $module, auth()->user(), $options['configuration'] ?? []);

        return $tenantModule;
    }

    /**
     * Remove a module from the tenant
     */
    public function removeModule(Module $module, string $reason = ''): bool
    {
        $tenantModule = $this->getTenantModule($module->slug);
        if (!$tenantModule) {
            return false;
        }

        // Log uninstallation
        ModuleUsageLog::logUninstall($this, $module, auth()->user(), $reason);

        // Decrement module installation count
        $module->decrementInstallations();

        return $tenantModule->delete();
    }

    /**
     * Get all enabled feature flags for this tenant
     */
    public function getEnabledFeatureFlags(): array
    {
        $flags = [];

        // Get subscription plan features
        $activeSubscription = $this->activeSubscription;
        if ($activeSubscription) {
            $flags = array_merge($flags, $activeSubscription->plan->getFeatureFlags());
        }

        // Get module features
        $this->activeModules()->each(function ($module) use (&$flags) {
            $flags = array_merge($flags, $module->getFeatureFlags());
        });

        return array_unique($flags);
    }

    /**
     * Get all available feature flags for this tenant (alias for getEnabledFeatureFlags)
     */
    public function getAllFeatureFlags(): array
    {
        return $this->getEnabledFeatureFlags();
    }

    /**
     * Get total monthly cost of all modules
     */
    public function getModulesMonthlyCost(): float
    {
        return $this->activeTenantModules()
            ->get()
            ->sum(fn($tm) => $tm->getMonthlyCost());
    }

    /**
     * Get total yearly cost of all modules
     */
    public function getModulesYearlyCost(): float
    {
        return $this->activeTenantModules()
            ->get()
            ->sum(fn($tm) => $tm->getYearlyCost());
    }

    /**
     * Check if tenant is over any module limits
     */
    public function checkModuleLimits(): array
    {
        $violations = [];

        $this->activeTenantModules()->each(function ($tenantModule) use (&$violations) {
            $limits = $tenantModule->getEffectiveLimits();

            foreach ($limits as $limit => $value) {
                if ($tenantModule->isOverLimit($limit)) {
                    $violations[] = [
                        'module' => $tenantModule->module->name,
                        'limit' => $limit,
                        'current' => $tenantModule->getUsageMetric($limit),
                        'max' => $value,
                    ];
                }
            }
        });

        return $violations;
    }

    /**
     * Get modules that are expiring soon
     */
    public function getModulesExpiringSoon(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeTenantModules()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->get();
    }

    /**
     * Get modules by category
     */
    public function getModulesByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeModules()
            ->where('category', $category)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get monthly usage statistics for all modules
     */
    public function getModulesUsageStatistics(): array
    {
        return $this->activeTenantModules()
            ->get()
            ->mapWithKeys(function ($tenantModule) {
                return [
                    $tenantModule->module->slug => [
                        'name' => $tenantModule->module->name,
                        'usage_stats' => $tenantModule->usage_stats ?? [],
                        'last_used_at' => $tenantModule->last_used_at,
                        'cost_per_month' => $tenantModule->getMonthlyCost(),
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Resolve route binding for archived tenants
     * This fixes the 404 error when accessing archived tenant details
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Check if we're in archived tenant context
        $currentRoute = request()->route();
        if ($currentRoute && str_contains($currentRoute->getName(), 'archived-tenants')) {
            return $this->withTrashed()->where($field ?? 'id', $value)->first();
        }

        // Default behavior for all other routes
        return parent::resolveRouteBinding($value, $field);
    }
}