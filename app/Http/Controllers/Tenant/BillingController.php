<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PaymentSettings;
use App\Models\PaymentProof;
use App\Models\TenantSubscription;
use App\Models\Invoice;
use App\Services\PaymentProofService;
use App\Services\SubscriptionService;

class BillingController extends Controller
{
    public function __construct(
        private PaymentProofService $paymentProofService,
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Main billing page - Ernesto-friendly interface
     */
    public function index()
    {
        $tenant = tenant();
        $user = Auth::guard('tenant')->user();

        // Get current subscription status
        $subscription = $this->subscriptionService->getCurrentSubscription($tenant);
        $paymentSettings = PaymentSettings::getDefault();

        // Get recent payment proofs for this tenant
        $recentPayments = PaymentProof::on('landlord')
            ->where('tenant_id', $tenant->id)
            ->with('invoice')
            ->latest()
            ->take(5)
            ->get();

        return view('tenant.billing.index', compact(
            'subscription',
            'paymentSettings',
            'recentPayments',
            'tenant'
        ));
    }

    /**
     * Store payment proof submission
     */
    public function storePaymentProof(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120', // 5MB max
            ],
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $tenant = tenant();
            $user = Auth::guard('tenant')->user();

            // Use the simplified service method
            $paymentProof = $this->paymentProofService->storePaymentProof(
                $request->file('file'),
                $request->all(),
                $tenant,
                $user
            );

            return redirect()
                ->route('tenant.billing.index')
                ->with('success', '¡Comprobante enviado! Esperando aprobación del administrador.');

        } catch (\Exception $e) {
            \Log::error('Payment proof submission error', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al subir el comprobante: ' . $e->getMessage());
        }
    }

    /**
     * Payment history page
     */
    public function history()
    {
        $tenant = tenant();

        $payments = PaymentProof::on('landlord')
            ->where('tenant_id', $tenant->id)
            ->with('invoice')
            ->latest()
            ->paginate(20);

        return view('tenant.billing.history', compact('payments'));
    }

    /**
     * Show specific invoice
     */
    public function showInvoice(Invoice $invoice)
    {
        // Verify this invoice belongs to current tenant
        if ($invoice->tenant_id !== tenant()->id) {
            abort(403, 'No autorizado');
        }

        return view('tenant.billing.invoice', compact('invoice'));
    }

    /**
     * Subscription details page
     */
    public function subscription()
    {
        $tenant = tenant();
        $subscription = $this->subscriptionService->getCurrentSubscription($tenant);
        $paymentSettings = PaymentSettings::getDefault();

        return view('tenant.billing.subscription', compact(
            'subscription',
            'paymentSettings',
            'tenant'
        ));
    }
}