<?php

namespace Tests\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * Helper utilities for security testing
 * Provides common methods for simulating attacks and validating security controls
 */
trait SecurityTestHelpers
{
    /**
     * Create a tenant with associated user for testing
     */
    protected function createTestTenantWithUser($userData = [], $tenantData = [])
    {
        $tenant = Tenant::factory()->create($tenantData);
        $user = User::factory()->create(array_merge([
            'is_active' => true,
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ], $userData));

        $tenant->users()->attach($user->id);

        return ['tenant' => $tenant, 'user' => $user];
    }

    /**
     * Simulate an IP rotation attack
     */
    protected function simulateIpRotationAttack($email, $passwords, $ips = [])
    {
        $defaultIps = [
            '192.168.1.1', '192.168.1.2', '10.0.0.1',
            '203.0.113.1', '198.51.100.1', '8.8.8.8',
        ];

        $attackIps = empty($ips) ? $defaultIps : $ips;
        $responses = [];

        foreach ($passwords as $index => $password) {
            $ip = $attackIps[$index % count($attackIps)];

            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post(route('tenant.login'), [
                    'email' => $email,
                    'password' => $password,
                ]);

            $responses[] = [
                'ip' => $ip,
                'response' => $response,
                'attempt' => $index + 1,
            ];
        }

        return $responses;
    }

    /**
     * Measure response time for timing attack prevention validation
     */
    protected function measureResponseTime($callback)
    {
        $startTime = microtime(true);
        $result = $callback();
        $endTime = microtime(true);

        return [
            'result' => $result,
            'time_ms' => ($endTime - $startTime) * 1000,
        ];
    }

    /**
     * Generate realistic attack scenarios
     */
    protected function generateAttackPayloads($baseEmail, $basePassword)
    {
        return [
            // Invalid email formats
            ['email' => '', 'password' => $basePassword],
            ['email' => 'invalid-email', 'password' => $basePassword],
            ['email' => '@example.com', 'password' => $basePassword],

            // SQL injection attempts
            ['email' => "' OR '1'='1", 'password' => $basePassword],
            ['email' => $baseEmail."' OR '1'='1", 'password' => $basePassword],

            // XSS attempts
            ['email' => '<script>alert("xss")</script>@example.com', 'password' => $basePassword],

            // Path traversal attempts
            ['email' => '../../../etc/passwd', 'password' => $basePassword],

            // Buffer overflow attempts
            ['email' => str_repeat('A', 1000).'@example.com', 'password' => $basePassword],

            // Special characters
            ['email' => $baseEmail.'!@#$%^&*()', 'password' => $basePassword],

            // Unicode attacks
            ['email' => $baseEmail.'💀', 'password' => $basePassword],
        ];
    }

    /**
     * Verify rate limiting keys are properly set
     */
    protected function assertRateLimitKeysExist($email, $ip)
    {
        $lowerEmail = strtolower($email);

        $key = 'auth_attempt:'.$lowerEmail.':'.$ip;
        $globalKey = 'auth_attempts_global:'.$lowerEmail;

        expect(Cache::has($key))->toBeTrue("Rate limit key should exist for IP: {$ip}");
        expect(Cache::has($globalKey))->toBeTrue('Global rate limit key should exist');

        return [
            'ip_key' => $key,
            'global_key' => $globalKey,
            'ip_attempts' => Cache::get($key),
            'global_attempts' => Cache::get($globalKey),
        ];
    }

    /**
     * Verify lockout is active
     */
    protected function assertAccountLocked($email)
    {
        $lockoutKey = 'auth_lockout:'.strtolower($email);
        expect(Cache::has($lockoutKey))->toBeTrue("Account should be locked for: {$email}");

        return Cache::get($lockoutKey);
    }

    /**
     * Test generic error message consistency
     */
    protected function assertGenericErrorResponse($response, $field = 'email')
    {
        $genericError = 'Estas credenciales no coinciden con nuestros registros';
        $response->assertSessionHasErrors([$field => $genericError]);
    }

    /**
     * Test JSON API responses for security
     */
    protected function assertSecurityJsonResponse($response, $expectedStatus, $expectedSuccess)
    {
        $response->assertStatus($expectedStatus);
        $response->assertJson([
            'success' => $expectedSuccess,
        ]);

        // Ensure no sensitive information is leaked
        $json = $response->json();
        expect($json)->not->toHaveKey('debug');
        expect($json)->not->toHaveKey('trace');
        expect($json)->not->toHaveKey('user_id');
        expect($json)->not->toHaveKey('password');
    }

    /**
     * Simulate concurrent login attempts
     */
    protected function simulateConcurrentAttempts($email, $passwords, $concurrency = 5)
    {
        $responses = [];

        for ($i = 0; $i < $concurrency; $i++) {
            $password = $passwords[$i % count($passwords)];
            $ip = '192.168.1.'.($i + 1);

            $result = $this->measureResponseTime(function () use ($email, $password, $ip) {
                return $this->withServerVariables(['REMOTE_ADDR' => $ip])
                    ->post(route('tenant.login'), [
                        'email' => $email,
                        'password' => $password,
                    ]);
            });

            $responses[] = $result;
        }

        return $responses;
    }

    /**
     * Clear all authentication cache and rate limits
     */
    protected function clearAllAuthData($email = null)
    {
        Cache::flush();
        RateLimiter::clear('2fa_attempt:*');
        Session::flush();

        if ($email) {
            // Clear specific email-related cache entries
            $lowerEmail = strtolower($email);
            Cache::forget('auth_attempts_global:'.$lowerEmail);
            Cache::forget('auth_lockout:'.$lowerEmail);
        }
    }

    /**
     * Verify session security properties
     */
    protected function assertSessionSecurity($sessionIdBefore, $sessionIdAfter)
    {
        expect($sessionIdAfter)->not->toBe($sessionIdBefore,
            'Session ID should change after authentication');

        expect(Session::get('tenant_authenticated'))->toBeTrue(
            'Tenant authenticated flag should be set');

        expect(Session::token())->not->toBeEmpty(
            'CSRF token should be generated');
    }

    /**
     * Test tenant isolation security
     */
    protected function assertTenantIsolation($user, $allowedTenant, $forbiddenTenant)
    {
        // Should work in allowed tenant
        tenancy()->initialize($allowedTenant);

        $response = $this->post(route('tenant.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user, 'tenant');
        $this->post(route('tenant.logout'));

        // Should fail in forbidden tenant
        tenancy()->initialize($forbiddenTenant);

        $response = $this->post(route('tenant.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertGuest('tenant');
        $this->assertGenericErrorResponse($response);
    }

    /**
     * Create security attack scenarios
     */
    protected function createAttackScenarios($targetEmail)
    {
        return [
            'brute_force' => [
                'description' => 'Multiple password attempts',
                'attempts' => array_fill(0, 6, 'wrong-password'),
            ],

            'ip_rotation' => [
                'description' => 'IP rotation bypass attempt',
                'ips' => ['1.1.1.1', '2.2.2.2', '3.3.3.3', '4.4.4.4', '5.5.5.5', '6.6.6.6'],
                'attempts' => array_fill(0, 6, 'wrong-password'),
            ],

            'timing_attack' => [
                'description' => 'Timing attack on response times',
                'tests' => [
                    ['email' => $targetEmail, 'password' => 'wrong'],
                    ['email' => 'nonexistent@example.com', 'password' => 'wrong'],
                    ['email' => $targetEmail, 'password' => 'password123'],
                ],
            ],

            'enumeration' => [
                'description' => 'User enumeration attempt',
                'emails' => [
                    $targetEmail,
                    'nonexistent@example.com',
                    'admin@'.config('app.url'),
                    'test@'.config('app.url'),
                ],
            ],
        ];
    }

    /**
     * Verify security logging and monitoring
     */
    protected function assertSecurityLogging($email, $expectedEvents = [])
    {
        $lowerEmail = strtolower($email);

        foreach ($expectedEvents as $event) {
            switch ($event) {
                case 'failed_attempts':
                    $key = 'auth_attempts_global:'.$lowerEmail;
                    expect(Cache::get($key))->toBeGreaterThan(0);
                    break;

                case 'account_locked':
                    $key = 'auth_lockout:'.$lowerEmail;
                    expect(Cache::has($key))->toBeTrue();
                    break;

                case 'successful_login':
                    $key = 'auth_success:'.$lowerEmail;
                    expect(Cache::has($key))->toBeTrue();

                    $data = Cache::get($key);
                    expect($data)->toHaveKeys(['user_id', 'ip', 'timestamp']);
                    break;
            }
        }
    }

    /**
     * Test performance impact of security measures
     */
    protected function assertSecurityPerformance($responses, $maxResponseTime = 1000)
    {
        $times = array_column($responses, 'time_ms');
        $averageTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);

        expect($averageTime)->toBeLessThan($maxResponseTime,
            "Average response time should be under {$maxResponseTime}ms");
        expect($maxTime)->toBeLessThan($maxResponseTime * 2,
            'Maximum response time should be reasonable');

        return [
            'average_ms' => round($averageTime, 2),
            'max_ms' => round($maxTime, 2),
            'min_ms' => round($minTime, 2),
            'total_attempts' => count($responses),
        ];
    }
}
