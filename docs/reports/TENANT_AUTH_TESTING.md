# Tenant Authentication Testing Guide

## Overview
This document provides testing instructions for the new tenant authentication system designed for "Ernesto" (non-technical hardware store owners).

## Files Created
- `/resources/views/tenant/auth/login.blade.php` - Full standalone login page
- `/resources/views/tenant/auth/login-simple.blade.php` - Login using layout component
- `/resources/views/tenant/auth/two-factor.blade.php` - Full standalone 2FA page
- `/resources/views/tenant/auth/two-factor-simple.blade.php` - 2FA using layout component
- `/resources/views/tenant/auth/layouts/auth.blade.php` - Reusable auth layout
- `/public/images/emporio-logo.svg` - Fallback logo for development

## Testing Checklist

### ✅ Mobile Responsiveness (320px+)
- [ ] Test on iPhone SE (375px)
- [ ] Test on Android small screen (360px)
- [ ] Test landscape orientation
- [ ] Verify touch targets are minimum 44px
- [ ] Test one-handed thumb reach (primary actions)

### ✅ Accessibility (WCAG 2.1 AA)
- [ ] Screen reader navigation with VoiceOver/TalkBack
- [ ] Keyboard-only navigation (Tab, Shift+Tab, Enter, Space)
- [ ] Color contrast verification (4.5:1 ratio)
- [ ] Focus indicators visible on all interactive elements
- [ ] ARIA labels and roles properly set
- [ ] Form validation errors properly announced

### ✅ Functionality Testing
- [ ] Valid credentials flow
- [ ] Invalid credentials error handling
- [ ] Empty form submission
- [ ] Password show/hide (if implemented)
- [ ] Remember me functionality
- [ ] Forgot password link navigation

### ✅ 2FA Testing
- [ ] Individual digit input (0-9 only)
- [ ] Auto-focus between fields
- [ ] Backspace navigation
- [ ] Paste 6-digit code support
- [ ] Invalid code error handling
- [ ] Resend code functionality with countdown
- [ ] Auto-submit when complete (if enabled)

### ✅ Tenant Branding
- [ ] Custom logo display (when available)
- [ ] Fallback logo display
- [ ] Tenant name display
- [ ] Dark mode compatibility
- [ ] Multiple tenant testing

### ✅ Error States
- [ ] Network connection issues
- [ ] Server errors (500)
- [ ] Form validation errors
- [ ] 2FA timeout errors
- [ ] Session expiration

### ✅ Performance
- [ ] Page load time under 3 seconds
- [ ] JavaScript bundle size
- [ ] Image optimization
- [ ] Cache headers verification

## Testing Scenarios

### 1. Login Screen Testing
```
Scenario 1: Successful Login
1. Enter valid email: ernesto@ferreteria.com
2. Enter valid password: contraseña123
3. Click "Iniciar Sesión"
Expected: Redirect to dashboard or 2FA screen

Scenario 2: Invalid Credentials
1. Enter valid email: ernesto@ferreteria.com
2. Enter invalid password: wrongpassword
3. Click "Iniciar Sesión"
Expected: Error message "Credenciales incorrectas"

Scenario 3: Empty Fields
1. Click "Iniciar Sesión" without filling fields
Expected: Field validation errors
```

### 2. Two-Factor Testing
```
Scenario 1: Valid 2FA Code
1. Enter 6-digit code: 123456
2. Click "Verificar Código"
Expected: Redirect to dashboard

Scenario 2: Invalid 2FA Code
1. Enter invalid 6-digit code: 000000
2. Click "Verificar Código"
Expected: Error message + shake animation

Scenario 3: Paste Full Code
1. Copy 6-digit code
2. Paste into first input field
Expected: Code distributed across all fields

Scenario 4: Resend Code
1. Click "Reenviar código"
Expected: 60-second countdown starts
```

### 3. Mobile Testing
```
Device Testing Priority:
1. iPhone SE (375x667) - Small screen
2. iPhone 12 (390x844) - Modern iPhone
3. Samsung Galaxy S20 (360x800) - Android
4. iPad (768x1024) - Tablet

Test Areas:
- Thumb reach for primary buttons
- Input field usability
- Virtual keyboard behavior
- Orientation changes
```

### 4. Accessibility Testing
```
Screen Reader Testing:
- VoiceOver (iOS)
- TalkBack (Android)
- NVDA/JAWS (Desktop)

Keyboard Navigation:
- Tab order logical
- Focus visible
- Enter/Space to submit
- Escape to cancel (if applicable)

Color Contrast:
- Use Chrome DevTools Lighthouse
- Verify text/background ratios
- Test dark mode colors
```

## Browser Testing Matrix
- ✅ Chrome (Latest)
- ✅ Firefox (Latest)
- ✅ Safari (Latest)
- ✅ Edge (Latest)
- ⚠️  Internet Explorer 11 (Not supported)

## Integration Notes

### Route Requirements
```php
// These routes need to exist in tenant.php
Route::get('/login', [TenantAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [TenantAuthController::class, 'login']);
Route::get('/two-factor', [TenantAuthController::class, 'showTwoFactor'])->name('two-factor');
Route::post('/two-factor/confirm', [TenantAuthController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
Route::post('/two-factor/resend', [TenantAuthController::class, 'resendTwoFactor'])->name('two-factor.resend');
```

### Tenant Data Structure
```php
// Tenant model should have these properties
$tenant->display_name  // Human readable name
$tenant->logo_url      // Path to logo asset
$tenant->logo_dimensions // ['width' => 120, 'height' => 120]
```

### Session Variables Used
- `session('status')` - Success messages
- `session('errors')` - Validation errors
- `request()->email` - Email for 2FA screen

## Performance Benchmarks
- First Contentful Paint: < 1.5s
- Largest Contentful Paint: < 2.5s
- Time to Interactive: < 3.0s
- Cumulative Layout Shift: < 0.1

## Ernesto-Filter Validation
✅ **3-Second Rule**: Primary action (login) is immediately visible
✅ **Thumb Zone**: All primary actions are reachable on mobile
✅ **Fear Factor**: Clean, minimal interface with progressive disclosure
✅ **Spanish Language**: All text in business-friendly Spanish
✅ **Error Clarity**: Error messages are helpful and actionable
✅ **Confidence Building**: Professional design builds trust

## Launch Readiness
Before deploying to production, ensure:
- [ ] All tests pass on target browsers
- [ ] Performance benchmarks met
- [ ] Accessibility audit completed
- [ ] Security review completed
- [ ] User acceptance testing with actual hardware store owners
- [ ] Error monitoring configured
- [ ] Analytics tracking implemented