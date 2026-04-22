# **CASHFLOW MIGRATION PLAN**
## **Complete Database Schema and Settings Integration**

### **PREREQUISITES**
```bash
# Install required PHP extensions (run as root/administrator)
sudo apt-get install php-gd php-zip php-xml php-dom

# Install Spatie Laravel Settings
composer require spatie/laravel-settings

# Publish settings migrations and config
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings"
```

### **MIGRATION EXECUTION ORDER**

#### **Phase 1: Core Infrastructure**
```bash
# 1. Create settings table (landlord database)
php artisan migrate --database=landlord --path=database/migrations/landlord/2025_11_29_000001_create_payment_settings_table.php

# 2. Create payment proofs table
php artisan migrate --database=landlord --path=database/migrations/landlord/2025_11_29_000002_create_payment_proofs_table.php

# 3. Create invoices table
php artisan migrate --database=landlord --path=database/migrations/landlord/2025_11_29_000003_create_invoices_table.php
```

#### **Phase 2: Performance Optimization**
```bash
# 4. Add indexes and constraints
php artisan migrate --database=landlord --path=database/migrations/landlord/2025_11_29_000004_add_indexes_and_constraints_to_existing_tables.php

# 5. Run Spatie settings migration
php artisan migrate --database=landlord
```

### **POST-MIGRATION SETUP**

#### **1. Seed Default Settings**
```bash
# Create default payment settings
php artisan tinker
>>> App\Models\PaymentSettings::create([
...     'business_name' => 'Emporio Digital',
...     'default_currency' => 'USD',
...     'manual_approval_required' => true,
...     'approval_timeout_hours' => 48,
...     'auto_reminder_enabled' => true,
...     'reminder_interval_hours' => 24,
...     'max_file_size_mb' => 10,
...     'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
...     'invoice_prefix' => 'INV-',
...     'receipt_prefix' => 'REC-',
... ]);
```

#### **2. Update Tenant Model**
```bash
# The Tenant model is already updated with subscription relationships
# Verify connections:
php artisan tinker
>>> $tenant = App\Models\Tenant::first();
>>> $tenant->subscriptions; // Should work
>>> $tenant->paymentProofs; // Should work after implementation
```

#### **3. Clear Caches**
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### **DIRECTORY STRUCTURE**
```
app/
├── Settings/
│   ├── PaymentSettings.php      # Spatie settings for payment configuration
│   └── BillingSettings.php      # Spatie settings for billing policies
├── Models/
│   ├── PaymentSettings.php      # Eloquent model for payment_settings table
│   ├── PaymentProof.php         # Eloquent model for payment_proofs table
│   └── Invoice.php              # Already exists, updated with new columns
├── Services/
│   ├── SubscriptionService.php  # Complete subscription lifecycle management
│   └── PaymentApprovalService.php # Manual payment proof workflow
└── Notifications/
    ├── PaymentProofApproved.php  # Tenant notification for approval
    ├── PaymentProofRejected.php  # Tenant notification for rejection
    └── SubscriptionActivated.php # User notification for activation

database/migrations/landlord/
├── 2025_11_29_000001_create_payment_settings_table.php      # Payment configuration
├── 2025_11_29_000002_create_payment_proofs_table.php       # Manual payment proofs
├── 2025_11_29_000003_create_invoices_table.php             # Enhanced invoicing
└── 2025_11_29_000004_add_indexes_and_constraints_to_existing_tables.php # Performance
```

### **CONFIGURATION FILES**
```
config/
└── settings.php              # Spatie Laravel Settings configuration
```

### **VERIFICATION COMMANDS**
```bash
# Test settings integration
php artisan tinker
>>> App\Settings\PaymentSettings::group(); // Should return 'payment'
>>> App\Settings\BillingSettings::group(); // Should return 'billing'

# Test model relationships
php artisan tinker
>>> $tenant = App\Models\Tenant::first();
>>> $tenant->subscriptions()->count();
>>> $tenant->paymentProofs()->count(); // After implementation
>>> App\Models\PaymentProof::count();
>>> App\Models\Invoice::count();

# Test service classes
php artisan tinker
>>> $service = app(App\Services\SubscriptionService::class);
>>> $service = app(App\Services\PaymentApprovalService::class);
```

### **ROLLBACK PLAN**
```bash
# In case of issues, rollback in reverse order:
php artisan migrate:rollback --database=landlord --step=1
php artisan migrate:rollback --database=landlord --step=1
php artisan migrate:rollback --database=landlord --step=1
php artisan migrate:rollback --database=landlord --step=1
```

### **DATA INTEGRITY CHECKS**
```sql
-- Verify foreign key constraints
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
    AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND tc.table_schema = 'landlord'
    AND tc.table_name IN ('payment_settings', 'payment_proofs', 'invoices');

-- Check indexes performance
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
    AND tablename IN ('payment_settings', 'payment_proofs', 'invoices', 'tenant_subscriptions', 'payment_transactions', 'subscription_plans')
ORDER BY tablename, indexname;
```

### **PERFORMANCE OPTIMIZATION**
```sql
-- Create partial indexes for better performance
CREATE INDEX idx_payment_proofs_pending ON payment_proofs (tenant_id, status) WHERE status = 'pending';
CREATE INDEX idx_invoices_unpaid ON invoices (tenant_id, status) WHERE status IN ('draft', 'sent', 'overdue');
CREATE INDEX idx_subscriptions_active ON tenant_subscriptions (tenant_id) WHERE status = 'active';

-- Create index on JSON fields
CREATE INDEX idx_subscription_plans_modules ON subscription_plans USING gin (enabled_modules);
CREATE INDEX idx_invoices_items ON invoices USING gin (items);
```

### **MONITORING QUERIES**
```sql
-- Monitor subscription health
SELECT
    t.name as tenant_name,
    t.status as tenant_status,
    ts.status as subscription_status,
    ts.payment_status,
    ts.ends_at,
    ts.next_billing_at,
    CASE
        WHEN ts.ends_at < NOW() THEN 'EXPIRED'
        WHEN ts.ends_at <= NOW() + INTERVAL '7 days' THEN 'EXPIRING_SOON'
        ELSE 'ACTIVE'
    END as health_status
FROM tenants t
LEFT JOIN tenant_subscriptions ts ON t.id = ts.tenant_id AND ts.status = 'active'
ORDER BY ts.ends_at ASC NULLS LAST;

-- Monitor payment proof backlog
SELECT
    COUNT(*) as total_pending,
    COUNT(*) FILTER (WHERE created_at < NOW() - INTERVAL '48 hours') as overdue_pending,
    AVG(EXTRACT(EPOCH FROM (NOW() - created_at))/3600) as avg_wait_hours
FROM payment_proofs
WHERE status = 'pending';

-- Monitor overdue invoices
SELECT
    COUNT(*) as overdue_count,
    SUM(total_amount) as overdue_total,
    AVG(total_amount) as avg_overdue_amount
FROM invoices
WHERE status = 'overdue'
    AND due_date < NOW() - INTERVAL '30 days';
```

This migration plan ensures a complete, performant, and scalable billing infrastructure for the Emporio Digital SaaS platform.