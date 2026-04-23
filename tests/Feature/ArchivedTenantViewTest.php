<?php

use App\Models\Tenant;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Simple test case class using standard TestCase instead of DuskTestCase
| This resolves the "Test case can not be used" error with Pest
*/

test('archived tenant view loads correctly', function () {
    // Create an archived tenant for testing
    $tenant = Tenant::factory()->create([
        'name' => 'Test Archived Tenant',
        'domain' => 'test-archived',
        'database' => 'test_archived_db',
        'status' => Tenant::STATUS_ARCHIVED,
        'deleted_at' => now()->subDays(30),
    ]);

    // Test archived tenant URL directly
    $response = $this->get("http://emporiodigital.test/admin/archived-tenants/{$tenant->domain}");

    // Assert page loads successfully (should not be 404)
    $response->assertStatus(200);

    // Assert we can see tenant name in response
    $response->assertSee('Test Archived Tenant');

    // Clean up
    $tenant->forceDelete();
});
