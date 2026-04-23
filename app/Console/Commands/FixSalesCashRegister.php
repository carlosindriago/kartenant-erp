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

class FixSalesCashRegister extends Command
{
    protected $signature = 'pos:fix-sales-cash-register {tenant}';

    protected $description = 'Corrige ventas sin cash_register_id asignado';

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

        $this->info("🔧 Corrigiendo ventas sin caja asignada para: {$tenant->name}");
        $this->newLine();

        // Obtener ventas sin caja asignada
        $salesWithoutRegister = Sale::whereNull('cash_register_id')->get();

        if ($salesWithoutRegister->isEmpty()) {
            $this->info('✅ No hay ventas sin caja asignada');
            $tenant->forgetCurrent();

            return 0;
        }

        $this->info("📊 Ventas sin caja: {$salesWithoutRegister->count()}");
        $this->newLine();

        $fixed = 0;
        $notFixed = 0;

        foreach ($salesWithoutRegister as $sale) {
            // Buscar caja del usuario que estaba abierta en el momento de la venta
            $cashRegister = CashRegister::where('opened_by_user_id', $sale->user_id)
                ->where('opened_at', '<=', $sale->created_at)
                ->where(function ($query) use ($sale) {
                    $query->whereNull('closed_at')
                        ->orWhere('closed_at', '>=', $sale->created_at);
                })
                ->orderBy('opened_at', 'desc')
                ->first();

            if ($cashRegister) {
                $sale->update(['cash_register_id' => $cashRegister->id]);
                $this->info("  ✓ Venta ID {$sale->id} → Caja {$cashRegister->register_number}");
                $fixed++;
            } else {
                $this->warn("  ✗ Venta ID {$sale->id} → No se encontró caja abierta del usuario {$sale->user_id}");
                $notFixed++;
            }
        }

        $this->newLine();
        $this->info('📊 RESUMEN:');
        $this->info("   Ventas corregidas: {$fixed}");
        if ($notFixed > 0) {
            $this->warn("   Ventas sin corregir: {$notFixed}");
            $this->newLine();
            $this->comment('💡 Las ventas sin corregir podrían ser de usuarios sin caja abierta.');
            $this->comment('   Puedes asignarlas manualmente o abrirles una caja primero.');
        }

        $tenant->forgetCurrent();

        return 0;
    }
}
