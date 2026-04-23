<?php

namespace Tests\Feature;

use App\Models\Landlord\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a super admin user
        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'email' => 'admin@test.com',
        ]);
    }

    /** @test */
    public function it_can_see_archived_tenants_navigation_item()
    {
        $response = $this
            ->actingAs($this->superAdmin, 'superadmin')
            ->get('/admin');

        $response->assertStatus(200);
        // The navigation should contain "Tenants Archivados"
        $response->assertSee('Tenants Archivados');
    }

    /** @test */
    public function archived_tenant_resource_is_accessible()
    {
        // Create a test tenant and archive it
        $tenant = Tenant::factory()->create([
            'name' => 'Test Archived Tenant',
            'domain' => 'test-archived',
            'database' => 'test_archived_db',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        // Archive the tenant
        $tenant->status = Tenant::STATUS_ARCHIVED;
        $tenant->delete();

        $response = $this
            ->actingAs($this->superAdmin, 'superadmin')
            ->get('/admin/archived-tenants');

        $response->assertStatus(200);
        $response->assertSee('Test Archived Tenant');
    }

    /** @test */
    public function active_tenants_resource_excludes_archived_tenants()
    {
        // Create active and archived tenants
        $activeTenant = Tenant::factory()->create([
            'name' => 'Active Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $archivedTenant = Tenant::factory()->create([
            'name' => 'Archived Tenant',
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $archivedTenant->delete();

        // Check that active tenants list shows only active tenants
        $response = $this
            ->actingAs($this->superAdmin, 'superadmin')
            ->get('/admin/tenants');

        $response->assertStatus(200);
        $response->assertSee('Active Tenant');
        $response->assertDontSee('Archived Tenant');
    }

    /** @test */
    public function archived_tenant_can_be_restored()
    {
        // Create and archive a tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test Restore Tenant',
            'domain' => 'test-restore',
            'database' => 'test_restore_db',
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        // Mock the database validation to avoid connection issues in test
        $tenant->shouldReceive('validateTenantDatabase')->andReturn(true);

        $restoreData = [
            'restore_reason' => 'Test restoration',
            'admin_password' => 'password',
            'confirm_tenant_name' => 'Test Restore Tenant',
            'backup_before_restore' => false,
            'understand_consequences' => true,
        ];

        $response = $this
            ->actingAs($this->superAdmin, 'superadmin')
            ->post("/admin/archived-tenants/{$tenant->id}/restore", $restoreData);

        // Should redirect and show success
        $response->assertRedirect();

        // Verify tenant is restored
        $restoredTenant = Tenant::withTrashed()->find($tenant->id);
        $this->assertFalse($restoredTenant->trashed());
        $this->assertEquals(Tenant::STATUS_ACTIVE, $restoredTenant->status);
    }

    /** @test */
    public function archive_info_attribute_returns_correct_data()
    {
        // Create and archive a tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test Archive Info',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $tenant->status = Tenant::STATUS_ARCHIVED;
        $tenant->delete();

        // Create archive record
        $archiveData = [
            'reason' => 'Test archive',
            'backup_path' => '/path/to/backup.sql',
            'backup_size' => 1024 * 1024 * 10, // 10MB
        ];
        $tenant->createArchiveRecord($archiveData);

        $archiveInfo = $tenant->archive_info;

        $this->assertIsArray($archiveInfo);
        $this->assertEquals('Test archive', $archiveInfo['archive_reason']);
        $this->assertEquals('/path/to/backup.sql', $archiveInfo['backup_path']);
        $this->assertEquals(1024 * 1024 * 10, $archiveInfo['backup_size']);
    }

    /** @test */
    public function can_be_restored_validation_works_correctly()
    {
        // Create an archived tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test Validation',
            'domain' => 'test-validation',
            'database' => 'test_validation_db',
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        // Mock database validation
        $tenant->shouldReceive('validateTenantDatabase')->andReturn(true);

        $checks = $tenant->canBeRestored();

        $this->assertIsArray($checks);
        $this->assertTrue($checks['is_archived']);
        $this->assertTrue($checks['database_accessible']);
        $this->assertArrayHasKey('conflicts_detected', $checks);
    }

    /** @test */
    public function archived_stats_returns_correct_information()
    {
        // Create and archive a tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test Stats',
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
        $tenant->delete();

        $stats = $tenant->getArchivedStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('days_archived', $stats);
        $this->assertArrayHasKey('archive_date', $stats);
        $this->assertArrayHasKey('original_status', $stats);
        $this->assertArrayHasKey('backup_count', $stats);
        $this->assertArrayHasKey('data_size_mb', $stats);
        $this->assertArrayHasKey('user_count', $stats);
        $this->assertArrayHasKey('has_conflicts', $stats);
    }

    /** @test */
    public function archive_history_returns_activities()
    {
        // Create and archive a tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test History',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $tenant->status = Tenant::STATUS_ARCHIVED;
        $tenant->delete();

        $history = $tenant->getArchiveHistory();

        $this->assertIsArray($history);
        // Should return empty array if no activities exist yet
        $this->assertIsArray($history);
    }

    /** @test */
    public function non_super_admin_cannot_access_archived_tenants()
    {
        // Create a regular admin user (not super admin)
        $regularAdmin = User::factory()->create([
            'is_super_admin' => false,
            'email' => 'admin-regular@test.com',
        ]);

        $response = $this
            ->actingAs($regularAdmin, 'superadmin')
            ->get('/admin/archived-tenants');

        // Should be forbidden or redirected
        $response->assertStatus(403);
    }

    /** @test */
    public function tenant_soft_delete_sets_correct_status()
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Soft Delete',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        // Use the softDeleteTenant method
        $result = $tenant->softDeleteTenant();

        $this->assertTrue($result);

        // Verify tenant is soft-deleted with archived status
        $archivedTenant = Tenant::withTrashed()->find($tenant->id);
        $this->assertTrue($archivedTenant->trashed());
        $this->assertEquals(Tenant::STATUS_ARCHIVED, $archivedTenant->status);
    }

    /** @test */
    public function archive_scopes_work_correctly()
    {
        // Create tenants with different statuses
        $activeTenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $archivedTenant = Tenant::factory()->create(['status' => Tenant::STATUS_ARCHIVED]);
        $archivedTenant->delete();
        $inactiveTenant = Tenant::factory()->create(['status' => Tenant::STATUS_INACTIVE]);

        // Test scopes
        $activeCount = Tenant::active()->count();
        $archivedCount = Tenant::archived()->count();
        $inactiveCount = Tenant::inactive()->count();

        $this->assertEquals(1, $activeCount);
        $this->assertEquals(0, $archivedCount); // Archived tenants are soft-deleted, so scope won't find them
        $this->assertEquals(1, $inactiveCount);

        // Test withTrashed for archived tenants
        $archivedWithTrashedCount = Tenant::archived()->withTrashed()->count();
        $this->assertEquals(1, $archivedWithTrashedCount);
    }
}
