<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentGatewaySetting;
use App\Models\SystemSetting;
use App\Services\PaymentGateways\LemonSqueezyDriver;
use App\Services\PaymentGateways\ManualTransferDriver;

class PaymentGatewayManager
{
    /**
     * Get the active payment gateway driver
     */
    public function getActiveDriver(): PaymentGatewayInterface
    {
        // Get active gateway name from system settings
        $activeGatewayName = SystemSetting::get('payments.active_gateway', 'manual_transfer');

        // Get gateway configuration
        $gateway = PaymentGatewaySetting::where('driver_name', $activeGatewayName)
            ->where('is_active', true)
            ->first();

        if (! $gateway) {
            // Fallback to manual transfer
            return new ManualTransferDriver([]);
        }

        return $this->instantiateDriver($gateway->driver_name, $gateway->config ?? []);
    }

    /**
     * Get a specific driver by name
     */
    public function getDriver(string $driverName): PaymentGatewayInterface
    {
        $gateway = PaymentGatewaySetting::where('driver_name', $driverName)->first();

        if (! $gateway) {
            throw new \InvalidArgumentException("Gateway '{$driverName}' not found");
        }

        return $this->instantiateDriver($gateway->driver_name, $gateway->config ?? []);
    }

    /**
     * Get all available drivers
     */
    public function getAllDrivers(): array
    {
        return PaymentGatewaySetting::ordered()->get()->map(function ($gateway) {
            return [
                'name' => $gateway->driver_name,
                'display_name' => $gateway->display_name,
                'is_active' => $gateway->is_active,
                'is_configured' => $gateway->isConfigured(),
                'driver' => $this->instantiateDriver($gateway->driver_name, $gateway->config ?? []),
            ];
        })->toArray();
    }

    /**
     * Check if a gateway is active
     */
    public function isGatewayActive(string $driverName): bool
    {
        return PaymentGatewaySetting::where('driver_name', $driverName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Activate a gateway
     */
    public function activateGateway(string $driverName): bool
    {
        // Deactivate all other gateways
        PaymentGatewaySetting::where('driver_name', '!=', $driverName)
            ->update(['is_active' => false]);

        // Activate the specified gateway
        $gateway = PaymentGatewaySetting::where('driver_name', $driverName)->first();

        if (! $gateway) {
            return false;
        }

        $gateway->update(['is_active' => true]);

        // Update system setting
        SystemSetting::set('payments.active_gateway', $driverName, 'string', 'payments');

        return true;
    }

    /**
     * Instantiate a driver instance
     */
    protected function instantiateDriver(string $driverName, array $config): PaymentGatewayInterface
    {
        return match ($driverName) {
            'manual_transfer' => new ManualTransferDriver($config),
            'lemon_squeezy' => new LemonSqueezyDriver($config),
            default => throw new \InvalidArgumentException("Unknown driver: {$driverName}"),
        };
    }

    /**
     * Register available drivers in database
     */
    public static function seedDefaultGateways(): void
    {
        $gateways = [
            [
                'driver_name' => 'manual_transfer',
                'display_name' => 'Transferencia Bancaria Manual',
                'is_active' => true,
                'sort_order' => 1,
                'config' => [
                    'bank_name' => '',
                    'account_holder' => '',
                    'account_number' => '',
                    'cbu' => '',
                    'alias' => '',
                    'currency' => 'USD',
                    'instructions' => 'Enviar comprobante a pagos@emporiodigital.com',
                ],
            ],
            [
                'driver_name' => 'lemon_squeezy',
                'display_name' => 'Lemon Squeezy',
                'is_active' => false,
                'sort_order' => 2,
                'config' => [
                    'api_key' => '',
                    'webhook_secret' => '',
                    'store_id' => '',
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGatewaySetting::updateOrCreate(
                ['driver_name' => $gateway['driver_name']],
                $gateway
            );
        }
    }
}
