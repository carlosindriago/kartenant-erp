<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::guard('superadmin')->user();

        // User must be authenticated and have admin users creation permission
        if (!$user || !$user->is_super_admin) {
            return false;
        }

        return $user->hasPermissionTo('admin.users.create', 'superadmin') ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::guard('superadmin')->user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return [
            // Basic user fields - always required
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],

            // Administrative fields - only superadmins can set these
            'is_super_admin' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'boolean',
                function ($attribute, $value, $fail) use ($user) {
                    if ($user && !$user->is_super_admin && $value) {
                        $fail('No puedes asignar privilegios de Super Admin.');
                    }
                }
            ],

            'is_active' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'boolean'
            ],

            'force_renew_password' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'boolean'
            ],

            'must_change_password' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'boolean'
            ],

            // Role and permission assignments - only for authorized users
            'roles' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'array',
                'exists:roles,id'
            ],

            'permissions' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'array',
                'exists:permissions,id'
            ],

            // Tenant assignments - only for authorized users
            'tenants' => [
                $isSuperAdmin ? 'sometimes' : 'prohibited',
                'array',
                'exists:tenants,id'
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
            'roles.exists' => 'Uno o más roles seleccionados no son válidos.',
            'permissions.exists' => 'Uno o más permisos seleccionados no son válidos.',
            'tenants.exists' => 'Uno o más tenants seleccionados no son válidos.',
        ];
    }

    /**
     * Get the sanitized input data with proper defaults.
     */
    public function sanitized(): array
    {
        $data = $this->validated();
        $user = Auth::guard('superadmin')->user();

        // Set secure defaults
        $data['is_active'] = $data['is_active'] ?? true;
        $data['force_renew_password'] = $data['force_renew_password'] ?? false;
        $data['must_change_password'] = $data['must_change_password'] ?? false;

        // Only superadmins can create other superadmins
        if (!$user?->is_super_admin) {
            $data['is_super_admin'] = false;
        }

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        return $data;
    }
}