<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services\POS;

use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * CashRegisterService
 * 
 * Servicio para manejar la lógica de negocio de cajas registradoras
 * Soporta múltiples usuarios con cajas abiertas simultáneamente
 */
class CashRegisterService
{
    /**
     * Abre una nueva caja para un usuario
     * 
     * @param int $userId ID del usuario que abre la caja
     * @param float $initialAmount Monto inicial en la caja
     * @param string|null $notes Notas de apertura
     * @return CashRegister
     * @throws \Exception Si el usuario ya tiene una caja abierta
     */
    public function openRegister(int $userId, float $initialAmount, ?string $notes = null): CashRegister
    {
        // Verificar que el usuario no tenga ya una caja abierta
        if (CashRegister::userHasOpenRegister($userId)) {
            throw new \Exception('Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.');
        }
        
        // Validar monto inicial
        if ($initialAmount < 0) {
            throw new \Exception('El monto inicial no puede ser negativo.');
        }
        
        return DB::transaction(function () use ($userId, $initialAmount, $notes) {
            $cashRegister = CashRegister::create([
                'opened_by_user_id' => $userId,
                'opened_at' => now(),
                'initial_amount' => $initialAmount,
                'status' => 'open',
                'opening_notes' => $notes,
            ]);
            
            // Log de actividad
            activity()
                ->performedOn($cashRegister)
                ->causedBy($userId)
                ->withProperties([
                    'action' => 'open_register',
                    'initial_amount' => $initialAmount,
                    'register_number' => $cashRegister->register_number,
                ])
                ->log('Caja abierta');
            
            return $cashRegister;
        });
    }
    
    /**
     * Cierra una caja existente
     * 
     * @param int $registerId ID del registro de caja
     * @param float $actualAmount Monto real contado en la caja
     * @param array|null $cashBreakdown Desglose de billetes y monedas
     * @param string|null $notes Notas de cierre
     * @param int|null $closedByUserId Usuario que cierra (null = usuario actual)
     * @return CashRegister
     * @throws \Exception Si la caja no existe, ya está cerrada, o el usuario no tiene permiso
     */
    public function closeRegister(
        int $registerId,
        float $actualAmount,
        ?array $cashBreakdown = null,
        ?string $notes = null,
        ?int $closedByUserId = null
    ): CashRegister {
        $cashRegister = CashRegister::findOrFail($registerId);
        $closedByUserId = $closedByUserId ?? auth('tenant')->id();
        
        // Verificar que la caja esté abierta
        if (!$cashRegister->isOpen()) {
            throw new \Exception('Esta caja ya está cerrada.');
        }
        
        // Calcular monto esperado
        $expectedAmount = $cashRegister->calculateExpectedAmount();
        
        // Calcular diferencia
        $difference = $actualAmount - $expectedAmount;
        
        return DB::transaction(function () use (
            $cashRegister,
            $actualAmount,
            $expectedAmount,
            $difference,
            $cashBreakdown,
            $notes,
            $closedByUserId
        ) {
            $cashRegister->update([
                'closed_at' => now(),
                'closed_by_user_id' => $closedByUserId,
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'difference' => $difference,
                'cash_breakdown' => $cashBreakdown,
                'closing_notes' => $notes,
                'status' => 'closed',
            ]);
            
            // Log de actividad
            activity()
                ->performedOn($cashRegister)
                ->causedBy($closedByUserId)
                ->withProperties([
                    'action' => 'close_register',
                    'expected_amount' => $expectedAmount,
                    'actual_amount' => $actualAmount,
                    'difference' => $difference,
                    'register_number' => $cashRegister->register_number,
                ])
                ->log('Caja cerrada');
            
            return $cashRegister->fresh();
        });
    }
    
    /**
     * Verifica si un usuario puede abrir una caja
     * 
     * @param int $userId
     * @return array ['can_open' => bool, 'reason' => string|null]
     */
    public function canUserOpenRegister(int $userId): array
    {
        // Verificar si ya tiene una caja abierta
        if (CashRegister::userHasOpenRegister($userId)) {
            $openRegister = CashRegister::getUserOpenRegister($userId);
            return [
                'can_open' => false,
                'reason' => "Ya tienes una caja abierta desde " . 
                           $openRegister->opened_at->format('H:i') . 
                           " (Registro: {$openRegister->register_number})",
            ];
        }
        
        return [
            'can_open' => true,
            'reason' => null,
        ];
    }
    
    /**
     * Verifica si un usuario puede cerrar una caja específica
     * 
     * @param int $userId
     * @param int $registerId
     * @param bool $isSupervisor Si el usuario tiene permisos de supervisor
     * @return array ['can_close' => bool, 'reason' => string|null]
     */
    public function canUserCloseRegister(int $userId, int $registerId, bool $isSupervisor = false): array
    {
        $cashRegister = CashRegister::find($registerId);
        
        if (!$cashRegister) {
            return [
                'can_close' => false,
                'reason' => 'La caja no existe.',
            ];
        }
        
        if (!$cashRegister->isOpen()) {
            return [
                'can_close' => false,
                'reason' => 'Esta caja ya está cerrada.',
            ];
        }
        
        // Supervisores pueden cerrar cualquier caja
        if ($isSupervisor) {
            return [
                'can_close' => true,
                'reason' => null,
            ];
        }
        
        // Usuarios normales solo pueden cerrar su propia caja
        if (!$cashRegister->belongsToUser($userId)) {
            return [
                'can_close' => false,
                'reason' => 'Solo puedes cerrar tu propia caja. Esta caja pertenece a otro usuario.',
            ];
        }
        
        return [
            'can_close' => true,
            'reason' => null,
        ];
    }
    
    /**
     * Obtiene el resumen del turno actual de una caja
     * 
     * @param int $registerId
     * @return array
     */
    public function getCashRegisterSummary(int $registerId): array
    {
        $cashRegister = CashRegister::with('openedBy')->findOrFail($registerId);
        
        $summary = $cashRegister->getSalesSummary();
        $expectedAmount = $cashRegister->calculateExpectedAmount();
        
        return array_merge($summary, [
            'register_number' => $cashRegister->register_number,
            'opened_by' => $cashRegister->openedBy->name ?? 'N/A',
            'opened_at' => $cashRegister->opened_at,
            'hours_open' => $cashRegister->opened_at->diffInHours(now()),
            'initial_amount' => $cashRegister->initial_amount,
            'expected_cash_amount' => $expectedAmount,
        ]);
    }
    
    /**
     * Obtiene historial de cajas de un usuario
     * 
     * @param int $userId
     * @param int $days Días hacia atrás
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCashRegisterHistory(int $userId, int $days = 30)
    {
        return CashRegister::ownedBy($userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('opened_at', 'desc')
            ->get();
    }
    
    /**
     * Obtiene estadísticas de un usuario
     * 
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getUserStatistics(int $userId, int $days = 30): array
    {
        $registers = CashRegister::ownedBy($userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', 'closed')
            ->get();
        
        $totalRegisters = $registers->count();
        $totalDifferences = $registers->sum('difference');
        $avgDifference = $totalRegisters > 0 ? $totalDifferences / $totalRegisters : 0;
        
        $surpluses = $registers->where('difference', '>', 0)->count();
        $shortages = $registers->where('difference', '<', 0)->count();
        $exact = $registers->where('difference', 0)->count();
        
        return [
            'total_registers' => $totalRegisters,
            'total_differences' => $totalDifferences,
            'average_difference' => $avgDifference,
            'surpluses' => $surpluses,
            'shortages' => $shortages,
            'exact_matches' => $exact,
            'accuracy_percentage' => $totalRegisters > 0 ? ($exact / $totalRegisters) * 100 : 0,
        ];
    }
    
    /**
     * Forzar cierre de caja (solo supervisores)
     * 
     * @param int $registerId
     * @param int $supervisorId
     * @param string $reason
     * @return CashRegister
     */
    public function forceCloseRegister(int $registerId, int $supervisorId, string $reason): CashRegister
    {
        $cashRegister = CashRegister::findOrFail($registerId);
        
        if (!$cashRegister->isOpen()) {
            throw new \Exception('Esta caja ya está cerrada.');
        }
        
        // Calcular monto esperado
        $expectedAmount = $cashRegister->calculateExpectedAmount();
        
        return DB::transaction(function () use ($cashRegister, $expectedAmount, $supervisorId, $reason) {
            $cashRegister->update([
                'closed_at' => now(),
                'closed_by_user_id' => $supervisorId,
                'expected_amount' => $expectedAmount,
                'actual_amount' => $expectedAmount, // Asumimos el esperado
                'difference' => 0,
                'closing_notes' => "CIERRE FORZADO POR SUPERVISOR: " . $reason,
                'status' => 'closed',
            ]);
            
            // Log de actividad
            activity()
                ->performedOn($cashRegister)
                ->causedBy($supervisorId)
                ->withProperties([
                    'action' => 'force_close_register',
                    'reason' => $reason,
                    'original_user_id' => $cashRegister->opened_by_user_id,
                ])
                ->log('Caja cerrada forzadamente por supervisor');
            
            return $cashRegister->fresh();
        });
    }
}
