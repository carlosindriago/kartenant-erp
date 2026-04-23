<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArchivedTenantResourceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate as superadmin
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin, 'superadmin');
    }

    /**
     * Test that archived tenants resource page loads correctly.
     */
    public function test_archived_tenants_page_loads()
    {
        // Create a test tenant and archive it
        $tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete(); // Soft delete to archive

        // Access archived tenants page
        $response = $this->get('/admin/archived-tenants');

        $response->assertStatus(200);
    }

    /**
     * Test that archived tenants query works correctly.
     */
    public function test_archived_tenants_query()
    {
        // Create active tenant
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        // Create archived tenant
        $archivedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $archivedTenant->delete();

        // Test the query from ArchivedTenantResource
        $query = Tenant::onlyTrashed()
            ->where('status', Tenant::STATUS_ARCHIVED);

        $results = $query->get();

        // Should only return archived tenants
        $this->assertCount(1, $results);
        $this->assertEquals($archivedTenant->id, $results->first()->id);
        $this->assertEquals(Tenant::STATUS_ARCHIVED, $results->first()->status);
        $this->assertNotNull($results->first()->deleted_at);
    }

    /**
     * Test archived tenant health score calculation.
     */
    public function test_archived_tenant_health_score()
    {
        // Create archived tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test Archived Tenant',
            'domain' => 'test-archived.example.com',
            'contact_email' => 'test@example.com',
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        // Test health score is calculated
        $healthScore = $tenant->archived_health_score;
        $this->assertIsInt($healthScore);
        $this->assertGreaterThanOrEqual(0, $healthScore);
        $this->assertLessThanOrEqual(100, $healthScore);

        // Test health score tooltip
        $tooltip = $tenant->health_score_tooltip;
        $this->assertIsString($tooltip);
        $this->assertStringContains('Health Score:', $tooltip);
    }

    /**
     * Test health score colors for different ranges.
     */
    public function test_health_score_colors()
    {
        $tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        // Test different score ranges
        $testCases = [
            95 => 'success',  // Excellent
            80 => 'primary',  // Good
            70 => 'warning',  // Regular/Poor
            30 => 'danger',   // Critical
        ];

        foreach ($testCases as $score => $expectedColor) {
            // Mock the health score
            $tenant->setAttribute('archived_health_score', $score);
            $color = $tenant->health_score_color;
            $this->assertEquals($expectedColor, $color, "Score {$score} should have color {$expectedColor}");
        }
    }

    /**
     * Test that filters work correctly.
     */
    public function test_archived_tenant_filters()
    {
        // Create tenants with different archive dates
        $recentTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $recentTenant->deleted_at = now()->subDays(15);
        $recentTenant->save();

        $oldTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $oldTenant->deleted_at = now()->subDays(100);
        $oldTenant->save();

        // Test recent archived filter
        $recentQuery = Tenant::onlyTrashed()
            ->where('status', Tenant::STATUS_ARCHIVED)
            ->where('deleted_at', '>=', now()->subDays(30));

        $this->assertCount(1, $recentQuery->get());

        // Test long archived filter
        $longQuery = Tenant::onlyTrashed()
            ->where('status', Tenant::STATUS_ARCHIVED)
            ->where('deleted_at', '<=', now()->subDays(90));

        $this->assertCount(1, $longQuery->get());
    }

    /**
     * Test tenant relationships work for archived tenants.
     */
    public function test_archived_tenant_relationships()
    {
        $tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        // Test relationships exist
        $this->assertTrue(method_exists($tenant, 'backupLogs'));
        $this->assertTrue(method_exists($tenant, 'activities'));
        $this->assertTrue(method_exists($tenant, 'users'));
    }

    /**
     * Test archived tenant status and labels.
     */
    public function test_archived_tenant_status_labels()
    {
        $tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        // Test status label
        $this->assertEquals('Archivado 🗄️', $tenant->status_label);
        $this->assertEquals('gray', $tenant->status_color);

        // Test archived health score category
        $category = $tenant->health_score_category;
        $this->assertContains($category, ['Excelente', 'Bueno', 'Regular', 'Pobre', 'Crítico']);
    }
}
