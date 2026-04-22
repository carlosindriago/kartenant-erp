<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\PaymentGatewayManager;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        PaymentGatewayManager::seedDefaultGateways();
        
        $this->command->info('✅ Payment gateways seeded successfully!');
        $this->command->info('   - Manual Transfer (Active)');
        $this->command->info('   - Lemon Squeezy (Inactive)');
    }
}
