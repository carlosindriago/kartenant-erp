<?php

namespace App\Contracts;

use App\Models\TenantSubscription;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Get the driver name
     */
    public function getName(): string;

    /**
     * Check if the driver is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Create a checkout session for a subscription
     * 
     * @return array ['type' => 'manual|redirect|embedded', 'data' => mixed]
     */
    public function createCheckoutSession(TenantSubscription $subscription): array;

    /**
     * Handle webhook from payment provider
     */
    public function handleWebhook(Request $request): bool;

    /**
     * Get the view name for the checkout page
     */
    public function getCheckoutView(): string;

    /**
     * Get display name for the UI
     */
    public function getDisplayName(): string;
}
