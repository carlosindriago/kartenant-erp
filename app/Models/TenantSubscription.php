<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Services\TimeFormattingService;

class TenantSubscription extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';
    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'price',
        'currency',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'next_billing_at',
        'payment_method',
        'stripe_subscription_id',
        'stripe_customer_id',
        'auto_renew',
        'cancellation_reason',
        'usage_stats',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'auto_renew' => 'boolean',
        'usage_stats' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_subscription_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeDueForBilling($query)
    {
        return $query->active()
            ->where('auto_renew', true)
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now());
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialHasEnded(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function daysUntilExpiration(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    /**
     * Obtiene el tiempo restante formateado para Ernesto
     */
    public function getFormattedRemainingTime(): string
    {
        if (!$this->ends_at) {
            return 'Sin fecha de vencimiento';
        }

        return $this->getExpiredTimeFormatted();
    }

    /**
     * Obtiene el tiempo transcurrido desde el vencimiento en formato legible
     * Para mostrar en el dashboard cuando ya expiró
     */
    public function getExpiredTimeFormatted(): string
    {
        if (!$this->ends_at) {
            return 'Sin fecha de vencimiento';
        }

        if (!$this->ends_at->isPast()) {
            return $this->getFormattedRemainingTime();
        }

        // Calcular diferencia precisa usando diff() en lugar de diffInDays()
        // Esto evita problemas con decimales y da resultados más consistentes
        $diff = now()->diff($this->ends_at);

        // Construir el formato manualmente para mayor precisión
        $days = $diff->days;
        $hours = $diff->h;

        if ($days === 0 && $hours === 0) {
            return 'Vencido hace momentos';
        }

        if ($days === 0) {
            return $hours === 1
                ? "Vencido hace 1 hora"
                : "Vencido hace {$hours} horas";
        }

        if ($hours === 0) {
            return $days === 1
                ? "Vencido hace 1 día"
                : "Vencido hace {$days} días";
        }

        return $days === 1
            ? "Vencido hace 1 día y {$hours} horas"
            : "Vencido hace {$days} días y {$hours} horas";
    }

    public function daysOfTrialRemaining(): ?int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Obtiene el tiempo de trial formateado para Ernesto
     */
    public function getFormattedTrialTime(): string
    {
        if (!$this->isOnTrial()) {
            return 'Sin trial activo';
        }

        return TimeFormattingService::formatRemainingTime($this->trial_ends_at);
    }

    // Actions
    public function cancel(?string $reason = null): bool
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        return true;
    }

    public function suspend(?string $reason = null): bool
    {
        $this->update([
            'status' => 'suspended',
        ]);

        return true;
    }

    public function resume(): bool
    {
        if (!$this->isSuspended()) {
            return false;
        }

        $this->update([
            'status' => 'active',
        ]);

        return true;
    }

    public function renew(): bool
    {
        if ($this->billing_cycle === 'yearly') {
            $nextBilling = now()->addYear();
            $ends = now()->addYear();
        } else {
            $nextBilling = now()->addMonth();
            $ends = now()->addMonth();
        }

        $this->update([
            'next_billing_at' => $nextBilling,
            'ends_at' => $ends,
            'status' => 'active',
        ]);

        return true;
    }

    public function changePlan(SubscriptionPlan $newPlan, bool $immediately = false): bool
    {
        $price = $newPlan->getPrice($this->billing_cycle);

        $this->update([
            'subscription_plan_id' => $newPlan->id,
            'price' => $price,
        ]);

        return true;
    }

    public function switchBillingCycle(string $cycle): bool
    {
        if (!in_array($cycle, ['monthly', 'yearly'])) {
            return false;
        }

        $price = $this->plan->getPrice($cycle);

        $this->update([
            'billing_cycle' => $cycle,
            'price' => $price,
        ]);

        return true;
    }

    // Helper methods
    public function getFormattedPrice(): string
    {
        return $this->currency . ' ' . number_format((float) $this->price, 2);
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'active' => 'success',
            'trial' => 'info',
            'cancelled' => 'danger',
            'expired' => 'warning',
            'suspended' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        $labels = [
            'active' => 'Activa',
            'cancelled' => 'Cancelada',
            'expired' => 'Expirada',
            'suspended' => 'Suspendida',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }
}
