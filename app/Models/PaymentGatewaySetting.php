<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $connection = 'landlord';

    protected $table = 'payment_gateway_settings';

    protected $fillable = [
        'driver_name',
        'is_active',
        'display_name',
        'config',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the active gateway
     */
    public static function getActive(): ?self
    {
        return self::active()->ordered()->first();
    }

    /**
     * Get gateway by driver name
     */
    public static function findByDriver(string $driverName): ?self
    {
        return self::where('driver_name', $driverName)->first();
    }

    /**
     * Check if gateway is configured (has config data)
     */
    public function isConfigured(): bool
    {
        return ! empty($this->config);
    }

    /**
     * Get config value
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }
}
