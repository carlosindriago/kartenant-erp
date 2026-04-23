<?php

namespace Database\Seeders;

use App\Modules\Inventory\Models\MovementReason;
use Illuminate\Database\Seeder;

class MovementReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates predefined movement reasons for inventory management.
     * Includes common entry and exit reasons for stock movements.
     */
    public function run(): void
    {
        $tenant = \Spatie\Multitenancy\Models\Tenant::current();

        if (! $tenant) {
            $this->command->error('❌ No tenant context found. This seeder must run within a tenant context.');

            return;
        }

        $tenantId = $tenant->id;

        // Motivos de Entrada predeterminados
        $entryReasons = [
            'Compra a Proveedor',
            'Devolución de Cliente',
            'Ajuste de Inventario (Aumento)',
            'Producción Interna',
            'Donación Recibida',
        ];

        // Motivos de Salida predeterminados
        $exitReasons = [
            'Venta',
            'Producto Dañado',
            'Uso Interno',
            'Devolución a Proveedor',
            'Ajuste de Inventario (Disminución)',
            'Donación Entregada',
            'Merma o Vencimiento',
        ];

        MovementReason::withoutEvents(function () use ($entryReasons, $exitReasons) {
            foreach ($entryReasons as $reason) {
                MovementReason::firstOrCreate(
                    [
                        'name' => $reason,
                        'type' => 'entrada',
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }

            foreach ($exitReasons as $reason) {
                MovementReason::firstOrCreate(
                    [
                        'name' => $reason,
                        'type' => 'salida',
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        });

        $totalReasons = count($entryReasons) + count($exitReasons);
        $this->command->info("✅ {$totalReasons} motivos de movimiento predeterminados creados (".count($entryReasons).' entrada, '.count($exitReasons).' salida).');
    }
}
