# Bugfix: Tenant Migration Foreign Key Order

**Branch:** `bugfix/tenant-migration-foreign-key-order`  
**Date:** 2025-10-06  
**Status:** ✅ Fixed

## 🐛 Problem Description

When creating a new tenant, the migration process was failing with the following error:

```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "categories" does not exist
(Connection: tenant, SQL: alter table "products" add constraint "products_category_id_foreign" 
foreign key ("category_id") references "categories" ("id") on delete set null)
```

### Root Cause

The tenant migrations were executing in the wrong order due to their timestamp-based filenames:

1. ❌ `2025_08_18_173700_create_products_table.php` (executed first)
2. ❌ `2025_09_15_144348_create_categories_table.php` (executed second)
3. ❌ `2025_09_15_144358_create_taxes_table.php` (executed third)

The `products` table migration was trying to create foreign key constraints to `categories` and `taxes` tables that didn't exist yet.

## ✅ Solution

Renamed the migration files to ensure correct execution order:

1. ✅ `2025_08_18_173600_create_categories_table.php` (first)
2. ✅ `2025_08_18_173650_create_taxes_table.php` (second)
3. ✅ `2025_08_18_173700_create_products_table.php` (third)

### Files Changed

```bash
# Renamed migrations
database/migrations/tenant/2025_09_15_144348_create_categories_table.php
→ database/migrations/tenant/2025_08_18_173600_create_categories_table.php

database/migrations/tenant/2025_09_15_144358_create_taxes_table.php
→ database/migrations/tenant/2025_08_18_173650_create_taxes_table.php
```

## 📋 Migration Dependencies

The correct dependency order is:

```
categories (no dependencies)
    ↓
taxes (no dependencies)
    ↓
products (depends on categories and taxes)
```

### Products Table Foreign Keys

```php
// Line 17: Foreign key to categories
$table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');

// Line 21: Foreign key to taxes
$table->foreignId('tax_id')->nullable()->constrained()->onDelete('set null');
```

## 🔍 How Tenant Creation Works

1. **TenantObserver** listens for `created` event
2. Creates tenant database: `Artisan::call('db:create')`
3. Executes tenant migrations in `$tenant->execute()` context:
   ```php
   Artisan::call('migrate', [
       '--database' => 'tenant',
       '--path' => 'database/migrations/tenant',
       '--seed' => true,
       '--seeder' => 'Database\\Seeders\\RolesAndPermissionsSeeder',
       '--force' => true,
   ]);
   ```

## ✅ Testing

To verify the fix works:

1. Create a new tenant via Admin panel:
   - Go to `/admin/tenants/create`
   - Fill in tenant details
   - Submit form
   
   **Note:** Queue worker is NOT required for tenant creation. The TenantObserver 
   executes migrations synchronously in the same HTTP request.

2. Verify migrations executed successfully:
   ```bash
   # Check tenant database
   ./vendor/bin/sail artisan tenants:list
   
   # Connect to tenant and verify tables
   ./vendor/bin/sail artisan tinker
   >>> Tenant::first()->execute(function() {
   ...     return DB::select('SELECT tablename FROM pg_tables WHERE schemaname = \'public\'');
   ... });
   ```

## 📝 Lessons Learned

1. **Migration Order Matters**: Always ensure dependent tables are created before tables with foreign keys
2. **Timestamp Naming**: Migration timestamps determine execution order
3. **Foreign Key Constraints**: `constrained()` helper requires referenced table to exist
4. **Tenant Context**: Migrations run in isolated tenant database context

## 🔗 Related Files

- `app/Observers/TenantObserver.php` - Handles tenant creation and migration execution
- `database/migrations/tenant/` - Tenant-specific migrations
- `app/Models/Tenant.php` - Tenant model with multitenancy support

## 📚 References

- [Laravel Migrations Documentation](https://laravel.com/docs/migrations)
- [Spatie Laravel Multitenancy](https://spatie.be/docs/laravel-multitenancy)
- [Foreign Key Constraints](https://laravel.com/docs/migrations#foreign-key-constraints)

---

**Commit:** `bc04224`  
**Merged to:** `develop`
