# Soft Limits System - Implementation Guide

## Overview

The "Soft Limits" system provides intelligent usage management with progressive enforcement, ensuring business continuity while maintaining fair usage policies. This guide covers the complete implementation process for Emporio Digital's multi-tenant SaaS platform.

## Architecture Summary

### Core Components

1. **Database Layer** (Landlord DB)
   - `tenant_usages` - Monthly usage tracking
   - `usage_alerts` - Alert history and delivery tracking
   - `usage_metrics_log` - Detailed audit trail

2. **Performance Layer**
   - Redis caching for real-time counters
   - Asynchronous processing queue
   - Middleware for request interception

3. **Integration Layer**
   - Observers for automatic tracking
   - Event-driven architecture
   - Billing system integration

4. **UI/UX Layer**
   - Filament v3 admin components
   - Progressive warning banners
   - Real-time usage dashboards

## Installation Steps

### 1. Run Database Migrations

```bash
# Run the landlord migration
./vendor/bin/sail artisan migrate --database=landlord --path=database/migrations/landlord/2025_12_04_000001_create_tenant_usages_and_alerts_tables.php
```

### 2. Register Service Provider

Add to your `config/app.php` providers array:

```php
'providers' => [
    // ... existing providers
    App\Providers\UsageTrackingServiceProvider::class,
],
```

### 3. Publish Configuration

```bash
./vendor/bin/sail artisan vendor:publish --tag=usage-limits-config
```

### 4. Register Middleware

Add to your tenant routes or globally:

```php
// In app/Http/Kernel.php or tenant route group
'middleware' => ['auth', 'tenant', 'usage.limits'],
```

### 5. Update Plan Limits

Ensure your subscription plans have limits defined:

```php
// Update existing plans or create new ones
SubscriptionPlan::where('slug', 'starter')->update([
    'max_sales_per_month' => 100,
    'max_products' => 50,
    'max_users' => 3,
    'max_storage_mb' => 1024, // 1GB
]);
```

### 6. Configure Notification Channels

#### Slack Integration (Optional)

Add to your `.env`:

```env
USAGE_SLACK_ALERTS_ENABLED=true
LOG_SLACK_WEBHOOK_URL="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"
```

#### Email Configuration

```env
USAGE_EMAIL_ALERTS_ENABLED=true
USAGE_SALES_TEAM_EMAIL="sales@emporiodigital.com"
```

### 7. Initialize Usage Tracking

Run for existing tenants:

```bash
# Initialize current month usage for all tenants
./vendor/bin/sail artisan usage:reset-monthly

# Recalculate from historical data (if needed)
./vendor/bin/sail artisan usage:recalculate
```

## Configuration Options

### Traffic Light Thresholds

```php
// config/usage-limits.php
'thresholds' => [
    'warning' => 80,      // 80% - Show warnings
    'overdraft' => 100,   // 100% - Allow but flag for upgrade
    'critical' => 120,    // 120% - Block new creations
],
```

### Bypass Rules

Sales operations are never blocked to ensure business continuity:

```php
'bypass_rules' => [
    'sales' => [
        'always_allow' => true,
        'reason' => 'Business continuity - Sales are revenue generating',
    ],
],
```

### Cache Configuration

```php
'cache' => [
    'ttl' => 3600,              // 1 hour
    'counter_ttl' => 86400 * 32, // 32 days
],
```

## Usage Examples

### Manual Usage Tracking

```php
use App\Services\TenantUsageService;

$usageService = app(TenantUsageService::class);

// Increment usage
$usageService->incrementUsage(
    tenantId: 1,
    metricType: 'products',
    value: 1,
    source: 'manual',
    entityType: 'Product',
    entityId: 123
);

// Check if action is allowed
if ($usageService->canPerformAction(1, 'create_product')) {
    // Allow product creation
}
```

### File Upload Tracking

```php
use App\Observers\StorageUsageObserver;

$storageObserver = app(StorageUsageObserver::class);

// On file upload
$storageObserver->fileUploaded($filePath, $fileSize, tenant()->id);

// On file deletion
$storageObserver->fileDeleted($filePath, tenant()->id);
```

### Custom Alerts

```php
use App\Services\UsageAlertService;

$alertService = app(UsageAlertService::class);

// Send test alert
$alertService->sendTestAlert(tenant()->id, 'warning');

// Process all pending alerts
$alertService->processAllPendingAlerts();
```

## UI Integration

### Add Usage Banner to Layout

Add to your main tenant layout:

```blade
<x-slot name="content">
    <x-usage-warning-banner />

    <!-- Rest of your content -->
</x-slot>
```

### Add Navigation Menu Item

In your Filament tenant panel provider:

```php
protected function getNavigationItems(): array
{
    return [
        // ... existing items
        NavigationItem::make('Plan Usage')
            ->url(route('filament.app.resources.tenant-usages.index'))
            ->icon('heroicon-o-chart-bar')
            ->sort(1),
    ];
}
```

## Monitoring and Maintenance

### Console Commands

```bash
# Reset monthly counters (run automatically on 1st of month)
./vendor/bin/sail artisan usage:reset-monthly

# Process pending alerts
./vendor/bin/sail artisan usage:process-alerts

# Synchronize Redis counters
./vendor/bin/sail artisan usage:sync

# Test alert system
./vendor/bin/sail artisan usage:test-alert --tenant=1 --type=warning

# Process billing cycles
./vendor/bin/sail artisan usage:process-billing
```

### Automated Tasks

The following tasks are automatically scheduled:

- **Monthly Reset**: 1st day of month at 00:05
- **Alert Processing**: Every 5 minutes
- **Counter Sync**: Every hour
- **Billing Process**: Daily at 02:00
- **Cleanup**: Weekly

### Health Monitoring

Monitor these metrics:

```php
use App\Services\TenantUsageService;

$usageService = app(TenantUsageService::class);

// Get system-wide statistics
$stats = $usageService->getAdminStatistics();

// Get tenants needing attention
$attention = $usageService->getTenantsNeedingAttention();
```

## Troubleshooting

### Common Issues

#### 1. "Relation does not exist" Error
```bash
# Check if migration was run to correct database
./vendor/bin/sail artisan migrate --database=landlord --path=database/migrations/landlord
```

#### 2. Redis Connection Issues
```bash
# Check Redis connection
./vendor/bin/sail artisan tinker
>>> Cache::store('redis')->put('test', 'value', 60);
>>> Cache::store('redis')->get('test');
```

#### 3. Missing Usage Records
```bash
# Force recalculation for specific tenant
./vendor/bin/sail artisan usage:recalculate --tenant=1
```

#### 4. Alerts Not Sending
```bash
# Test alert configuration
./vendor/bin/sail artisan usage:test-alert --type=warning
```

### Performance Optimization

1. **Redis Configuration**: Ensure Redis is properly configured for persistence
2. **Queue Processing**: Configure supervisors for reliable queue processing
3. **Database Indexes**: All necessary indexes are included in migrations
4. **Cache Warming**: Pre-warm cache during off-peak hours

### Debug Mode

Enable debug mode for detailed logging:

```env
USAGE_DEBUG_MODE=true
USAGE_LOG_LEVEL=debug
USAGE_TRACK_PERFORMANCE=true
```

## Security Considerations

1. **Tenant Isolation**: All usage data is properly isolated by tenant
2. **Rate Limiting**: Consider rate limiting for high-frequency operations
3. **Audit Trail**: All usage changes are logged for compliance
4. **Data Privacy**: Usage data doesn't contain sensitive business information

## Testing

### Unit Tests

```bash
# Run usage-related tests
./vendor/bin/sail artisan test --filter=Usage
```

### Browser Tests

```bash
# Run browser automation tests
./vendor/bin/sail artisan dusk --filter=UsageLimits
```

### Load Testing

Test system performance under load:

```bash
# Simulate high-frequency usage
./vendor/bin/sail artisan tinker
>>> for($i=0; $i<1000; $i++) {
>>>     app(TenantUsageService::class)->incrementUsage(1, 'sales');
>>> }
```

## Rollback Plan

If needed, rollback can be done safely:

```bash
# Rollback migration
./vendor/bin/sail artisan migrate:rollback --database=landlord

# Remove service provider from config/app.php

# Clear caches
./vendor/bin/sail artisan optimize:clear
```

## Support

For issues or questions:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Monitor Redis: `./vendor/bin/sail redis-cli monitor`
3. Review queue jobs: `./vendor/bin/sail artisan queue:monitor`

## Future Enhancements

Planned improvements:

1. **Predictive Analytics**: Usage forecasting and trend analysis
2. **Custom Metrics**: Allow tenants to define custom usage metrics
3. **API Rate Limiting**: Integrate with API rate limiting
4. **Advanced Reporting**: Detailed usage analytics and exports
5. **Multi-Currency Support**: Usage limits based on plan currency

---

**Implementation Status**: ✅ Complete
**Last Updated**: December 4, 2025
**Version**: 1.0.0