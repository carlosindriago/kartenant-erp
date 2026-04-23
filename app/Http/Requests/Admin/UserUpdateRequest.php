<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::guard('superadmin')->user();
        $targetUser = $this->route('user');

        // User must be authenticated and have admin users update permission
        if (! $user || ! $targetUser) {
            return false;
        }

        if (! $user->is_super_admin && ! $user->hasPermissionTo('admin.users.update', 'superadmin')) {
            return false;
        }

        // Users cannot modify their own super_admin status (self-privilege escalation prevention)
        if ($user->id === $targetUser->id && $this->has('is_super_admin')) {
            return false;
        }

        // Only superadmins can modify other superadmins
        if ($targetUser->is_super_admin && ! $user->is_super_admin) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::guard('superadmin')->user();
        $targetUser = $this->route('user');
        $isSuperAdmin = $user?->is_super_admin ?? false;
        $isSelf = $user?->id === $targetUser?->id;

        return [
            // Basic user fields - can be updated by authorized users
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($targetUser?->id)],

            // Password fields - allowed but with proper validation
            'password' => ['sometimes', 'confirmed', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],

            // HIGHLY RESTRICTED: Super admin status - only superadmins can modify
            'is_super_admin' => [
                $isSuperAdmin && ! $isSelf ? 'sometimes' : 'prohibited',
                'boolean',
                function ($attribute, $value, $fail) use ($user, $isSelf) {
                    if ($isSelf && $value !== $user->is_super_admin) {
                        $fail('No puedes modificar tu propio estado de Super Admin.');
                    }
                    if (! $user?->is_super_admin && $value) {
                        $fail('No tienes permisos para asignar privilegios de Super Admin.');
                    }
                },
            ],

            // Administrative fields - restricted access
            'is_active' => [
                $isSuperAdmin && ! $isSelf ? 'sometimes' : 'prohibited',
                'boolean',
                function ($attribute, $value, $fail) use ($isSelf) {
                    if ($isSelf && ! $value) {
                        $fail('No puedes desactivar tu propia cuenta.');
                    }
                },
            ],

            'deactivation_reason' => [
                $isSuperAdmin && ! $isSelf ? 'required_if:is_active,false' : 'prohibited',
                'string', 'max:500',
            ],

            'force_renew_password' => [
                $isSuperAdmin && ! $isSelf ? 'sometimes' : 'prohibited',
                'boolean',
            ],

            'must_change_password' => [
                'sometimes',
                'boolean',
            ],

            // Security fields - only superadmins can modify
            'email_2fa_code' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'string', 'size:6',
            ],

            'email_2fa_expires_at' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'date',
            ],

            // Role and permission assignments - only for authorized users
            'roles' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'array',
                'exists:roles,id',
            ],

            'permissions' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'array',
                'exists:permissions,id',
            ],

            // Tenant assignments - only for authorized users
            'tenants' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'array',
                'exists:tenants,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.regex' => 'La contraseña debe contener al menos una letra minúscula, una letra mayúscula y un número.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'deactivation_reason.required_if' => 'El motivo de desactivación es obligatorio cuando se desactiva un usuario.',
            'roles.exists' => 'Uno o más roles seleccionados no son válidos.',
            'permissions.exists' => 'Uno o más permisos seleccionados no son válidos.',
            'tenants.exists' => 'Uno o más tenants seleccionados no son válidos.',
        ];
    }

    /**
     * Get the sanitized input data with proper authorization checks.
     */
    public function sanitized(): array
    {
        $data = $this->validated();
        $user = Auth::guard('superadmin')->user();
        $targetUser = $this->route('user');
        $isSelf = $user?->id === $targetUser?->id;

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
            unset($data['password_confirmation']);
        }

        // Track sensitive field changes for audit logging
        $sensitiveFields = ['is_super_admin', 'is_active', 'deactivation_reason', 'roles', 'permissions'];
        $changes = [];

        foreach ($sensitiveFields as $field) {
            if ($this->has($field) && $targetUser) {
                $oldValue = $targetUser->$field;
                $newValue = $data[$field] ?? null;

                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        // Add audit trail data
        if (! empty($changes)) {
            $data['_audit_changes'] = $changes;
            $data['_audit_user_id'] = $user?->id;
            $data['_audit_ip'] = request()->ip();
        }

        return $data;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::guard('superadmin')->user();
            $targetUser = $this->route('user');

            // Prevent last superadmin from being deactivated
            if ($this->has('is_active') && ! $this->input('is_active') &&
                $targetUser?->is_super_admin) {

                $superadminCount = User::where('is_super_admin', true)
                    ->where('is_active', true)
                    ->count();

                if ($superadminCount <= 1) {
                    $validator->errors()->add('is_active', 'No se puede desactivar al último administrador activo del sistema.');
                }
            }

            // Prevent removing superadmin status from last superadmin
            if ($this->has('is_super_admin') && ! $this->input('is_super_admin') &&
                $targetUser?->is_super_admin) {

                $superadminCount = User::where('is_super_admin', true)->count();

                if ($superadminCount <= 1) {
                    $validator->errors()->add('is_super_admin', 'No se puede remover el estatus de Super Admin del último administrador del sistema.');
                }
            }
        });
    }
}
