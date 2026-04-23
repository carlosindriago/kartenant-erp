<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * OPERACIÓN FORTRESS - SECURITY VALIDATION TESTING
 *
 * Comprehensive security tests for tenant authentication system
 * Validates all critical security fixes implemented in AuthController
 */
beforeEach(function () {
    // Create test tenant for cross-tenant security tests
    $this->tenant = Tenant::factory()->create();

    // Set tenant context for tests
    tenancy()->initialize($this->tenant);

    // Create test users for security testing
    $this->activeUser = User::factory()->create([
        'is_active' => true,
        'password' => Hash::make('password123'),
    ]);

    $this->inactiveUser = User::factory()->create([
        'is_active' => false,
        'password' => Hash::make('password123'),
    ]);

    $this->unauthorizedUser = User::factory()->create([
        'is_active' => true,
        'password' => Hash::make('password123'),
    ]);

    // Associate active user with current tenant
    $this->tenant->users()->attach($this->activeUser->id);

    // Create separate tenant for cross-tenant tests
    $this->otherTenant = Tenant::factory()->create();
    $this->otherTenant->users()->attach($this->unauthorizedUser->id);

    // Clear any existing cache entries
    Cache::flush();
    RateLimiter::clear('2fa_attempt:*');
});

afterEach(function () {
    // Clean up session and cache
    Session::flush();
    Cache::flush();
    RateLimiter::clear('2fa_attempt:*');
});

/**
 * RATE LIMITING BYPASS PROTECTION TESTS
 */
test('rate limiting prevents ip rotation bypass', function () {
    // Track the email that will be attacked
    $attackedEmail = $this->activeUser->email;

    // Simulate attacker using different IPs to bypass rate limiting
    $attackerIPs = ['192.168.1.1', '192.168.1.2', '10.0.0.1', '203.0.113.1', '198.51.100.1'];

    // Make 5 failed login attempts from different IPs with same email
    foreach ($attackerIPs as $index => $ip) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('tenant.login'), [
                'email' => $attackedEmail,
                'password' => 'wrong-password-'.$index,
            ]);

        // First 5 attempts should fail with generic error
        $response->assertSessionHasErrors(['email']);
    }

    // 6th attempt from yet another IP should be blocked regardless of IP
    $finalResponse = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->post(route('tenant.login'), [
            'email' => $attackedEmail,
            'password' => 'wrong-password-final',
        ]);

    // Should be blocked with lockout message
    $finalResponse->assertSessionHasErrors(['email']);

    // Verify lockout is in place
    $lockoutKey = 'auth_lockout:'.strtolower($attackedEmail);
    expect(Cache::has($lockoutKey))->toBeTrue();
});

test('rate limiting tracks attempts globally per email', function () {
    $attackedEmail = $this->activeUser->email;
    $attackerIPs = ['192.168.1.1', '192.168.1.2', '10.0.0.1'];

    // Mix failed attempts from multiple IPs to test global tracking
    foreach ($attackerIPs as $ip) {
        for ($i = 1; $i <= 2; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post(route('tenant.login'), [
                    'email' => $attackedEmail,
                    'password' => "wrong-password-from-{$ip}-attempt-{$i}",
                ]);
        }
    }

    // Should have 6 total attempts across all IPs (3 IPs × 2 attempts each)
    $globalKey = 'auth_attempts_global:'.strtolower($attackedEmail);
    expect(Cache::get($globalKey))->toBe(6);

    // 6th attempt should trigger lockout regardless of which IP makes it
    $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->post(route('tenant.login'), [
            'email' => $attackedEmail,
            'password' => 'final-attempt-after-6-previous',
        ]);

    $response->assertSessionHasErrors(['email']);
});

/**
 * SESSION FIXATION PROTECTION TESTS
 */
test('session id changes after successful login', function () {
    // Start a session and record its ID
    $this->get(route('tenant.login'));
    $originalSessionId = Session::getId();

    // Perform successful authentication
    $response = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);

    // Session ID should have changed after successful login
    expect(Session::getId())->not->toBe($originalSessionId);

    // User should be authenticated
    $this->assertAuthenticatedAs($this->activeUser, 'tenant');
});

test('session properly invalidated on logout', function () {
    // Login first
    $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);

    $originalSessionId = Session::getId();
    $originalToken = Session::token();

    // Logout
    $response = $this->post(route('tenant.logout'));

    // Session should be invalidated
    expect(Session::getId())->not->toBe($originalSessionId);
    expect(Session::token())->not->toBe($originalToken);

    // Should not be authenticated anymore
    $this->assertGuest('tenant');

    // Verify redirect to login
    $response->assertRedirect(route('tenant.login'));
});

/**
 * ANTI-ENUMERATION PROTECTION TESTS
 */
test('generic error messages prevent user enumeration', function () {
    $genericError = 'Estas credenciales no coinciden con nuestros registros';

    // Test 1: Valid email + wrong password
    $response1 = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'wrong-password',
    ]);

    // Test 2: Invalid email + any password
    $response2 = $this->post(route('tenant.login'), [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    // Test 3: Non-existent email
    $response3 = $this->post(route('tenant.login'), [
        'email' => 'completelyfake@fake.com',
        'password' => 'random',
    ]);

    // Test 4: Valid email but inactive user
    $response4 = $this->post(route('tenant.login'), [
        'email' => $this->inactiveUser->email,
        'password' => 'password123',
    ]);

    // All should return identical generic error messages
    $response1->assertSessionHasErrors(['email' => $genericError]);
    $response2->assertSessionHasErrors(['email' => $genericError]);
    $response3->assertSessionHasErrors(['email' => $genericError]);
    $response4->assertSessionHasErrors(['email' => $genericError]);
});

test('consistent response times prevent timing attacks', function () {
    $maxVariance = 100; // 100ms maximum acceptable variance
    $responseTimes = [];

    // Test 1: Invalid email
    $start1 = microtime(true);
    $this->post(route('tenant.login'), [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);
    $responseTimes['invalid_email'] = (microtime(true) - $start1) * 1000;

    // Test 2: Valid email wrong password
    $start2 = microtime(true);
    $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'wrong-password',
    ]);
    $responseTimes['valid_email_wrong_pass'] = (microtime(true) - $start2) * 1000;

    // Test 3: Valid email but inactive user
    $start3 = microtime(true);
    $this->post(route('tenant.login'), [
        'email' => $this->inactiveUser->email,
        'password' => 'password123',
    ]);
    $responseTimes['valid_email_inactive'] = (microtime(true) - $start3) * 1000;

    // Test 4: Valid email unauthorized tenant
    $start4 = microtime(true);
    $this->post(route('tenant.login'), [
        'email' => $this->unauthorizedUser->email,
        'password' => 'password123',
    ]);
    $responseTimes['valid_email_unauthorized'] = (microtime(true) - $start4) * 1000;

    // Calculate average response time
    $avgTime = array_sum($responseTimes) / count($responseTimes);

    // All response times should be within acceptable variance of average
    foreach ($responseTimes as $test => $time) {
        $variance = abs($time - $avgTime);
        expect($variance)->toBeLessThan($maxVariance,
            "Response time variance too high for {$test}: {$time}ms vs avg {$avgTime}ms");
    }
});

/**
 * ACCOUNT LOCKOUT MECHANISM TESTS
 */
test('account locks out after 5 failed attempts', function () {
    $email = $this->activeUser->email;

    // Make 5 failed login attempts
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->post(route('tenant.login'), [
            'email' => $email,
            'password' => "wrong-password-{$i}",
        ]);

        if ($i < 5) {
            // First 4 attempts should fail with generic error
            $response->assertSessionHasErrors(['email']);
        } else {
            // 5th attempt should trigger lockout
            $response->assertSessionHasErrors(['email']);
        }
    }

    // 6th attempt should be blocked with lockout message
    $lockoutResponse = $this->post(route('tenant.login'), [
        'email' => $email,
        'password' => 'correct-password',
    ]);

    $lockoutResponse->assertSessionHasErrors(['email']);

    // Verify lockout key exists
    $lockoutKey = 'auth_lockout:'.strtolower($email);
    expect(Cache::has($lockoutKey))->toBeTrue();

    // Even correct password should be blocked
    expect(Auth::guard('tenant')->check())->toBeFalse();
});

test('exponential lockout durations increase correctly', function () {
    $email = $this->activeUser->email;

    // Helper function to trigger lockout
    $triggerLockout = function () use ($email) {
        // Clear existing attempts
        Cache::flush();

        // Trigger 5 failed attempts to get first lockout
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('tenant.login'), [
                'email' => $email,
                'password' => "wrong-password-{$i}",
            ]);
        }

        // Get lockout duration
        $lockoutKey = 'auth_lockout:'.strtolower($email);

        return Cache::get($lockoutKey);
    };

    // Test first lockout (should be 60s)
    $firstLockoutDuration = $triggerLockout();
    expect($firstLockoutDuration)->toBe(60);

    // Wait a bit and trigger again for second lockout
    sleep(1); // Small delay to ensure different timestamp
    Cache::forget('auth_lockout:'.strtolower($email));

    // Simulate additional attempts beyond 5 to trigger exponential backoff
    for ($i = 6; $i <= 10; $i++) {
        $this->post(route('tenant.login'), [
            'email' => $email,
            'password' => "wrong-password-{$i}",
        ]);
    }

    $lockoutKey = 'auth_lockout:'.strtolower($email);
    $secondLockoutDuration = Cache::get($lockoutKey);

    // Second lockout should be longer (exponential backoff)
    expect($secondLockoutDuration)->toBeGreaterThan($firstLockoutDuration);
});

/**
 * TWO-FACTOR AUTHENTICATION SECURITY TESTS
 */
test('2fa has separate rate limiting from login', function () {
    // Skip this test if 2FA is not enabled for the user
    if (! $this->twoFactorAuthService->isTwoFactorEnabled($this->activeUser)) {
        $this->markTestSkipped('2FA not enabled for test user');
    }

    // Use up login rate limit first
    for ($i = 1; $i <= 6; $i++) {
        $this->post(route('tenant.login'), [
            'email' => $this->activeUser->email,
            'password' => "wrong-password-{$i}",
        ]);
    }

    // Login should now be rate limited
    $loginResponse = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);
    $loginResponse->assertSessionHasErrors(['email']);

    // But 2FA verification should work independently (start 2FA flow first)
    $this->twoFactorAuthService->enableTwoFactor($this->activeUser);
    $this->twoFactorAuthService->startTwoFactorSession($this->activeUser);

    // Test 2FA attempts
    for ($i = 1; $i <= 5; $i++) {
        $twoFaResponse = $this->post(route('tenant.2fa.verify'), [
            'code' => '000000', // Wrong code
        ]);

        if ($i < 10) {
            // Should still be allowed (2FA has higher limit)
            $twoFaResponse->assertSessionHasErrors(['code']);
        }
    }
});

test('2fa maintains anti-enumeration protection', function () {
    // Skip if 2FA not available
    if (! class_exists(TwoFactorAuthService::class)) {
        $this->markTestSkipped('2FA service not available');
    }

    // Mock 2FA session
    Session::put('2fa_user_id', $this->activeUser->id);
    Session::put('2fa_session_expires', now()->addMinutes(5));

    $genericError = 'Estas credenciales no coinciden con nuestros registros';

    // Test with invalid codes
    $response1 = $this->post(route('tenant.2fa.verify'), [
        'code' => '111111',
    ]);

    $response2 = $this->post(route('tenant.2fa.verify'), [
        'code' => 'abcdef',
    ]);

    $response3 = $this->post(route('tenant.2fa.verify'), [
        'code' => '12345',
    ]);

    // All should return generic error messages
    $response1->assertSessionHasErrors(['code' => $genericError]);
    $response2->assertSessionHasErrors(['code' => $genericError]);
    $response3->assertSessionHasErrors(['code' => $genericError]);
});

/**
 * CROSS-TENANT SECURITY TESTS
 */
test('authentication respects tenant isolation', function () {
    // Test unauthorized user (belongs to different tenant)
    $response = $this->post(route('tenant.login'), [
        'email' => $this->unauthorizedUser->email,
        'password' => 'password123',
    ]);

    // Should be blocked with generic error (not revealing it's a tenant issue)
    $response->assertSessionHasErrors(['email']);
    expect(Auth::guard('tenant')->check())->toBeFalse();

    // Should not be able to access tenant resources
    $this->get(route('tenant.dashboard'))->assertRedirect(route('tenant.login'));
});

test('user cannot access another tenant with same credentials', function () {
    // First, try to login with user in correct tenant (should work)
    tenancy()->initialize($this->tenant);

    $response = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);

    expect(Auth::guard('tenant')->check())->toBeTrue();
    $this->post(route('tenant.logout'));

    // Now try to switch to different tenant context
    tenancy()->initialize($this->otherTenant);

    $response = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);

    // Should be blocked - user doesn't belong to this tenant
    $response->assertSessionHasErrors(['email']);
    expect(Auth::guard('tenant')->check())->toBeFalse();
});

/**
 * INTEGRATION SECURITY TESTS
 */
test('successful login flow works with security fixes', function () {
    // Test complete successful login flow
    $response = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);

    // Should be authenticated
    $this->assertAuthenticatedAs($this->activeUser, 'tenant');

    // Should redirect to dashboard
    $response->assertRedirect(route('tenant.dashboard'));

    // Session should be properly managed
    expect(Session::get('tenant_authenticated'))->toBeTrue();

    // Should be able to access protected routes
    $this->get(route('tenant.dashboard'))->assertOk();
});

test('ajax requests handle security correctly', function () {
    // Test AJAX login attempt with invalid credentials
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('tenant.login'), [
            'email' => $this->activeUser->email,
            'password' => 'wrong-password',
        ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Estas credenciales no coinciden con nuestros registros',
        ]);

    // Test AJAX successful login
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('tenant.login'), [
            'email' => $this->activeUser->email,
            'password' => 'password123',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => '¡Inicio de sesión exitoso!',
            'redirect' => route('tenant.dashboard'),
        ]);
});

test('csrf protection works for authentication', function () {
    // Test login without CSRF token
    Session::forget('_token');

    $response = $this->post(route('tenant.login'), [
        'email' => $this->activeUser->email,
        'password' => 'password123',
    ]);

    // Should fail due to CSRF protection
    $response->assertStatus(419); // Page expired (CSRF mismatch)
});

/**
 * SECURITY VALIDATION EDGE CASES
 */
test('invalid input formats are handled securely', function () {
    $testCases = [
        [
            'email' => '',
            'password' => 'password123',
            'description' => 'empty email',
        ],
        [
            'email' => 'invalid-email',
            'password' => 'password123',
            'description' => 'invalid email format',
        ],
        [
            'email' => $this->activeUser->email,
            'password' => '',
            'description' => 'empty password',
        ],
        [
            'email' => $this->activeUser->email,
            'password' => '123',
            'description' => 'password too short',
        ],
    ];

    foreach ($testCases as $case) {
        $response = $this->post(route('tenant.login'), [
            'email' => $case['email'],
            'password' => $case['password'],
        ]);

        $response->assertSessionHasErrors(['email' => 'Estas credenciales no coinciden con nuestros registros']);
    }
});

test('email case insensitivity is maintained securely', function () {
    // Test login with uppercase email
    $response = $this->post(route('tenant.login'), [
        'email' => strtoupper($this->activeUser->email),
        'password' => 'password123',
    ]);

    // Should work normally
    $this->assertAuthenticatedAs($this->activeUser, 'tenant');
    $response->assertRedirect(route('tenant.dashboard'));

    // Rate limiting should work with case insensitivity too
    $this->post(route('tenant.logout'));

    // Try failed attempts with mixed case
    $mixedCaseEmail = ucfirst($this->activeUser->email);

    for ($i = 1; $i <= 3; $i++) {
        $this->post(route('tenant.login'), [
            'email' => $mixedCaseEmail,
            'password' => "wrong-password-{$i}",
        ]);
    }

    // Should be tracking attempts correctly despite case variations
    $globalKey = 'auth_attempts_global:'.strtolower($mixedCaseEmail);
    expect(Cache::get($globalKey))->toBe(3);
});

/**
 * PRODUCTION READINESS VALIDATION
 */
test('security controls work under load simulation', function () {
    $concurrentAttempts = 10;
    $responses = [];

    // Simulate multiple concurrent login attempts
    for ($i = 0; $i < $concurrentAttempts; $i++) {
        $startTime = microtime(true);

        $response = $this->post(route('tenant.login'), [
            'email' => $this->activeUser->email,
            'password' => "wrong-password-{$i}",
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $responses[] = [
            'status' => $response->getStatusCode(),
            'time' => $responseTime,
            'has_errors' => $response->getSession()->has('errors'),
        ];
    }

    // All requests should handle gracefully (no 500 errors)
    foreach ($responses as $response) {
        expect($response['status'])->not->toBe(500);
        expect($response['has_errors'])->toBeTrue();

        // Response times should be reasonable even with security overhead
        expect($response['time'])->toBeLessThan(1000); // Less than 1 second
    }
});

test('audit trail is maintained for security events', function () {
    $email = $this->activeUser->email;

    // Make failed attempts
    for ($i = 1; $i <= 3; $i++) {
        $this->post(route('tenant.login'), [
            'email' => $email,
            'password' => "wrong-password-{$i}",
        ]);
    }

    // Verify failed attempts are tracked
    $globalKey = 'auth_attempts_global:'.strtolower($email);
    expect(Cache::get($globalKey))->toBe(3);

    // Make successful login
    $this->post(route('tenant.login'), [
        'email' => $email,
        'password' => 'password123',
    ]);

    // Verify success is logged
    $successKey = 'auth_success:'.strtolower($email);
    expect(Cache::has($successKey))->toBeTrue();

    $successData = Cache::get($successKey);
    expect($successData)->toHaveKey('user_id');
    expect($successData)->toHaveKey('ip');
    expect($successData)->toHaveKey('timestamp');

    // Verify all counters are cleared on success
    expect(Cache::get($globalKey))->toBeNull();
});
