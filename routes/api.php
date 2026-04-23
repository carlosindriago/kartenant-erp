<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\TenantBillingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API v1 Routes for Kartenant v2.0
| Base URL: /api/v1
|
| All routes use 'api' middleware which includes:
| - JSON responses
| - Rate limiting
| - CORS
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Authentication endpoints
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    Route::post('/auth/password/forgot', [AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot-password');
    Route::post('/auth/password/reset', [AuthController::class, 'resetPassword'])->name('api.v1.auth.reset-password');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant.api'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.v1.auth.refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
    });

    /*
    |--------------------------------------------------------------------------
    | Products API
    |--------------------------------------------------------------------------
    */
    // Route::apiResource('products', ProductController::class);
    // Route::get('products/search', [ProductController::class, 'search'])->name('api.v1.products.search');
    // Route::get('products/{id}/movements', [ProductController::class, 'movements'])->name('api.v1.products.movements');
    // Route::get('products/{id}/sales-history', [ProductController::class, 'salesHistory'])->name('api.v1.products.sales-history');

    /*
    |--------------------------------------------------------------------------
    | Categories API
    |--------------------------------------------------------------------------
    */
    // Route::apiResource('categories', CategoryController::class);

    /*
    |--------------------------------------------------------------------------
    | Stock Movements API
    |--------------------------------------------------------------------------
    */
    // Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'show']);
    // Route::post('stock-movements/entry', [StockMovementController::class, 'createEntry'])->name('api.v1.stock-movements.entry');
    // Route::post('stock-movements/exit', [StockMovementController::class, 'createExit'])->name('api.v1.stock-movements.exit');
    // Route::get('stock-movements/{id}/pdf', [StockMovementController::class, 'downloadPdf'])->name('api.v1.stock-movements.pdf');

    /*
    |--------------------------------------------------------------------------
    | Sales API
    |--------------------------------------------------------------------------
    */
    // Route::apiResource('sales', SaleController::class);
    // Route::post('sales/{id}/return', [SaleController::class, 'return'])->name('api.v1.sales.return');
    // Route::get('sales/{id}/receipt', [SaleController::class, 'receipt'])->name('api.v1.sales.receipt');
    // Route::get('sales/stats', [SaleController::class, 'stats'])->name('api.v1.sales.stats');

    /*
    |--------------------------------------------------------------------------
    | Customers API
    |--------------------------------------------------------------------------
    */
    // Route::apiResource('customers', CustomerController::class);
    // Route::get('customers/{id}/sales', [CustomerController::class, 'sales'])->name('api.v1.customers.sales');
    // Route::get('customers/{id}/stats', [CustomerController::class, 'stats'])->name('api.v1.customers.stats');

    /*
    |--------------------------------------------------------------------------
    | Suppliers API
    |--------------------------------------------------------------------------
    */
    // Route::apiResource('suppliers', SupplierController::class);

    /*
    |--------------------------------------------------------------------------
    | Cash Registers API
    |--------------------------------------------------------------------------
    */
    // Route::get('cash-registers', [CashRegisterController::class, 'index'])->name('api.v1.cash-registers.index');
    // Route::post('cash-registers/open', [CashRegisterController::class, 'open'])->name('api.v1.cash-registers.open');
    // Route::post('cash-registers/close', [CashRegisterController::class, 'close'])->name('api.v1.cash-registers.close');
    // Route::get('cash-registers/current', [CashRegisterController::class, 'current'])->name('api.v1.cash-registers.current');
    // Route::get('cash-registers/{id}/movements', [CashRegisterController::class, 'movements'])->name('api.v1.cash-registers.movements');

    /*
    |--------------------------------------------------------------------------
    | Reports API
    |--------------------------------------------------------------------------
    */
    // Route::prefix('reports')->group(function () {
    //     Route::get('dashboard', [ReportController::class, 'dashboard'])->name('api.v1.reports.dashboard');
    //     Route::get('sales', [ReportController::class, 'sales'])->name('api.v1.reports.sales');
    //     Route::get('inventory', [ReportController::class, 'inventory'])->name('api.v1.reports.inventory');
    //     Route::get('abc-analysis', [ReportController::class, 'abcAnalysis'])->name('api.v1.reports.abc-analysis');
    //     Route::get('profitability', [ReportController::class, 'profitability'])->name('api.v1.reports.profitability');
    // });

    /*
    |--------------------------------------------------------------------------
    | Settings API
    |--------------------------------------------------------------------------
    */
    // Route::get('settings', [SettingsController::class, 'index'])->name('api.v1.settings.index');
    // Route::put('settings', [SettingsController::class, 'update'])->name('api.v1.settings.update');
    // Route::get('settings/branding', [SettingsController::class, 'branding'])->name('api.v1.settings.branding');
    // Route::put('settings/branding', [SettingsController::class, 'updateBranding'])->name('api.v1.settings.branding.update');

    /*
    |--------------------------------------------------------------------------
    | Billing API
    |--------------------------------------------------------------------------
    */
    Route::prefix('billing')->group(function () {
        Route::get('/', [TenantBillingController::class, 'index'])->name('api.v1.billing.index');
        Route::post('/', [TenantBillingController::class, 'store'])->name('api.v1.billing.store');
        Route::get('/history', [TenantBillingController::class, 'history'])->name('api.v1.billing.history');
        Route::get('/payment-proofs/{id}', [TenantBillingController::class, 'show'])->name('api.v1.billing.show');
        Route::delete('/payment-proofs/{id}', [TenantBillingController::class, 'destroy'])->name('api.v1.billing.destroy');

        // Download payment proof file
        Route::get('/payment-proofs/{id}/files/{file_path}', [TenantBillingController::class, 'downloadFile'])
            ->where('file_path', '.*')
            ->name('api.v1.billing.download-file');
    });
});
