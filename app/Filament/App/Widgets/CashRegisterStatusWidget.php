<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Widgets;

use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

/**
 * CashRegisterStatusWidget: Estado de Caja Actual
 *
 * Muestra el estado actual de la(s) caja(s):
 * - Si hay caja abierta: monto inicial, ventas, esperado
 * - Si está cerrada: cuándo fue el último cierre
 * - Historial de últimos arqueos con diferencias
 * - Alertas si hay diferencias significativas
 */
class CashRegisterStatusWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.cash-register-status';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'lg' => 4,  // Full width in desktop
    ];

    protected static ?int $sort = 3;

    /**
     * Obtener estado actual de las cajas con información completa
     */
    public function getCurrentRegisters(): array
    {
        return Cache::remember('widget.cash_registers.current', 60, function () {
            return CashRegister::where('status', 'open')
                ->with(['openedBy'])
                ->orderBy('opened_at', 'desc')
                ->get()
                ->map(function ($register) {
                    // Ventas completadas desde la apertura
                    $completedSales = Sale::where('created_at', '>=', $register->opened_at)
                        ->where('status', 'completed');

                    // Ventas por método de pago
                    $cashSales = (clone $completedSales)->where('payment_method', 'cash')->sum('total');
                    $cardSales = (clone $completedSales)->where('payment_method', 'card')->sum('total');
                    $transferSales = (clone $completedSales)->where('payment_method', 'transfer')->sum('total');
                    $otherSales = (clone $completedSales)->whereNotIn('payment_method', ['cash', 'card', 'transfer'])->sum('total');

                    $totalSales = $cashSales + $cardSales + $transferSales + $otherSales;
                    $transactionCount = $completedSales->count();

                    // Devoluciones (ventas canceladas)
                    $returns = Sale::where('created_at', '>=', $register->opened_at)
                        ->where('status', 'cancelled')
                        ->where('payment_method', 'cash')
                        ->sum('total');

                    $returnsCount = Sale::where('created_at', '>=', $register->opened_at)
                        ->where('status', 'cancelled')
                        ->count();

                    // Cálculo del efectivo esperado en caja
                    $expectedCash = $register->initial_amount + $cashSales - $returns;

                    // Formatear tiempo abierto
                    $hoursOpen = $register->opened_at->diffInHours(now());
                    $minutesOpen = $register->opened_at->diffInMinutes(now()) % 60;
                    $timeOpen = $hoursOpen > 0
                        ? "{$hoursOpen}h ".($minutesOpen > 0 ? "{$minutesOpen}m" : '')
                        : "{$minutesOpen}m";

                    return [
                        'id' => $register->id,
                        'register_number' => $register->register_number,
                        'user_name' => $register->openedBy->name ?? 'Usuario',
                        'opened_at' => $register->opened_at,
                        'time_open' => trim($timeOpen),
                        'hours_open' => $hoursOpen,

                        // Montos iniciales
                        'initial_amount' => $register->initial_amount,

                        // Ventas por método
                        'cash_sales' => $cashSales,
                        'card_sales' => $cardSales,
                        'transfer_sales' => $transferSales,
                        'other_sales' => $otherSales,
                        'total_sales' => $totalSales,
                        'transaction_count' => $transactionCount,

                        // Devoluciones
                        'returns' => $returns,
                        'returns_count' => $returnsCount,

                        // Efectivo esperado en caja
                        'expected_cash' => $expectedCash,

                        // Ticket promedio
                        'average_ticket' => $transactionCount > 0 ? $totalSales / $transactionCount : 0,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Obtener último cierre si no hay cajas abiertas
     */
    public function getLastClosing(): ?array
    {
        if (count($this->getCurrentRegisters()) > 0) {
            return null;
        }

        return Cache::remember('widget.cash_registers.last_closing', 60, function () {
            $lastRegister = CashRegister::where('status', 'closed')
                ->orderBy('closed_at', 'desc')
                ->first();

            if (! $lastRegister) {
                return null;
            }

            $discrepancy = $lastRegister->actual_amount - $lastRegister->expected_amount;

            return [
                'closed_at' => $lastRegister->closed_at,
                'hours_ago' => $lastRegister->closed_at->diffInHours(now()),
                'expected_amount' => $lastRegister->expected_amount,
                'actual_amount' => $lastRegister->actual_amount,
                'discrepancy' => $discrepancy,
            ];
        });
    }

    /**
     * Obtener historial de últimos arqueos con diferencias
     */
    public function getRecentDiscrepancies(): array
    {
        return Cache::remember('widget.cash_registers.discrepancies', 300, function () {
            return CashRegister::where('status', 'closed')
                ->where('closed_at', '>', now()->subDays(7))
                ->whereRaw('ABS(expected_amount - actual_amount) > 10')
                ->orderBy('closed_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($register) {
                    $discrepancy = $register->actual_amount - $register->expected_amount;

                    return [
                        'date' => $register->closed_at,
                        'expected' => $register->expected_amount,
                        'actual' => $register->actual_amount,
                        'discrepancy' => $discrepancy,
                        'type' => $discrepancy > 0 ? 'surplus' : 'shortage',
                        'user_name' => $register->user->name ?? 'N/D',
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Calcular total de diferencias acumuladas (últimos 30 días)
     */
    public function getTotalDiscrepancies(): array
    {
        return Cache::remember('widget.cash_registers.total_discrepancies', 300, function () {
            $registers = CashRegister::where('status', 'closed')
                ->where('closed_at', '>', now()->subDays(30))
                ->selectRaw('
                    SUM(CASE WHEN actual_amount > expected_amount THEN actual_amount - expected_amount ELSE 0 END) as total_surplus,
                    SUM(CASE WHEN actual_amount < expected_amount THEN expected_amount - actual_amount ELSE 0 END) as total_shortage,
                    COUNT(*) as total_closings
                ')
                ->first();

            $netDiscrepancy = ($registers->total_surplus ?? 0) - ($registers->total_shortage ?? 0);

            return [
                'total_surplus' => $registers->total_surplus ?? 0,
                'total_shortage' => $registers->total_shortage ?? 0,
                'net_discrepancy' => $netDiscrepancy,
                'total_closings' => $registers->total_closings ?? 0,
            ];
        });
    }

    /**
     * Calcular totales generales de todas las cajas abiertas
     */
    public function getTotals(): array
    {
        $registers = $this->getCurrentRegisters();

        if (empty($registers)) {
            return [
                'initial_amount' => 0,
                'cash_sales' => 0,
                'card_sales' => 0,
                'transfer_sales' => 0,
                'other_sales' => 0,
                'total_sales' => 0,
                'transaction_count' => 0,
                'returns' => 0,
                'returns_count' => 0,
                'expected_cash' => 0,
                'average_ticket' => 0,
            ];
        }

        $totals = [
            'initial_amount' => array_sum(array_column($registers, 'initial_amount')),
            'cash_sales' => array_sum(array_column($registers, 'cash_sales')),
            'card_sales' => array_sum(array_column($registers, 'card_sales')),
            'transfer_sales' => array_sum(array_column($registers, 'transfer_sales')),
            'other_sales' => array_sum(array_column($registers, 'other_sales')),
            'total_sales' => array_sum(array_column($registers, 'total_sales')),
            'transaction_count' => array_sum(array_column($registers, 'transaction_count')),
            'returns' => array_sum(array_column($registers, 'returns')),
            'returns_count' => array_sum(array_column($registers, 'returns_count')),
            'expected_cash' => array_sum(array_column($registers, 'expected_cash')),
        ];

        // Calcular ticket promedio global
        $totals['average_ticket'] = $totals['transaction_count'] > 0
            ? $totals['total_sales'] / $totals['transaction_count']
            : 0;

        return $totals;
    }
}
