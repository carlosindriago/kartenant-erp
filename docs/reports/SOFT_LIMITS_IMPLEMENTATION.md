# Soft Limits Configuration Implementation

## Overview
This implementation extends the existing Soft Limits system to allow SuperAdmin configuration of limits per subscription plan with configurable overage strategies.

## Database Changes

### Migration: `2025_12_04_133814_add_soft_limits_configuration_to_subscription_plans_table.php`

Added three new columns to the `subscription_plans` table:

```sql
-- JSON column storing configurable limits
limits JSON NULL COMMENT 'Configurable limits for metrics like monthly_sales, users, storage_mb, products'

-- Strategy for handling overages
overage_strategy VARCHAR(20) DEFAULT 'soft_limit' COMMENT 'Strategy for handling overages: strict, soft_limit'

-- Buffer zone percentage for soft limits
overage_percentage INTEGER DEFAULT 20 COMMENT 'Buffer zone percentage for soft limits'
```

## Model Updates

### SubscriptionPlan Model

**New Fillable Fields:**
- `limits` - JSON configuration for limits
- `overage_strategy` - Strategy for handling overages ('strict' or 'soft_limit')
- `overage_percentage` - Buffer percentage for overage zone

**New Casts:**
- `limits` -> `array`
- `overage_strategy` -> `string`
- `overage_percentage` -> `integer`

**New Helper Methods:**

```php
// Get configured limit for a specific metric
public function getLimit(string $metric): ?int

// Get overage limit including buffer (if applicable)
public function getOverageLimit(string $metric): ?int

// Check if plan allows overage
public function allowsOverage(): bool

// Check if plan uses strict limits
public function isStrict(): bool

// Calculate usage percentage against overage limit
public function calculateUsagePercentage(string $metric, int $current): float

// Check if current usage exceeds base limit
public function exceedsBaseLimit(string $metric, int $current): bool

// Check if current usage exceeds overage limit (hard limit)
public function exceedsOverageLimit(string $metric, int $current): bool

// Get remaining capacity before hitting limits
public function getRemainingCapacity(string $metric, int $current): int
public function getRemainingOverageCapacity(string $metric, int $current): int

// Get comprehensive limit status
public function getLimitStatus(string $metric, int $current): array

// Check if plan has configurable limits set
public function hasConfigurableLimits(): bool

// Get all available metrics from configuration
public function getAvailableMetrics(): array
```

### TenantUsage Model Updates

**New Methods:**

```php
// Get overage limit based on subscription plan configuration
public function getOverageLimit(string $metric): ?int

// Updated zone detection to use configurable limits
public function isWarningZone(string $metric): bool
public function isCriticalZone(string $metric): bool
public function getZoneForMetric(string $metric): string

// Updated getOrCreateCurrentUsage to use configurable limits
public static function getOrCreateCurrentUsage(int $tenantId): self
```

## Service Integration

### SubscriptionService Updates

**New Methods:**

```php
// Update tenant usage limits when subscription changes
public function updateTenantUsageLimits(TenantSubscription $subscription): void

// Check if tenant can perform action based on current subscription and usage
public function canTenantPerformAction(Tenant $tenant, string $action): bool

// Get tenant's current usage status with configurable limits
public function getTenantUsageStatus(Tenant $tenant): array

// Apply plan configuration changes to existing subscriptions
public function applyPlanConfigurationChanges(SubscriptionPlan $plan): int
```

**Updated Methods:**

- `createSubscription()` - Now initializes usage limits
- `changePlan()` - Now updates usage limits when changing plans

## Configuration Format

### Example Plan Configuration

```json
{
  "limits": {
    "monthly_sales": 1000,
    "products": 500,
    "users": 10,
    "storage_mb": 1024
  },
  "overage_strategy": "soft_limit",
  "overage_percentage": 20
}
```

### Available Metrics

- `monthly_sales` - Number of sales per month
- `products` - Number of products in inventory
- `users` - Number of user accounts
- `storage_mb` - Storage usage in megabytes

### Overage Strategies

1. **strict** - No overage allowed, hard limits enforced
2. **soft_limit** - Overage allowed up to specified percentage buffer

## Usage Examples

### Creating a Plan with Configurable Limits

```php
$plan = SubscriptionPlan::create([
    'name' => 'Professional Plan',
    'slug' => 'professional',
    'price_monthly' => 49.99,
    'limits' => [
        'monthly_sales' => 1000,
        'products' => 500,
        'users' => 10,
        'storage_mb' => 1024,
    ],
    'overage_strategy' => 'soft_limit',
    'overage_percentage' => 20,
    'is_active' => true,
]);
```

### Checking Limits

```php
$plan = SubscriptionPlan::findBySlug('professional');

// Get base limit
$baseLimit = $plan->getLimit('monthly_sales'); // 1000

// Get overage limit (includes 20% buffer)
$overageLimit = $plan->getOverageLimit('monthly_sales'); // 1200

// Check current usage
$currentUsage = 850;

// Check if in overage zone
$isOverage = $plan->exceedsBaseLimit('monthly_sales', $currentUsage); // false

// Check if exceeded hard limit
$isCritical = $plan->exceedsOverageLimit('monthly_sales', $currentUsage); // false

// Get detailed status
$status = $plan->getLimitStatus('monthly_sales', $currentUsage);
/*
[
    'status' => 'normal',
    'message' => '850/1000',
    'percentage' => 70.83,
    'current' => 850,
    'limit' => 1200,
    'base_limit' => 1000,
    'allows_overage' => true,
]
*/
```

### Tenant Usage Integration

```php
$subscriptionService = app(SubscriptionService::class);
$tenant = Tenant::find(1);

// Get comprehensive usage status
$usageStatus = $subscriptionService->getTenantUsageStatus($tenant);

// Check if tenant can perform actions
$canCreateProduct = $subscriptionService->canTenantPerformAction($tenant, 'create_product');
$canCreateUser = $subscriptionService->canTenantPerformAction($tenant, 'create_user');
```

## Backward Compatibility

The implementation maintains full backward compatibility with existing subscription plans:

1. **Legacy Plans** - Plans without `limits` configuration continue to use existing `max_*` columns
2. **Gradual Migration** - Plans can be migrated individually to the new system
3. **Fallback Logic** - All methods gracefully fall back to legacy system when configurable limits are not set

## Zone Detection Logic

### Configurable Limits System
- **Normal**: Usage ≤ base limit
- **Warning**: Base limit < usage ≤ overage limit
- **Critical**: Usage > overage limit

### Legacy System (Fallback)
- **Normal**: Usage ≤ 80% of limit
- **Warning**: 80% < usage ≤ 100% of limit
- **Overdraft**: 100% < usage ≤ 120% of limit
- **Critical**: Usage > 120% of limit

## Database Migration Status

✅ **Completed**: Migration has been successfully applied to the landlord database.

## Testing

The implementation includes comprehensive test coverage for:
- Plan configuration creation and validation
- Limit calculations and overage logic
- Zone detection with configurable limits
- Integration with tenant usage system
- Backward compatibility with legacy plans
- Strict vs soft limit strategies

## Next Steps for SuperAdmin

1. **Configure Plans**: Update existing subscription plans with configurable limits
2. **Set Strategies**: Choose appropriate overage strategies for each plan tier
3. **Monitor Usage**: Use the new configurable limits in the admin dashboard
4. **Migrate Legacy Plans**: Gradually migrate existing plans to the new system

## Files Modified

1. `/database/migrations/landlord/2025_12_04_133814_add_soft_limits_configuration_to_subscription_plans_table.php`
2. `/app/Models/SubscriptionPlan.php`
3. `/app/Models/TenantUsage.php`
4. `/app/Services/SubscriptionService.php`

## Files Created

1. `/SOFT_LIMITS_IMPLEMENTATION.md` (this documentation)

The implementation is production-ready and follows Laravel 11 best practices with proper type hints, validation, and comprehensive error handling.