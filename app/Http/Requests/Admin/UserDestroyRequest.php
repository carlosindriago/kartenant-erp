<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::guard('superadmin')->user();
        $targetUser = $this->route('user');

        // User must be authenticated and have admin users delete permission
        if (! $user || ! $targetUser) {
            return false;
        }

        if (! $user->is_super_admin && ! $user->hasPermissionTo('admin.users.delete', 'superadmin')) {
            return false;
        }

        // Users cannot delete themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Only superadmins can delete other superadmins
        if ($targetUser->is_super_admin && ! $user->is_super_admin) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Add confirmation field for security
            'confirm_delete' => ['required', 'string', 'in:DELETE,CONFIRMAR'],
            'delete_reason' => ['required', 'string', 'max:500'],
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
            'confirm_delete.required' => 'Debes confirmar la eliminación del usuario.',
            'confirm_delete.in' => 'La confirmación debe ser exactamente "DELETE" o "CONFIRMAR".',
            'delete_reason.required' => 'El motivo de eliminación es obligatorio.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $targetUser = $this->route('user');

            // Prevent deletion of last superadmin
            if ($targetUser?->is_super_admin) {
                $superadminCount = \App\Models\User::where('is_super_admin', true)->count();

                if ($superadminCount <= 1) {
                    $validator->errors()->add('confirm_delete', 'No se puede eliminar al último Super Admin del sistema.');
                }
            }
        });
    }

    /**
     * Get audit data for logging
     */
    public function getAuditData(): array
    {
        $user = Auth::guard('superadmin')->user();
        $targetUser = $this->route('user');

        return [
            'action' => 'user_deleted',
            'target_user_id' => $targetUser?->id,
            'target_user_email' => $targetUser?->email,
            'target_user_name' => $targetUser?->name,
            'target_was_super_admin' => $targetUser?->is_super_admin ?? false,
            'performed_by' => $user?->id,
            'performed_by_email' => $user?->email,
            'delete_reason' => $this->input('delete_reason'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
    }
}
