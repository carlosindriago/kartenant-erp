<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Policies;

use App\Models\User;
use App\Modules\POS\Models\CashRegister;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * CashRegisterPolicy
 * 
 * Define las políticas de acceso para las cajas registradoras
 * Soporta control granular por usuario y rol
 */
class CashRegisterPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver cualquier caja
     * (Admin/Supervisor pueden ver todas)
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pos.view_all_registers') || 
               $user->can('pos.access');
    }

    /**
     * Determina si el usuario puede ver una caja específica
     * (Usuarios ven solo su caja, supervisores ven todas)
     */
    public function view(User $user, CashRegister $cashRegister): bool
    {
        // Supervisores pueden ver todas
        if ($user->can('pos.view_all_registers')) {
            return true;
        }
        
        // Usuarios pueden ver su propia caja
        return $cashRegister->belongsToUser($user->id);
    }

    /**
     * Determina si el usuario puede abrir una nueva caja
     */
    public function create(User $user): bool
    {
        // Debe tener permiso básico de POS y apertura de caja
        if (!$user->can('pos.open_register')) {
            return false;
        }
        
        // No puede tener ya una caja abierta
        return !CashRegister::userHasOpenRegister($user->id);
    }

    /**
     * Determina si el usuario puede actualizar una caja
     * (Solo mientras esté abierta y sea su caja, o sea supervisor)
     */
    public function update(User $user, CashRegister $cashRegister): bool
    {
        // Supervisores pueden actualizar cualquiera
        if ($user->can('pos.close_any_register')) {
            return true;
        }
        
        // Usuarios solo pueden actualizar su propia caja si está abierta
        return $cashRegister->isOpen() && $cashRegister->belongsToUser($user->id);
    }

    /**
     * Determina si el usuario puede cerrar una caja específica
     */
    public function close(User $user, CashRegister $cashRegister): bool
    {
        // La caja debe estar abierta
        if (!$cashRegister->isOpen()) {
            return false;
        }
        
        // Supervisores pueden cerrar cualquier caja
        if ($user->can('pos.close_any_register')) {
            return true;
        }
        
        // Usuarios solo pueden cerrar su propia caja
        return $user->can('pos.close_register') && 
               $cashRegister->belongsToUser($user->id);
    }

    /**
     * Determina si el usuario puede forzar el cierre de una caja
     * (Solo supervisores/gerentes)
     */
    public function forceClose(User $user, CashRegister $cashRegister): bool
    {
        return $user->can('pos.close_any_register') && 
               $cashRegister->isOpen();
    }

    /**
     * Determina si el usuario puede eliminar una caja
     * (Solo admin/superadmin, y solo si está cerrada y sin ventas)
     */
    public function delete(User $user, CashRegister $cashRegister): bool
    {
        // Solo superadmin
        if (!$user->is_super_admin) {
            return false;
        }
        
        // Solo si está cerrada y no tiene ventas
        return $cashRegister->isClosed() && 
               $cashRegister->sales()->count() === 0;
    }

    /**
     * Determina si el usuario puede restaurar una caja eliminada
     */
    public function restore(User $user, CashRegister $cashRegister): bool
    {
        return $user->is_super_admin;
    }

    /**
     * Determina si el usuario puede eliminar permanentemente una caja
     */
    public function forceDelete(User $user, CashRegister $cashRegister): bool
    {
        return $user->is_super_admin;
    }

    /**
     * Determina si el usuario puede ver el historial completo de cajas
     */
    public function viewHistory(User $user): bool
    {
        return $user->can('pos.view_all_registers') || 
               $user->can('pos.view_reports');
    }

    /**
     * Determina si el usuario puede ver reportes de otros usuarios
     */
    public function viewOthersReports(User $user): bool
    {
        return $user->can('pos.view_all_registers') || 
               $user->can('pos.view_reports');
    }

    /**
     * Determina si el usuario puede exportar reportes
     */
    public function export(User $user): bool
    {
        return $user->can('pos.view_reports');
    }
}
