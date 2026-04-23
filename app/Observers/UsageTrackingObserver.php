<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Services\TenantUsageService;
use Illuminate\Database\Eloquent\Model;

class UsageTrackingObserver
{
    public function __construct(
        private TenantUsageService $usageService
    ) {}

    /**
     * Track model creation
     */
    public function created(Model $model): void
    {
        $this->trackUsage($model, 'created');
    }

    /**
     * Track model updates (when relevant)
     */
    public function updated(Model $model): void
    {
        // Only track updates for specific models that affect usage
        $this->trackUsage($model, 'updated');
    }

    /**
     * Track model deletion
     */
    public function deleted(Model $model): void
    {
        $this->trackUsage($model, 'deleted');
    }

    /**
     * Track usage based on model type and action
     */
    private function trackUsage(Model $model, string $action): void
    {
        if (! $this->shouldTrackModel($model)) {
            return;
        }

        $tenantId = $this->getTenantId($model);
        if (! $tenantId) {
            return;
        }

        $mapping = $this->getUsageMapping($model, $action);
        if (! $mapping) {
            return;
        }

        // Track usage asynchronously to avoid performance impact
        $this->usageService->incrementUsage(
            $tenantId,
            $mapping['metric_type'],
            $mapping['value'],
            'observer',
            class_basename($model),
            $model->id,
            $mapping['metadata'] ?? []
        );
    }

    /**
     * Check if model should be tracked
     */
    private function shouldTrackModel(Model $model): bool
    {
        $trackedModels = [
            \App\Modules\Inventory\Models\Product::class,
            \App\Models\User::class,
            \App\Modules\POS\Models\Sale::class,
        ];

        return in_array(get_class($model), $trackedModels);
    }

    /**
     * Get tenant ID from model
     */
    private function getTenantId(Model $model): ?int
    {
        // Direct tenant relationship
        if (isset($model->tenant_id)) {
            return $model->tenant_id;
        }

        // Get tenant from current context
        $tenant = tenant();

        return $tenant?->id;
    }

    /**
     * Get usage mapping for model and action
     */
    private function getUsageMapping(Model $model, string $action): ?array
    {
        $modelClass = get_class($model);

        return match ($modelClass) {
            \App\Modules\Inventory\Models\Product::class => $this->getProductMapping($model, $action),
            \App\Models\User::class => $this->getUserMapping($model, $action),
            \App\Modules\POS\Models\Sale::class => $this->getSaleMapping($model, $action),
            default => null,
        };
    }

    /**
     * Product usage mapping
     */
    private function getProductMapping($product, string $action): ?array
    {
        return match ($action) {
            'created' => [
                'metric_type' => 'products',
                'value' => 1,
                'metadata' => [
                    'product_name' => $product->name ?? 'Unknown',
                    'sku' => $product->sku ?? null,
                ],
            ],
            'deleted' => [
                'metric_type' => 'products',
                'value' => -1, // Decrement on deletion
                'metadata' => [
                    'product_name' => $product->name ?? 'Unknown',
                    'sku' => $product->sku ?? null,
                ],
            ],
            default => null,
        };
    }

    /**
     * User usage mapping
     */
    private function getUserMapping($user, string $action): ?array
    {
        // Only count tenant users, not superadmin
        if (isset($user->is_super_admin) && $user->is_super_admin) {
            return null;
        }

        // Only track active users
        if (isset($user->is_active) && ! $user->is_active) {
            return null;
        }

        return match ($action) {
            'created' => [
                'metric_type' => 'users',
                'value' => 1,
                'metadata' => [
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                ],
            ],
            'updated' => $this->getUserUpdateMapping($user),
            'deleted' => [
                'metric_type' => 'users',
                'value' => -1, // Decrement on deletion
                'metadata' => [
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                ],
            ],
            default => null,
        };
    }

    /**
     * Handle user updates (deactivation/reactivation)
     */
    private function getUserUpdateMapping($user): ?array
    {
        // Check if user was deactivated/reactivated
        if ($user->wasChanged('is_active')) {
            $wasActive = $user->getOriginal('is_active');
            $isActive = $user->is_active;

            if ($wasActive && ! $isActive) {
                return [
                    'metric_type' => 'users',
                    'value' => -1, // Decrement on deactivation
                    'metadata' => [
                        'user_email' => $user->email,
                        'action' => 'deactivated',
                    ],
                ];
            } elseif (! $wasActive && $isActive) {
                return [
                    'metric_type' => 'users',
                    'value' => 1, // Increment on reactivation
                    'metadata' => [
                        'user_email' => $user->email,
                        'action' => 'reactivated',
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * Sale usage mapping
     */
    private function getSaleMapping($sale, string $action): ?array
    {
        // Only count completed sales
        if (isset($sale->status) && $sale->status !== 'completed') {
            return null;
        }

        return match ($action) {
            'created' => [
                'metric_type' => 'sale_created',
                'value' => 1,
                'metadata' => [
                    'sale_total' => $sale->total ?? 0,
                    'sale_number' => $sale->sale_number ?? null,
                ],
            ],
            'updated' => $this->getSaleUpdateMapping($sale),
            'deleted' => [
                'metric_type' => 'sale_created',
                'value' => -1, // Decrement on deletion
                'metadata' => [
                    'sale_total' => $sale->total ?? 0,
                    'sale_number' => $sale->sale_number ?? null,
                    'action' => 'deleted',
                ],
            ],
            default => null,
        };
    }

    /**
     * Handle sale updates (status changes)
     */
    private function getSaleUpdateMapping($sale): ?array
    {
        // Check if sale status changed to/from completed
        if ($sale->wasChanged('status')) {
            $wasCompleted = $sale->getOriginal('status') === 'completed';
            $isCompleted = $sale->status === 'completed';

            if (! $wasCompleted && $isCompleted) {
                return [
                    'metric_type' => 'sale_created',
                    'value' => 1, // Increment when completed
                    'metadata' => [
                        'sale_total' => $sale->total ?? 0,
                        'sale_number' => $sale->sale_number ?? null,
                        'action' => 'completed',
                    ],
                ];
            } elseif ($wasCompleted && ! $isCompleted) {
                return [
                    'metric_type' => 'sale_created',
                    'value' => -1, // Decrement when uncompleted
                    'metadata' => [
                        'sale_total' => $sale->total ?? 0,
                        'sale_number' => $sale->sale_number ?? null,
                        'action' => 'uncompleted',
                    ],
                ];
            }
        }

        return null;
    }
}
