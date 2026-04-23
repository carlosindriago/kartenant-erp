<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->is_super_admin ||
               $user->hasPermissionTo('admin.users.view', 'superadmin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, User $model): bool
    {
        if (! $user) {
            return false;
        }

        // Users can always view themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Superadmins can view any user
        if ($user->is_super_admin) {
            return true;
        }

        // Users with permission can view others
        return $user->hasPermissionTo('admin.users.view', 'superadmin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->is_super_admin ||
               $user->hasPermissionTo('admin.users.create', 'superadmin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, User $model): bool
    {
        if (! $user) {
            return false;
        }

        // Users can update their own basic information
        if ($user->id === $model->id) {
            return true;
        }

        // Superadmins can update any user
        if ($user->is_super_admin) {
            return true;
        }

        // Users with permission can update others
        return $user->hasPermissionTo('admin.users.update', 'superadmin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, User $model): bool
    {
        if (! $user) {
            return false;
        }

        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only superadmins can delete users
        if (! $user->is_super_admin) {
            return false;
        }

        return $user->hasPermissionTo('admin.users.delete', 'superadmin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user, User $model): bool
    {
        // Only superadmins can force delete
        if (! $user?->is_super_admin) {
            return false;
        }

        // Cannot force delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot force delete the last superadmin
        if ($model->is_super_admin) {
            $superadminCount = User::where('is_super_admin', true)->count();

            return $superadminCount > 1;
        }

        return true;
    }

    /**
     * Determine whether the user can modify super admin status.
     */
    public function modifySuperAdminStatus(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        // Users cannot modify their own super admin status
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can modify user active status.
     */
    public function modifyActiveStatus(?User $user, User $model): bool
    {
        if (! $user) {
            return false;
        }

        // Users cannot deactivate themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only superadmins can modify active status
        if (! $user->is_super_admin) {
            return false;
        }

        // Cannot deactivate the last active superadmin
        if ($model->is_super_admin && $model->is_active) {
            $activeSuperadminCount = User::where('is_super_admin', true)
                ->where('is_active', true)
                ->count();

            return $activeSuperadminCount > 1;
        }

        return true;
    }

    /**
     * Determine whether the user can assign roles.
     */
    public function assignRoles(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        // Users cannot modify their own roles to prevent privilege escalation
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can assign permissions.
     */
    public function assignPermissions(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        // Users cannot modify their own permissions to prevent privilege escalation
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage user's tenants.
     */
    public function manageTenants(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can force password renewal.
     */
    public function forcePasswordRenewal(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        // Users cannot force their own password renewal
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can access user security settings.
     */
    public function accessSecuritySettings(?User $user, User $model): bool
    {
        if (! $user) {
            return false;
        }

        // Users can always access their own security settings
        if ($user->id === $model->id) {
            return true;
        }

        // Only superadmins can access others' security settings
        return $user->is_super_admin;
    }

    /**
     * Determine whether the user can modify 2FA settings.
     */
    public function modify2FASettings(?User $user, User $model): bool
    {
        if (! $user) {
            return false;
        }

        // Users can modify their own 2FA settings
        if ($user->id === $model->id) {
            return true;
        }

        // Only superadmins can modify others' 2FA settings
        return $user->is_super_admin;
    }

    /**
     * Determine whether the user can view user audit logs.
     */
    public function viewAuditLogs(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can impersonate another user.
     */
    public function impersonate(?User $user, User $model): bool
    {
        if (! $user || ! $user->is_super_admin) {
            return false;
        }

        // Users cannot impersonate themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Users cannot impersonate other superadmins
        if ($model->is_super_admin) {
            return false;
        }

        return $user->hasPermissionTo('admin.users.impersonate', 'superadmin');
    }

    /**
     * Get the user's permissions for a specific field.
     */
    public function canModifyField(?User $user, User $model, string $field): bool
    {
        if (! $user) {
            return false;
        }

        // Users can always modify their own basic fields
        if ($user->id === $model->id) {
            $allowedSelfFields = ['name', 'email', 'password'];

            return in_array($field, $allowedSelfFields);
        }

        // Superadmins can modify most fields
        if ($user->is_super_admin) {
            $restrictedFields = [];

            return ! in_array($field, $restrictedFields);
        }

        return false;
    }

    /**
     * Additional security checks before model operations
     */
    public function before(?User $user, string $ability): ?bool
    {
        if ($user === null) {
            return false;
        }

        // Superadmins have all abilities except where restricted
        if ($user->is_super_admin) {
            return null; // Let specific method handle restrictions
        }

        return null; // Let specific method handle authorization
    }
}
