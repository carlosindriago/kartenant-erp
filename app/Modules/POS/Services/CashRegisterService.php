<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Services;

use App\Models\User;
use App\Modules\POS\Models\CashRegister;
use App\Notifications\CashRegisterForcedClosureNotification;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Abre una nueva caja
     */
    public function openRegister(float $initialAmount, ?string $notes = null): CashRegister
    {
        $user = auth('tenant')->user() ?? auth('web')->user();

        // Verificar que EL USUARIO no tenga ya una caja abierta
        if (CashRegister::userHasOpenRegister($user->id)) {
            throw new \Exception('Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.');
        }

        DB::beginTransaction();

        try {

            $cashRegister = CashRegister::create([
                'opened_at' => now(),
                'opened_by_user_id' => $user->id,
                'initial_amount' => $initialAmount,
                'status' => 'open',
                'opening_notes' => $notes,
            ]);

            // Auditoría
            activity()
                ->causedBy($user)
                ->performedOn($cashRegister)
                ->withProperties([
                    'register_number' => $cashRegister->register_number,
                    'initial_amount' => $initialAmount,
                    'opened_by' => $user->name,
                    'opened_at' => $cashRegister->opened_at->format('Y-m-d H:i:s'),
                    'notes' => $notes,
                ])
                ->log('💵 Apertura de Caja');

            DB::commit();

            return $cashRegister;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cierra una caja con arqueo
     */
    public function closeRegister(
        CashRegister $cashRegister,
        float $actualAmount,
        ?array $cashBreakdown = null,
        ?string $notes = null
    ): CashRegister {
        if ($cashRegister->isClosed()) {
            throw new \Exception('Esta caja ya está cerrada.');
        }

        DB::beginTransaction();

        try {
            $user = auth('tenant')->user() ?? auth('web')->user();

            // Calcular monto esperado
            $expectedAmount = $cashRegister->calculateExpectedAmount();
            $difference = $actualAmount - $expectedAmount;

            // Obtener resumen de ventas
            $salesSummary = $cashRegister->getSalesSummary();

            // Actualizar caja
            $cashRegister->update([
                'closed_at' => now(),
                'closed_by_user_id' => $user->id,
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'difference' => $difference,
                'cash_breakdown' => $cashBreakdown,
                'status' => 'closed',
                'closing_notes' => $notes,
            ]);

            // Generar hash de seguridad único e inmutable para el reporte
            $cashRegister->ensureVerificationHash();

            // Auditoría detallada
            activity()
                ->causedBy($user)
                ->performedOn($cashRegister)
                ->withProperties([
                    'register_number' => $cashRegister->register_number,
                    'closed_by' => $user->name,
                    'closed_at' => $cashRegister->closed_at->format('Y-m-d H:i:s'),
                    'initial_amount' => $cashRegister->initial_amount,
                    'expected_amount' => $expectedAmount,
                    'actual_amount' => $actualAmount,
                    'difference' => $difference,
                    'difference_type' => $difference > 0 ? 'sobrante' : ($difference < 0 ? 'faltante' : 'exacto'),
                    'sales_summary' => $salesSummary,
                    'cash_breakdown' => $cashBreakdown,
                    'notes' => $notes,
                ])
                ->log('🔒 Cierre de Caja');

            DB::commit();

            return $cashRegister->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el reporte de ingresos del día
     */
    public function getDailyReport(CashRegister $cashRegister): array
    {
        // Asegurar que tenga hash de seguridad (si está cerrada)
        if ($cashRegister->isClosed()) {
            $cashRegister->ensureVerificationHash();
        }

        $salesSummary = $cashRegister->getSalesSummary();

        // Calcular el total esperado en efectivo (inicial + ventas completadas en efectivo)
        // NOTA: NO se restan las ventas canceladas porque el dinero ya fue devuelto al cliente
        $expectedCashTotal = $cashRegister->initial_amount + $salesSummary['cash_sales'];

        return [
            'register_info' => [
                'register_number' => $cashRegister->register_number,
                'opened_at' => $cashRegister->opened_at,
                'closed_at' => $cashRegister->closed_at,
                'opened_by' => $cashRegister->openedBy->name,
                'closed_by' => $cashRegister->closedBy?->name,
                'status' => $cashRegister->status,
            ],
            'security' => [
                'verification_hash' => $cashRegister->verification_hash,
                'verification_generated_at' => $cashRegister->verification_generated_at,
                'verification_url' => $cashRegister->verification_hash ? $cashRegister->getInternalVerificationRoute() : null,
            ],
            'cash_flow' => [
                'initial_amount' => $cashRegister->initial_amount,
                'cash_sales' => $salesSummary['cash_sales'],
                'cash_returns' => $salesSummary['cash_returns'], // Solo para referencia estadística
                'expected_cash_total' => $expectedCashTotal, // Total esperado en efectivo para contar
                'expected_amount' => $cashRegister->expected_amount,
                'actual_amount' => $cashRegister->actual_amount,
                'difference' => $cashRegister->difference,
            ],
            'sales_summary' => $salesSummary,
            'payment_methods' => [
                'cash' => $salesSummary['cash_sales'],
                'card' => $salesSummary['card_sales'],
                'transfer' => $salesSummary['transfer_sales'],
            ],
            'cash_breakdown' => $cashRegister->cash_breakdown,
            'notes' => [
                'opening' => $cashRegister->opening_notes,
                'closing' => $cashRegister->closing_notes,
            ],
        ];
    }

    /**
     * Cierre forzado de caja por administrador
     *
     * @param  CashRegister  $cashRegister  Caja a cerrar
     * @param  float  $actualAmount  Monto contado
     * @param  string  $reason  Motivo del cierre forzado
     * @param  int  $forcedByUserId  Usuario administrador que fuerza el cierre
     * @param  array|null  $cashBreakdown  Desglose de efectivo (opcional)
     */
    public function forceClosureByAdmin(
        CashRegister $cashRegister,
        float $actualAmount,
        string $reason,
        int $forcedByUserId,
        ?array $cashBreakdown = null
    ): CashRegister {
        if ($cashRegister->isClosed()) {
            throw new \Exception('Esta caja ya está cerrada.');
        }

        return DB::transaction(function () use (
            $cashRegister,
            $actualAmount,
            $reason,
            $forcedByUserId,
            $cashBreakdown
        ) {
            $forcedByUser = User::find($forcedByUserId);

            // Calcular monto esperado
            $expectedAmount = $cashRegister->calculateExpectedAmount();
            $difference = $actualAmount - $expectedAmount;

            // Obtener el usuario dueño de la caja
            $cashierUser = User::find($cashRegister->opened_by_user_id);

            // Actualizar caja con información de cierre forzado
            $cashRegister->update([
                'closed_at' => now(),
                'closed_by_user_id' => $cashRegister->opened_by_user_id, // Se registra como si el cajero la cerrara
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'difference' => $difference,
                'cash_breakdown' => $cashBreakdown,
                'status' => 'closed',
                'closing_notes' => "CIERRE FORZADO POR ADMINISTRADOR\nMotivo: {$reason}",
                'forced_closure' => true,
                'forced_by_user_id' => $forcedByUserId,
                'forced_reason' => $reason,
            ]);

            // Log de actividad detallado
            activity()
                ->causedBy($forcedByUser)
                ->performedOn($cashRegister)
                ->withProperties([
                    'action' => 'forced_closure',
                    'register_number' => $cashRegister->register_number,
                    'forced_by' => $forcedByUser->name,
                    'cashier' => $cashierUser->name,
                    'cashier_id' => $cashierUser->id,
                    'reason' => $reason,
                    'expected_amount' => $expectedAmount,
                    'actual_amount' => $actualAmount,
                    'difference' => $difference,
                    'forced_at' => now()->format('Y-m-d H:i:s'),
                ])
                ->log('⚠️ Cierre Forzado de Caja por Administrador');

            // Enviar notificación al cajero afectado
            if ($cashierUser) {
                try {
                    // Intentar enviar notificación (requiere tabla notifications en tenant DB)
                    $cashierUser->notify(new CashRegisterForcedClosureNotification(
                        $cashRegister,
                        $reason,
                        $forcedByUser->name
                    ));
                } catch (\Exception $e) {
                    // Si falla, solo registrar en log pero continuar
                    \Log::warning('No se pudo enviar notificación de cierre forzado', [
                        'error' => $e->getMessage(),
                        'cashier_id' => $cashierUser->id,
                        'register_number' => $cashRegister->register_number,
                    ]);
                }
            }

            return $cashRegister->fresh();
        });
    }

    /**
     * Valida si se puede realizar una venta (debe haber caja abierta)
     */
    public function validateRegisterForSale(): CashRegister
    {
        $currentRegister = CashRegister::getCurrentOpen();

        if (! $currentRegister) {
            throw new \Exception('Debe abrir una caja antes de realizar ventas.');
        }

        return $currentRegister;
    }
}
