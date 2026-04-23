<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Sale;
use Illuminate\Console\Command;

class DiagnoseCashRegisterSales extends Command
{
    protected $signature = 'pos:diagnose-sales {tenant}';

    protected $description = 'Diagnostica ventas y cajas registradoras';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant');

        $tenant = Tenant::where('domain', $tenantDomain)->first();

        if (! $tenant) {
            $this->error("❌ Tenant '{$tenantDomain}' no encontrado");

            return 1;
        }

        // Hacer que el tenant sea el actual
        $tenant->makeCurrent();

        $this->info("🔍 Diagnóstico para tenant: {$tenant->name}");
        $this->newLine();

        // Verificar cajas registradoras
        $registers = CashRegister::orderBy('id', 'desc')->limit(5)->get();

        $this->info('📦 CAJAS REGISTRADORAS:');
        if ($registers->isEmpty()) {
            $this->warn('  ⚠️  No hay cajas registradoras');
        } else {
            foreach ($registers as $register) {
                $this->info("  ID: {$register->id} | {$register->register_number} | Usuario: {$register->opened_by_user_id} | Estado: {$register->status}");

                // Contar ventas de esta caja
                $salesCount = Sale::where('cash_register_id', $register->id)->count();
                $salesTotal = Sale::where('cash_register_id', $register->id)
                    ->where('status', 'completed')
                    ->sum('total');

                $this->comment("    └─ Ventas: {$salesCount} | Total: \$".number_format($salesTotal, 2));
            }
        }

        $this->newLine();

        // Verificar ventas
        $this->info('🛒 VENTAS:');
        $totalSales = Sale::count();
        $salesWithoutRegister = Sale::whereNull('cash_register_id')->count();

        $this->info("  Total de ventas: {$totalSales}");
        $this->info("  Ventas sin caja asignada: {$salesWithoutRegister}");

        if ($salesWithoutRegister > 0) {
            $this->warn('  ⚠️  HAY VENTAS SIN CAJA ASIGNADA!');
        }

        $this->newLine();

        // Últimas 5 ventas
        $recentSales = Sale::orderBy('id', 'desc')->limit(5)->get();

        if ($recentSales->isEmpty()) {
            $this->warn('  No hay ventas registradas');
        } else {
            $this->info('  Últimas 5 ventas:');
            foreach ($recentSales as $sale) {
                $this->info("    ID: {$sale->id} | Total: \${$sale->total} | Método: {$sale->payment_method} | Estado: {$sale->status} | Caja ID: ".($sale->cash_register_id ?? 'NULL'));
            }
        }

        $this->newLine();

        // Verificar por usuario
        $this->info('👥 VENTAS POR USUARIO:');
        $users = Sale::select('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($users as $userId) {
            $userSales = Sale::where('user_id', $userId)->count();
            $userTotal = Sale::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('total');

            $user = \App\Models\User::find($userId);
            $userName = $user ? $user->name : "Usuario ID {$userId}";

            $this->info("  {$userName}: {$userSales} ventas | Total: \$".number_format($userTotal, 2));
        }

        $tenant->forgetCurrent();

        return 0;
    }
}
