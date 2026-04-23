<?php

use App\Filament\App\Pages\Auth\ForgotPassword;
use App\Filament\App\Pages\Auth\ResetPassword;
use App\Filament\App\Pages\Auth\SecurityQuestionsReset;
use App\Filament\App\Pages\Auth\SetupSecurityQuestions;
use App\Filament\App\Pages\Auth\VerifySecurityCode;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Http\Controllers\DebugLogController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\BugReportController;
use App\Http\Controllers\Tenant\CreditNoteController;
use App\Http\Controllers\Tenant\InternalVerificationController;
use App\Http\Controllers\Tenant\POSReceiptController;
use App\Http\Controllers\Tenant\StockMovementController;
use App\Http\Controllers\TenantLandingController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\AuthenticateTenantUser;
use App\Http\Middleware\CheckVerificationAccess;
use App\Http\Middleware\MakeSpatieTenantCurrent;
use App\Http\Middleware\RequireInternalVerificationPermission;
use App\Http\Middleware\VerificationSecurityMiddleware;
use App\Livewire\POS\PointOfSale;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\SetUpPanel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Health Check endpoint for monitoring (no auth required)
Route::get('/health', HealthCheckController::class)->name('health');

// Comenta o borra la ruta por defecto de Laravel
// Route::get('/', function () {
//     return view('welcome');
// });

// Apex domain landing only
Route::domain('kartenant.test')->group(function () {
    Route::get('/', [LandingPageController::class, 'show'])->name('landing');
    Route::get('/acceder', [LandingPageController::class, 'showTenantLoginForm'])->name('tenant.login.form');
    Route::post('/acceder', [LandingPageController::class, 'redirectToTenantLogin'])->name('tenant.login.redirect');
});

// Tenant subdomain landing
Route::domain('{tenant}.kartenant.test')->group(function () {
    Route::get('/', [TenantLandingController::class, 'show'])->name('tenant.landing');
    // Optional: simple redirect for /login on subdomain to the Filament app login
    Route::get('/login', function () {
        return redirect('/app/login');
    })->name('tenant.login.redirect.short');

    // Forgot password flow routes
    Route::get('/forgot-password', ForgotPassword::class)
        ->name('tenant.forgot-password');
    Route::get('/verify-security-code', VerifySecurityCode::class)
        ->name('tenant.verify-security-code');
    Route::get('/security-questions-reset', SecurityQuestionsReset::class)
        ->name('tenant.security-questions-reset');
    Route::get('/reset-password', ResetPassword::class)
        ->name('tenant.reset-password');

    // Security questions setup route
    Route::get('/setup-security-questions', SetupSecurityQuestions::class)
        ->name('tenant.setup-security-questions');

    // POS Terminal - Fullscreen experience outside Filament
    Route::get('/pos', PointOfSale::class)
        ->middleware([
            'web',
            MakeSpatieTenantCurrent::class,
            AuthenticateTenantUser::class,
        ])
        ->name('tenant.pos');

    // POS Receipt Routes - PDF y Print
    Route::middleware([
        'web',
        MakeSpatieTenantCurrent::class,
        AuthenticateTenantUser::class,
    ])->prefix('pos/receipt')->name('tenant.pos.receipt.')->group(function () {
        Route::get('/{sale}/pdf', [POSReceiptController::class, 'downloadPDF'])
            ->name('pdf')
            ->whereNumber('sale');
        Route::get('/{sale}/print', [POSReceiptController::class, 'print'])
            ->name('print')
            ->whereNumber('sale');
    });

    // Credit Note Routes - PDF para Notas de Crédito (Devoluciones)
    Route::middleware([
        'web',
        MakeSpatieTenantCurrent::class,
        AuthenticateTenantUser::class,
    ])->prefix('pos/credit-note')->name('tenant.pos.credit-note.')->group(function () {
        Route::get('/{saleReturn}/pdf', [CreditNoteController::class, 'download'])
            ->name('pdf')
            ->whereNumber('saleReturn');
        Route::get('/{saleReturn}/view', [CreditNoteController::class, 'view'])
            ->name('view')
            ->whereNumber('saleReturn');
    });

    // Stock Movement Routes - PDF para Movimientos de Inventario
    Route::middleware([
        'web',
        MakeSpatieTenantCurrent::class,
        AuthenticateTenantUser::class,
    ])->prefix('stock-movements')->name('tenant.stock-movements.')->group(function () {
        Route::get('/{movement}/download', [StockMovementController::class, 'download'])
            ->name('download')
            ->whereNumber('movement');
    });

    // Internal Verification Routes - Verificación de documentos internos (requiere autenticación)
    Route::middleware([
        'web',
        MakeSpatieTenantCurrent::class,
        AuthenticateTenantUser::class,
        RequireInternalVerificationPermission::class,
    ])->prefix('app/internal-verify')->name('tenant.internal-verification.')->group(function () {
        Route::get('/{hash}', [InternalVerificationController::class, 'show'])
            ->name('show');
        Route::get('/{hash}/pdf', [InternalVerificationController::class, 'downloadPdf'])
            ->name('pdf');
        Route::post('/{hash}/verify', [InternalVerificationController::class, 'verify'])
            ->name('verify');
    });

    // Bug Report Route - Reporte de problemas desde el panel del tenant
    // Uses tenant guard for authentication (same as Filament app panel)
    Route::post('/app/bug-report', [BugReportController::class, 'submit'])
        ->middleware(['web', 'auth:tenant'])
        ->name('tenant.bug-report');

    // Debug Log Route - Para diagnosticar problemas de navegación
    Route::post('/app/debug-log', [DebugLogController::class, 'store'])
        ->middleware(['web'])
        ->name('tenant.debug-log');
});

// Public Document Verification Routes (protegido con seguridad multicapa)
Route::prefix('verify')
    ->name('verify.')
    ->middleware([
        VerificationSecurityMiddleware::class, // Protección contra ataques
        CheckVerificationAccess::class,        // Control de acceso
    ])
    ->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');
        Route::get('/{hash}', [VerificationController::class, 'verify'])->name('hash');
        Route::post('/api', [VerificationController::class, 'verifyApi'])->name('api');
    });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Ruta pública para el desafío 2FA del panel admin (sin middleware de autenticación)
Route::middleware([
    SetUpPanel::class.':admin',
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    SubstituteBindings::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
])->group(function () {
    Route::get('/admin/two-factor-challenge', TwoFactorChallenge::class)
        ->name('filament.admin.two-factor-challenge');
});
