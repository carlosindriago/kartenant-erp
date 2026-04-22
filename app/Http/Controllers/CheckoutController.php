<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Show checkout page for a subscription
     */
    public function show(TenantSubscription $subscription)
    {
        // Get active payment gateway
        $driver = $this->gatewayManager->getActiveDriver();

        // Create checkout session
        $checkoutData = $driver->createCheckoutSession($subscription);

        // Return appropriate view based on driver type
        return view($driver->getCheckoutView(), [
            'subscription' => $subscription->load('tenant', 'plan'),
            'checkout' => $checkoutData,
            'driver' => $driver,
        ]);
    }

    /**
     * Upload payment proof for manual transfer
     */
    public function uploadProof(Request $request, PaymentTransaction $transaction)
    {
        $request->validate([
            'proof' => 'required|image|max:5120', // 5MB max
            'notes' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('payment-proofs', 'local');
            
            $transaction->update([
                'proof_of_payment' => $path,
                'status' => PaymentTransaction::STATUS_PENDING,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'notes' => $request->notes,
                    'uploaded_at' => now()->toIso8601String(),
                ]),
            ]);

            // Update subscription status
            $transaction->subscription->update([
                'payment_status' => 'pending_approval',
            ]);
        }

        return redirect()->route('checkout.success', $transaction->subscription)
            ->with('success', '¡Comprobante enviado! Te notificaremos cuando sea aprobado.');
    }

    /**
     * Checkout success page
     */
    public function success(TenantSubscription $subscription)
    {
        return view('checkout.success', [
            'subscription' => $subscription->load('tenant', 'plan'),
        ]);
    }

    /**
     * Handle webhook from payment provider
     */
    public function webhook(Request $request, string $gateway)
    {
        try {
            $driver = $this->gatewayManager->getDriver($gateway);
            $result = $driver->handleWebhook($request);

            return response()->json(['success' => $result]);
        } catch (\Exception $e) {
            \Log::error("Webhook error for {$gateway}", [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
