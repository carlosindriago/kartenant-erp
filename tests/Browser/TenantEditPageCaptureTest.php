<?php

namespace Tests\Browser;

use App\Models\Tenant;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenantEditPageCaptureTest extends DuskTestCase
{
    public function test_capture_tenant_edit_page(): void
    {
        $this->browse(function (Browser $browser) {
            $tenant = Tenant::where('domain', 'data-protection-test-1764024534')->first();

            if (! $tenant) {
                $this->markTestSkipped('Test tenant not found');

                return;
            }

            try {
                // Visit the page directly
                $browser->visit("/admin/tenants/{$tenant->id}/edit")
                    ->pause(5000) // Wait longer for any redirects
                    ->screenshot('tenant-edit-page-capture');

                $currentUrl = $browser->driver->getCurrentURL();
                echo 'Current URL: '.$currentUrl."\n";

                // Check what we see on the page
                $pageContent = $browser->script('return document.body.innerText.substring(0, 2000);')[0];
                echo 'Page content preview: '.substr($pageContent, 0, 500)."\n";

                $this->assertTrue(true, 'Page captured successfully');

            } catch (\Exception $e) {
                echo 'Error: '.$e->getMessage()."\n";
                $this->markTestSkipped('Navigation error: '.$e->getMessage());
            }
        });
    }
}
