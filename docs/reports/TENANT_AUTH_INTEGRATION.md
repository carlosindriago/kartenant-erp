# Tenant Authentication Integration Guide

## Quick Start

### 1. Choose Your Template Type
**Standalone Templates** (Recommended for custom control):
- `/resources/views/tenant/auth/login.blade.php`
- `/resources/views/tenant/auth/two-factor.blade.php`

**Layout-Based Templates** (Recommended for consistency):
- `/resources/views/tenant/auth/login-simple.blade.php`
- `/resources/views/tenant/auth/two-factor-simple.blade.php`
- `/resources/views/tenant/auth/layouts/auth.blade.php`

### 2. Controller Integration
```php
// app/Http/Controllers/Tenant/AuthController.php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $tenant = tenant();
        return view('tenant.auth.login-simple', compact('tenant'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean'
        ]);

        // Your login logic here
        // Redirect to 2FA if needed
        if ($user->two_factor_secret) {
            return redirect()->route('tenant.two-factor', ['email' => $request->email]);
        }

        return redirect()->route('tenant.dashboard');
    }

    public function showTwoFactor(Request $request)
    {
        return view('tenant.auth.two-factor-simple', [
            'tenant' => tenant(),
            'email' => $request->email
        ]);
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'email' => 'required|email'
        ]);

        // Your 2FA verification logic here
        return redirect()->route('tenant.dashboard');
    }

    public function resendTwoFactor(Request $request)
    {
        // Your resend logic here
        return response()->json(['success' => true]);
    }
}
```

### 3. Route Configuration
```php
// routes/tenant.php
use App\Http\Controllers\Tenant\AuthController;

Route::middleware(['guest:tenant'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/two-factor', [AuthController::class, 'showTwoFactor'])->name('two-factor');
    Route::post('/two-factor/confirm', [AuthController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
    Route::post('/two-factor/resend', [AuthController::class, 'resendTwoFactor'])->name('two-factor.resend');
});
```

### 4. Tenant Branding Setup
```php
// in your Tenant model
class Tenant extends Model
{
    protected $casts = [
        'logo_dimensions' => 'array'
    ];

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }

    public function getDisplayNameAttribute()
    {
        return $this->display_name ?: $this->name;
    }
}
```

## Customization Options

### 1. Logo Configuration
```php
// In your controller
$tenant = tenant();
$tenant->logo_url = 'storage/logos/my-logo.png';
$tenant->display_name = 'Ferretería Ernesto';
```

### 2. Color Customization
```blade
<!-- Override primary color by adding to your app.css -->
<style>
:root {
    --primary-color: #0EA5E9; /* Change this */
}

.bg-sky-600 {
    background-color: var(--primary-color) !important;
}

.focus\:ring-sky-500:focus {
    --tw-ring-color: var(--primary-color) !important;
}
</style>
```

### 3. Adding New Fields to Login Form
```blade
<!-- Add to login-simple.blade.php -->
<div class="mt-4">
    <label for="store_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        🏪 Código de Tienda
    </label>
    <input
        id="store_code"
        name="store_code"
        type="text"
        class="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
        placeholder="Código de tu tienda"
    >
</div>
```

## Security Enhancements

### 1. Rate Limiting
```php
// In routes/tenant.php
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/two-factor/confirm', [AuthController::class, 'confirmTwoFactor']);
});
```

### 2. Session Security
```php
// In config/session.php
'expire_on_close' => true,
'secure' => env('APP_ENV') === 'production',
'http_only' => true,
'same_site' => 'strict',
```

### 3. CSRF Protection
All forms include `@csrf` token automatically. Ensure you have the VerifyCsrfToken middleware enabled.

## Error Monitoring Setup
```php
// In your AuthController
use App\Services\ErrorMonitoringService;

public function login(Request $request)
{
    try {
        // Your login logic
    } catch (\Exception $e) {
        ErrorMonitoringService::captureException($e, [
            'tenant_id' => tenant()->id,
            'email' => $request->email,
            'context' => 'tenant_login'
        ]);

        return back()->withErrors(['email' => 'Error temporal. Intenta en unos minutos.']);
    }
}
```

## Performance Optimizations

### 1. Asset Optimization
```bash
# Build optimized assets
npm run build

# Enable compression in nginx
gzip_types text/css application/javascript image/svg+xml;
```

### 2. Caching Strategy
```php
// Cache tenant branding data
$tenantBranding = Cache::remember(
    "tenant.{$tenant->id}.branding",
    3600,
    fn() => [
        'logo_url' => $tenant->logo_url,
        'display_name' => $tenant->display_name,
        'primary_color' => $tenant->primary_color ?? '#0EA5E9'
    ]
);
```

### 3. Lazy Loading
```html
<!-- Add loading="lazy" to tenant logos -->
<img src="{{ $tenant->logo_url }}"
     loading="lazy"
     alt="{{ $tenant->display_name }}"
     class="mx-auto h-24 w-auto object-contain mb-4">
```

## A/B Testing Setup
```blade
<!-- In your layout component -->
@if(app()->environment('production'))
    <script>
        // Google Optimize or similar
        gtag('config', 'GA_MEASUREMENT_ID', {
            'optimize_id': 'OPT_CONTAINER_ID'
        });
    </script>
@endif
```

## Analytics Integration
```html
<!-- Add to auth layout -->
@if(config('analytics.enabled'))
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('analytics.id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('analytics.id') }}', {
            'custom_map': {'dimension1': 'tenant_id'}
        });
        gtag('event', 'tenant_auth_dimension', {'tenant_id': '{{ tenant()->id }}'});
    </script>
@endif
```

## Troubleshooting

### Common Issues
1. **Logo not displaying**: Check file permissions and asset paths
2. **2FA inputs not working**: Verify Alpine.js is loading correctly
3. **Styles not applying**: Ensure Vite assets are built
4. **Mobile responsiveness issues**: Test with actual devices, not just browser dev tools

### Debug Mode
```php
// Enable debugging in .env
APP_DEBUG=true
LOG_LEVEL=debug

// Add to your controller
if (config('app.debug')) {
    logger()->debug('Tenant login attempt', [
        'tenant' => tenant()->id,
        'email' => $request->email
    ]);
}
```

## Support Contact
For integration issues, contact the development team with:
- Error messages
- Steps to reproduce
- Browser/device information
- Tenant ID (if applicable)