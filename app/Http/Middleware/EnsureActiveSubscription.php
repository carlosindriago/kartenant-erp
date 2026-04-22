<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el tenant tenga una suscripción activa y pagada.
     * Si no, redirige a la página de checkout o muestra mensaje de bloqueo.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en contexto de tenant
        if (!tenant()) {
            return $next($request);
        }

        $tenant = tenant();
        
        // Obtener la suscripción activa del tenant
        $subscription = $tenant->activeSubscription();

        // Caso 1: No tiene suscripción
        if (!$subscription) {
            return $this->handleNoSubscription($request, $tenant);
        }

        // Caso 2: Trial expirado
        if ($subscription->isTrialEnded()) {
            return $this->handleTrialExpired($request, $tenant, $subscription);
        }

        // Caso 3: Pago pendiente de aprobación
        if ($subscription->payment_status === 'pending_approval') {
            return $this->handlePendingApproval($request);
        }

        // Caso 4: Pago rechazado o fallido
        if (in_array($subscription->payment_status, ['failed', 'pending']) && $subscription->status === 'inactive') {
            return $this->handlePaymentRequired($request, $subscription);
        }

        // Caso 5: Todo OK - continuar
        return $next($request);
    }

    /**
     * Manejar caso: No tiene suscripción
     */
    private function handleNoSubscription(Request $request, Tenant $tenant): Response
    {
        // Si es una ruta de configuración inicial, permitir
        if ($request->is('app/onboarding*')) {
            return redirect()->route('tenant.onboarding');
        }

        return response()->view('tenant.blocked.no-subscription', [
            'tenant' => $tenant,
            'message' => 'Tu cuenta no tiene una suscripción activa.',
        ], 403);
    }

    /**
     * Manejar caso: Trial expirado
     */
    private function handleTrialExpired(Request $request, Tenant $tenant, $subscription): Response
    {
        // Redirigir a checkout para actualizar a plan de pago
        return redirect()->route('checkout.show', $subscription)
            ->with('warning', 'Tu período de prueba ha expirado. Por favor selecciona un plan de pago para continuar.');
    }

    /**
     * Manejar caso: Pago pendiente de aprobación
     */
    private function handlePendingApproval(Request $request): Response
    {
        return response()->view('tenant.blocked.pending-approval', [
            'message' => 'Tu pago está siendo procesado. Te notificaremos cuando sea aprobado.',
        ], 403);
    }

    /**
     * Manejar caso: Pago requerido
     */
    private function handlePaymentRequired(Request $request, $subscription): Response
    {
        return redirect()->route('checkout.show', $subscription)
            ->with('error', 'Debes completar el pago para acceder a tu cuenta.');
    }
}
