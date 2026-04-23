<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedTenantAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that archived tenant resource is accessible.
     */
    public function test_archived_tenant_routes_are_registered(): void
    {
        // Check that the routes exist in the route list
        $routes = app('router')->getRoutes();
        $archivedRoutes = [];

        foreach ($routes as $route) {
            if (strpos($route->uri(), 'archived-tenants') !== false) {
                $archivedRoutes[] = $route->uri();
            }
        }

        $this->assertNotEmpty($archivedRoutes, 'Archived tenant routes should be registered');
        $this->assertContains('admin/archived-tenants', $archivedRoutes);
    }

    /**
     * Test that ArchivedTenantResource class exists and can be instantiated.
     */
    public function test_archived_tenant_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Filament\Resources\ArchivedTenantResource::class));

        // Try to get the form schema without errors
        $resource = new \App\Filament\Resources\ArchivedTenantResource;
        $this->assertNotNull($resource);
    }
}
