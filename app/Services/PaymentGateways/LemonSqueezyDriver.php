<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;

class LemonSqueezyDriver implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'lemon_squeezy';
    }

    public function getDisplayName(): string
    {
        return 'Lemon Squeezy';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['store_id']);
    }

    public function createCheckoutSession(TenantSubscription $subscription): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Lemon Squeezy no está configurado correctamente');
        }

        // TODO: Implementar integración con Lemon Squeezy API
        // Por ahora retornamos estructura básica
        return [
            'type' => 'redirect',
            'checkout_url' => '#', // URL del checkout de Lemon Squeezy
            'session_id' => '',
        ];
    }

    public function handleWebhook(Request $request): bool
    {
        // TODO: Implementar validación y procesamiento de webhooks
        return false;
    }

    public function getCheckoutView(): string
    {
        return 'checkout.lemon-squeezy';
    }
}
