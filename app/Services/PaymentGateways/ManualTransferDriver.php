<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentTransaction;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;

class ManualTransferDriver implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'manual_transfer';
    }

    public function getDisplayName(): string
    {
        return 'Transferencia Bancaria Manual';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['bank_name']) && ! empty($this->config['account_number']);
    }

    public function createCheckoutSession(TenantSubscription $subscription): array
    {
        // Create a pending payment transaction
        $transaction = PaymentTransaction::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'gateway_driver' => $this->getName(),
            'amount' => $subscription->plan->getPrice($subscription->billing_cycle),
            'currency' => $this->config['currency'] ?? 'USD',
            'status' => PaymentTransaction::STATUS_PENDING,
            'metadata' => [
                'subscription_plan' => $subscription->plan->name,
                'billing_cycle' => $subscription->billing_cycle,
            ],
        ]);

        return [
            'type' => 'manual',
            'transaction_id' => $transaction->id,
            'bank_details' => [
                'bank_name' => $this->config['bank_name'] ?? '',
                'account_holder' => $this->config['account_holder'] ?? '',
                'account_number' => $this->config['account_number'] ?? '',
                'cbu' => $this->config['cbu'] ?? '',
                'alias' => $this->config['alias'] ?? '',
                'currency' => $this->config['currency'] ?? 'USD',
                'amount' => $transaction->amount,
                'reference' => "SUB-{$subscription->id}",
            ],
            'instructions' => $this->config['instructions'] ?? 'Por favor enviar el comprobante de pago a pagos@emporiodigital.com',
        ];
    }

    public function handleWebhook(Request $request): bool
    {
        // Manual transfers don't have webhooks
        return false;
    }

    public function getCheckoutView(): string
    {
        return 'checkout.manual-transfer';
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }
}
