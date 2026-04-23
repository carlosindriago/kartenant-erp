<?php

use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\StoreSettingController;
use App\Http\Controllers\Tenant\WelcomeController;
use App\Http\Middleware\EnforceTenantIsolation;
use App\Http\Middleware\EnsureTenantContext;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Custom Routes - Operation Ernesto Freedom
|--------------------------------------------------------------------------
|
| This file provides DUAL routing capability alongside existing Filament /app routes.
| These routes use the /tenant prefix to avoid conflicts with existing Filament panels.
|
| IMPORTANT: These routes work IN PARALLEL with existing /app routes.
| DO NOT MODIFY existing Filament routes or functionality.
|
| Architecture:
| - /app/* -> Existing Filament v3 panel (PRESERVED)
| - /tenant/* -> New custom Blade + Livewire routes (NEW)
|
*/

// NOTE: These routes are already loaded with 'web' middleware by TenantRouteServiceProvider
// The web middleware group includes MakeSpatieTenantCurrent, which handles tenant database switching
// We only add EnsureTenantContext for additional tenant-specific validation
Route::middleware([EnsureTenantContext::class])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Tenant Authentication Routes (No Auth Required)
    |--------------------------------------------------------------------------
    */

    // DEBUG: Temporary route to test tenant routing
    Route::get('/test-auth', function () {
        Log::info('TEST AUTH ROUTE ACCESSED', ['tenant' => tenant()?->domain]);

        return 'AuthController route working! Tenant: '.tenant()?->domain;
    })->name('tenant.test.auth');

    // Login routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/two-factor', [AuthController::class, 'showTwoFactorForm'])->name('tenant.2fa');
    Route::post('/two-factor', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:5,1');
    Route::post('/two-factor/resend', [AuthController::class, 'resendTwoFactorCode'])->middleware('throttle:3,1')->name('tenant.2fa.resend');

    /*
    |--------------------------------------------------------------------------
    | Tenant Public Routes (No Auth Required)
    |--------------------------------------------------------------------------
    */

    // NOTE: Root route (/) is handled by web.php with proper domain constraints
    // web.php routes:
    // - emporiodigital.test/ -> LandingPageController
    // - {tenant}.emporiodigital.test/ -> TenantLandingController
    // Keeping these here would cause conflicts and 500 errors on apex domain

    // Tenant public home page - StoreSettings-powered landing page
    // Route::get('/', [WelcomeController::class, 'index'])->name('tenant.welcome');

    // Legacy route for compatibility
    // Route::get('/tenant', [WelcomeController::class, 'index'])->name('tenant.public.home');

    /*
    |--------------------------------------------------------------------------
    | Tenant Protected Routes (Auth Required)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:tenant', EnforceTenantIsolation::class])->group(function () {

        // Logout route (must be inside auth group)
        Route::post('/logout', [AuthController::class, 'logout'])->name('tenant.logout');

        // Tenant Dashboard - Custom implementation
        Route::get('/tenant/dashboard', function () {
            return view('tenant.dashboard');
        })->name('tenant.dashboard');

        // Demo Page - Show components and features
        Route::get('/tenant/demo', function () {
            return view('tenant.demo');
        })->name('tenant.demo');

        // Tenant Inventory Management
        Route::prefix('/tenant/inventory')->name('tenant.inventory.')->group(function () {
            Route::get('/', function () {
                return view('tenant.inventory.index');
            })->name('index');

            Route::get('/products', function () {
                return view('tenant.inventory.products');
            })->name('products');

            Route::get('/categories', function () {
                return view('tenant.inventory.categories');
            })->name('categories');

            Route::get('/stock-movements', function () {
                return view('tenant.inventory.stock-movements');
            })->name('stock-movements');
        });

        // Tenant Point of Sale (POS)
        Route::prefix('/tenant/pos')->name('tenant.pos.')->group(function () {
            Route::get('/', function () {
                return view('tenant.pos.index');
            })->name('index');

            Route::get('/terminal', function () {
                return view('tenant.pos.terminal');
            })->name('terminal');

            Route::get('/sales', function () {
                return view('tenant.pos.sales');
            })->name('sales');

            Route::get('/returns', function () {
                return view('tenant.pos.returns');
            })->name('returns');
        });

        // Tenant Sales Management
        Route::prefix('/tenant/sales')->name('tenant.sales.')->group(function () {
            Route::get('/', function () {
                return view('tenant.sales.index');
            })->name('index');

            Route::get('/create', function () {
                return view('tenant.sales.create');
            })->name('create');

            Route::get('/{sale}', function ($sale) {
                return view('tenant.sales.show', ['sale' => $sale]);
            })->name('show')->whereNumber('sale');
        });

        // Tenant Customer Management
        Route::prefix('/tenant/customers')->name('tenant.customers.')->group(function () {
            Route::get('/', function () {
                return view('tenant.customers.index');
            })->name('index');

            Route::get('/create', function () {
                return view('tenant.customers.create');
            })->name('create');

            Route::get('/{customer}', function ($customer) {
                return view('tenant.customers.show', ['customer' => $customer]);
            })->name('show')->whereNumber('customer');
        });

        // Tenant Reports
        Route::prefix('/tenant/reports')->name('tenant.reports.')->group(function () {
            Route::get('/', function () {
                return view('tenant.reports.index');
            })->name('index');

            Route::get('/sales', function () {
                return view('tenant.reports.sales');
            })->name('sales');

            Route::get('/inventory', function () {
                return view('tenant.reports.inventory');
            })->name('inventory');

            Route::get('/financial', function () {
                return view('tenant.reports.financial');
            })->name('financial');
        });

        // Tenant Settings
        Route::prefix('/tenant/settings')->name('tenant.settings.')->group(function () {
            Route::get('/', [StoreSettingController::class, 'index'])->name('index');
            Route::post('/update', [StoreSettingController::class, 'updateBasic'])->name('update');
            Route::post('/upload-logo', [StoreSettingController::class, 'uploadLogo'])->name('upload.logo');
            Route::post('/upload-background', [StoreSettingController::class, 'uploadBackground'])->name('upload.background');
            Route::delete('/image/{type}', [StoreSettingController::class, 'deleteImage'])->name('delete.image');
            Route::get('/preview', [StoreSettingController::class, 'preview'])->name('preview');
            Route::get('/storage-stats', [StoreSettingController::class, 'storageStats'])->name('storage.stats');
            Route::get('/current', [StoreSettingController::class, 'getCurrent'])->name('current');

            Route::get('/profile', function () {
                return view('tenant.settings.profile');
            })->name('profile');

            Route::get('/security', function () {
                return view('tenant.settings.security');
            })->name('security');
        });

        // Tenant Billing & Subscription Management - Ernesto-Friendly Interface
        Route::prefix('/billing')->name('tenant.billing.')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('index');
            Route::get('/subscription', [BillingController::class, 'subscription'])->name('subscription');
            Route::post('/payment-proof', [BillingController::class, 'storePaymentProof'])->name('payment-proof.store');
            Route::get('/history', [BillingController::class, 'history'])->name('history');
            Route::get('/invoice/{invoice}', [BillingController::class, 'showInvoice'])->name('invoice.show');
        });

        /*
        |--------------------------------------------------------------------------
        | Tenant Livewire Component Routes
        |--------------------------------------------------------------------------
        |
        | These routes will be populated with actual Livewire components
        | as we implement them in subsequent phases.
        |
        */

        // Example Livewire route patterns (to be implemented):
        // Route::get('/tenant/dashboard', \App\Livewire\Tenant\Dashboard::class)
        //     ->name('tenant.dashboard.livewire');

    });

    /*
    |--------------------------------------------------------------------------
    | Tenant API Routes (for future mobile apps, etc.)
    |--------------------------------------------------------------------------
    */

    Route::prefix('/tenant/api')->name('tenant.api.')->group(function () {
        // API routes will be added here in future phases
        // These will use token-based authentication or similar
    });
});
