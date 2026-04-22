<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantActivityService
{
    /**
     * Log tenant activity
     */
    public static function log(
        Tenant $tenant,
        string $action,
        string $description,
        ?User $user = null,
        ?array $metadata = null,
        ?Request $request = null
    ): TenantActivity {
        try {
            $ipAddress = $request?->ip() ?? request()?->ip();
            $userAgent = $request?->userAgent() ?? request()?->userAgent();

            return TenantActivity::log(
                tenant: $tenant,
                action: $action,
                description: $description,
                user: $user,
                metadata: $metadata,
                ipAddress: $ipAddress,
                userAgent: $userAgent
            );
        } catch (\Exception $e) {
            Log::error("Failed to log tenant activity: " . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'action' => $action,
                'description' => $description,
            ]);

            // Don't throw the exception to avoid breaking the main application flow
            throw $e;
        }
    }

    /**
     * Log tenant creation
     */
    public static function logTenantCreated(Tenant $tenant, ?User $creator = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_CREATED,
            description: "Tenant '{$tenant->name}' was created",
            user: $creator,
            metadata: [
                'tenant_name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'plan' => $tenant->plan,
            ]
        );
    }

    /**
     * Log tenant status change
     */
    public static function logStatusChange(
        Tenant $tenant,
        string $oldStatus,
        string $newStatus,
        ?User $changedBy = null
    ): TenantActivity {
        $action = match($newStatus) {
            Tenant::STATUS_ACTIVE => TenantActivity::ACTION_ACTIVATED,
            Tenant::STATUS_SUSPENDED => TenantActivity::ACTION_SUSPENDED,
            Tenant::STATUS_TRIAL => TenantActivity::ACTION_TRIAL_STARTED,
            Tenant::STATUS_EXPIRED => TenantActivity::ACTION_TRIAL_EXPIRED,
            default => TenantActivity::ACTION_UPDATED,
        };

        return self::log(
            tenant: $tenant,
            action: $action,
            description: "Tenant status changed from '{$oldStatus}' to '{$newStatus}'",
            user: $changedBy,
            metadata: [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'tenant_name' => $tenant->name,
            ]
        );
    }

    /**
     * Log tenant soft delete
     */
    public static function logTenantDeleted(Tenant $tenant, ?User $deletedBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_DELETED,
            description: "Tenant '{$tenant->name}' was archived",
            user: $deletedBy,
            metadata: [
                'tenant_name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
            ]
        );
    }

    /**
     * Log tenant restoration
     */
    public static function logTenantRestored(Tenant $tenant, ?User $restoredBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_UPDATED,
            description: "Tenant '{$tenant->name}' was restored",
            user: $restoredBy,
            metadata: [
                'tenant_name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
            ]
        );
    }

    /**
     * Log tenant login
     */
    public static function logLogin(Tenant $tenant, User $user, ?Request $request = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_LOGIN,
            description: "User '{$user->name}' logged in",
            user: $user,
            request: $request,
            metadata: [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ]
        );
    }

    /**
     * Log tenant logout
     */
    public static function logLogout(Tenant $tenant, User $user, ?Request $request = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_LOGOUT,
            description: "User '{$user->name}' logged out",
            user: $user,
            request: $request,
            metadata: [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ]
        );
    }

    /**
     * Log user added to tenant
     */
    public static function logUserAdded(Tenant $tenant, User $user, ?User $addedBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_USER_ADDED,
            description: "User '{$user->name}' was added to tenant",
            user: $addedBy,
            metadata: [
                'added_user_name' => $user->name,
                'added_user_email' => $user->email,
            ]
        );
    }

    /**
     * Log user removed from tenant
     */
    public static function logUserRemoved(Tenant $tenant, User $user, ?User $removedBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_USER_REMOVED,
            description: "User '{$user->name}' was removed from tenant",
            user: $removedBy,
            metadata: [
                'removed_user_name' => $user->name,
                'removed_user_email' => $user->email,
            ]
        );
    }

    /**
     * Log backup created
     */
    public static function logBackupCreated(Tenant $tenant, string $backupPath, ?User $createdBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_BACKUP_CREATED,
            description: "Backup was created for tenant",
            user: $createdBy,
            metadata: [
                'backup_path' => $backupPath,
                'file_size' => file_exists($backupPath) ? filesize($backupPath) : null,
            ]
        );
    }

    /**
     * Log backup restored
     */
    public static function logBackupRestored(Tenant $tenant, string $backupPath, ?User $restoredBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_BACKUP_RESTORED,
            description: "Backup was restored for tenant",
            user: $restoredBy,
            metadata: [
                'backup_path' => $backupPath,
                'restored_at' => now()->toISOString(),
            ]
        );
    }

    /**
     * Log settings updated
     */
    public static function logSettingsUpdated(
        Tenant $tenant,
        array $changedFields,
        ?User $updatedBy = null
    ): TenantActivity {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_SETTINGS_UPDATED,
            description: "Tenant settings were updated",
            user: $updatedBy,
            metadata: [
                'changed_fields' => array_keys($changedFields),
                'old_values' => $changedFields,
            ]
        );
    }

    /**
     * Log trial started
     */
    public static function logTrialStarted(Tenant $tenant, ?User $startedBy = null): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_TRIAL_STARTED,
            description: "Trial period started for tenant",
            user: $startedBy,
            metadata: [
                'trial_ends_at' => $tenant->trial_ends_at?->toISOString(),
                'trial_duration_days' => $tenant->trial_ends_at ?
                    now()->diffInDays($tenant->trial_ends_at) : null,
            ]
        );
    }

    /**
     * Log trial expired
     */
    public static function logTrialExpired(Tenant $tenant): TenantActivity
    {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_TRIAL_EXPIRED,
            description: "Trial period expired for tenant",
            user: null,
            metadata: [
                'trial_ended_at' => now()->toISOString(),
                'was_active_for_days' => $tenant->trial_ends_at ?
                    $tenant->created_at->diffInDays($tenant->trial_ends_at) : null,
            ]
        );
    }

    /**
     * Log subscription status change (for future billing integration)
     */
    public static function logSubscriptionChange(
        Tenant $tenant,
        string $oldStatus,
        string $newStatus,
        ?User $changedBy = null
    ): TenantActivity {
        return self::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_UPDATED,
            description: "Subscription status changed from '{$oldStatus}' to '{$newStatus}'",
            user: $changedBy,
            metadata: [
                'old_subscription_status' => $oldStatus,
                'new_subscription_status' => $newStatus,
                'plan' => $tenant->plan,
            ]
        );
    }

    /**
     * Get recent tenant activities
     */
    public static function getRecentActivities(Tenant $tenant, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return TenantActivity::forTenant($tenant)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get tenant activities by action
     */
    public static function getActivitiesByAction(
        Tenant $tenant,
        string $action,
        int $limit = 50
    ): \Illuminate\Database\Eloquent\Collection {
        return TenantActivity::forTenant($tenant)
            ->byAction($action)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get tenant activities by date range
     */
    public static function getActivitiesByDateRange(
        Tenant $tenant,
        \DateTime $startDate,
        \DateTime $endDate
    ): \Illuminate\Database\Eloquent\Collection {
        return TenantActivity::forTenant($tenant)
            ->with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up old activities (keep last 90 days)
     */
    public static function cleanupOldActivities(): int
    {
        $cutoffDate = now()->subDays(90);

        return TenantActivity::where('created_at', '<', $cutoffDate)
            ->delete();
    }
}