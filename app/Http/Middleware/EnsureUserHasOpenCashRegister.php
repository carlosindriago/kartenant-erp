<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Middleware;

use App\Modules\POS\Models\CashRegister;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureUserHasOpenCashRegister Middleware
 *
 * Verifica que el usuario tenga una caja abierta antes de acceder al POS
 * Permite múltiples usuarios con cajas abiertas simultáneamente
 * Cada usuario debe tener su propia caja abierta
 */
class EnsureUserHasOpenCashRegister
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, dejar que el guard de auth maneje
        if (! $user) {
            return $next($request);
        }

        // Verificar si el usuario tiene una caja abierta
        $hasOpenRegister = CashRegister::userHasOpenRegister($user->id);

        // Si no tiene caja abierta, redirigir a la página de apertura
        if (! $hasOpenRegister) {
            // Si es una request AJAX/Livewire, retornar error JSON
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                Notification::make()
                    ->danger()
                    ->title('Caja no abierta')
                    ->body('Debes abrir una caja antes de usar el POS.')
                    ->persistent()
                    ->send();

                return redirect()->route('filament.app.pos.open-register');
            }

            // Request normal, redirigir con mensaje
            Notification::make()
                ->warning()
                ->title('Apertura de Caja Requerida')
                ->body('Para usar el POS, primero debes abrir tu caja registradora.')
                ->persistent()
                ->send();

            return redirect()->route('filament.app.pos.open-register');
        }

        // El usuario tiene caja abierta, continuar
        return $next($request);
    }
}
