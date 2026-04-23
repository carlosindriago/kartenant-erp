<?php

namespace Database\Seeders;

use App\Models\PaymentSettings;
use Illuminate\Database\Seeder;

class PaymentSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Default bank details for Ernesto's easy understanding
        // Argentina-style bank details with CBU and Alias
        PaymentSettings::updateOrCreate(
            ['id' => 1], // Single record system-wide settings
            [
                // Bank transfer details (Argentina style)
                'bank_name' => 'Banco Galicia',
                'bank_account_number' => '0070067430000000123456', // CBU
                'bank_account_holder' => 'Emporio Digital S.A.',
                'bank_routing_number' => 'EMPORIO.DIGITAL.PAGO', // Alias

                // Business identification
                'business_name' => 'Emporio Digital S.A.',
                'business_tax_id' => '30-12345678-9', // CUIT

                // Payment instructions (Spanish for Ernesto)
                'payment_instructions' => 'Realice la transferencia bancaria a los datos proporcionados y suba el comprobante para activar su suscripción.',

                // Default settings
                'manual_approval_required' => true,
                'approval_timeout_hours' => 48,
                'auto_reminder_enabled' => true,
                'max_file_size_mb' => 5,
                'default_currency' => 'ARS',
                'locale' => 'es',

                // Allowed file types for payment proof
                'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
            ]
        );

        $this->command->info('✅ Payment settings seeded with Argentine bank details for Ernesto');
    }
}
