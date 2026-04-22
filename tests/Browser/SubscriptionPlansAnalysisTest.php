<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;

class SubscriptionPlansAnalysisTest extends DuskTestCase
{
    /**
     * Test to analyze the subscription plans table and identify all reported issues
     */
    public function test_subscription_plans_comprehensive_analysis()
    {
        // Get admin user
        $admin = User::where('email', 'admin@emporiodigital.com')->first();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/subscription-plans')
                    ->pause(2000) // Wait for page to load
                    ->screenshot('01_subscription_plans_initial_load');

            // 1. Check page title and basic structure
            $browser->assertSee('Planes de Suscripción')
                    ->assertPresent('table')
                    ->screenshot('02_page_structure_verified');

            // 2. Analyze the table headers
            $headers = $browser->elements('th');
            $this->assertGreaterThan(0, count($headers), 'Table headers should be present');
            $browser->screenshot('03_table_headers_analysis');

            // 3. Count visible rows and check sort_order values
            $tableRows = $browser->elements('tbody tr');
            $visibleRows = count($tableRows);
            $browser->screenshot('04_visible_rows_count');

            // Log the number of visible rows
            echo "\n=== VISIBLE ROWS ANALYSIS ===\n";
            echo "Visible table rows: {$visibleRows}\n";

            // 4. Check each visible row for sort_order issues
            for ($i = 0; $i < min($visibleRows, 10); $i++) {
                $rowIndex = $i + 1;
                try {
                    $sortOrderCell = $browser->element("tbody tr:nth-child({$rowIndex}) td:first-child");
                    if ($sortOrderCell) {
                        $sortOrderText = $sortOrderCell->getText();
                        echo "Row {$rowIndex} sort_order: '{$sortOrderText}'\n";

                        // Check if sort_order shows 0 (the reported issue)
                        if ($sortOrderText === '0') {
                            $browser->screenshot("05_sort_order_issue_row_{$rowIndex}");
                        }
                    }
                } catch (\Exception $e) {
                    echo "Error reading row {$rowIndex}: " . $e->getMessage() . "\n";
                }
            }

            // 5. Check for pagination and total count
            try {
                $paginationInfo = $browser->element('.filament-tables-pagination');
                if ($paginationInfo) {
                    $paginationText = $paginationInfo->getText();
                    echo "Pagination info: '{$paginationText}'\n";
                    $browser->screenshot('06_pagination_info');
                }
            } catch (\Exception $e) {
                echo "No pagination found or error reading pagination: " . $e->getMessage() . "\n";
            }

            // 6. Test individual deletion attempt
            try {
                // Find first row that can be selected
                $firstRowCheckbox = $browser->element('input[type="checkbox"][name*="toggleAll"]');
                if ($firstRowCheckbox) {
                    // Try to select first row
                    $firstRow = $browser->element('input[type="checkbox"][dusk*="tableRecordCheckbox"]:not([dusk*="toggleAll"])');
                    if ($firstRow) {
                        $browser->click($firstRow)
                                ->pause(500)
                                ->screenshot('07_first_row_selected');

                        // Check if delete button appears for individual row
                        $deleteButtons = $browser->elements('button[dusk*="table-delete-action"]');
                        if (count($deleteButtons) > 0) {
                            echo "Found " . count($deleteButtons) . " delete buttons\n";
                            $browser->screenshot('08_delete_buttons_visible');
                        }
                    }
                }
            } catch (\Exception $e) {
                echo "Error testing individual deletion: " . $e->getMessage() . "\n";
            }

            // 7. Test bulk selection and deletion
            try {
                // Try to select all items
                $toggleAllCheckbox = $browser->element('input[type="checkbox"][dusk*="toggleAll"]');
                if ($toggleAllCheckbox) {
                    $browser->click($toggleAllCheckbox)
                            ->pause(1000)
                            ->screenshot('09_all_items_selected');

                    // Check bulk actions dropdown
                    $bulkActionsDropdown = $browser->element('button[dusk*="bulk-actions"]');
                    if ($bulkActionsDropdown) {
                        $browser->click($bulkActionsDropdown)
                                ->pause(500)
                                ->screenshot('10_bulk_actions_opened');

                        // Look for delete bulk action
                        $deleteBulkAction = $browser->element('button[dusk*="bulk-delete-action"]');
                        if ($deleteBulkAction) {
                            echo "Delete bulk action found\n";
                            $browser->screenshot('11_delete_bulk_action_visible');

                            // Check if it's disabled (which would explain the issue)
                            $isDisabled = $deleteBulkAction->getAttribute('disabled');
                            if ($isDisabled) {
                                echo "Delete bulk action is disabled - this explains the issue!\n";
                                $browser->screenshot('12_delete_bulk_action_disabled');
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                echo "Error testing bulk deletion: " . $e->getMessage() . "\n";
            }

            // 8. Check for filters and their states
            try {
                $filters = $browser->elements('.filament-tables-filter');
                echo "Found " . count($filters) . " filters\n";
                $browser->screenshot('13_filters_visible');
            } catch (\Exception $e) {
                echo "Error checking filters: " . $e->getMessage() . "\n";
            }

            // 9. Check for any error messages or notifications
            try {
                $notifications = $browser->elements('.filament-notifications');
                echo "Found " . count($notifications) . " notifications\n";
                if (count($notifications) > 0) {
                    $browser->screenshot('14_notifications_present');
                }
            } catch (\Exception $e) {
                echo "Error checking notifications: " . $e->getMessage() . "\n";
            }

            // 10. Final state screenshot
            $browser->screenshot('15_final_page_state');
        });
    }

    /**
     * Test JavaScript console errors
     */
    public function test_javascript_console_errors()
    {
        $admin = User::where('email', 'admin@emporiodigital.com')->first();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/subscription-plans')
                    ->pause(3000);

            // Capture console errors
            $logs = $browser->driver->manage()->getLog('browser');
            $errors = array_filter($logs, function($log) {
                return $log['level'] === 'SEVERE';
            });

            echo "\n=== JAVASCRIPT CONSOLE ERRORS ===\n";
            if (empty($errors)) {
                echo "No JavaScript errors found\n";
            } else {
                foreach ($errors as $error) {
                    echo "Error: " . $error['message'] . "\n";
                    echo "Source: " . $error['source'] . "\n";
                    echo "Line: " . $error['line'] . "\n";
                    echo "---\n";
                }
            }

            $browser->screenshot('16_console_analysis_complete');
        });
    }

    /**
     * Test network requests and API calls
     */
    public function test_network_requests_analysis()
    {
        $admin = User::where('email', 'admin@emporiodigital.com')->first();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/subscription-plans')
                    ->pause(2000);

            // Try to trigger some API calls by interacting with the page
            try {
                // Try sorting
                $sortHeader = $browser->element('th.sortable');
                if ($sortHeader) {
                    $browser->click($sortHeader)
                            ->pause(1000)
                            ->screenshot('17_sort_attempt');
                }

                // Try filtering
                $filterButton = $browser->element('button[dusk*="filter"]');
                if ($filterButton) {
                    $browser->click($filterButton)
                            ->pause(1000)
                            ->screenshot('18_filter_opened');
                }
            } catch (\Exception $e) {
                echo "Error during interaction testing: " . $e->getMessage() . "\n";
            }

            $browser->screenshot('19_network_analysis_complete');
        });
    }

    /**
     * Test specific deletion scenarios
     */
    public function test_deletion_scenarios()
    {
        $admin = User::where('email', 'admin@emporiodigital.com')->first();

        // Get a plan that should be deletable (no subscriptions, inactive, not visible, not featured)
        $deletablePlan = SubscriptionPlan::where('is_active', false)
                                       ->where('is_visible', false)
                                       ->where('is_featured', false)
                                       ->whereHas('subscriptions', function($query) {
                                           $query->whereRaw('0=1'); // No subscriptions
                                       }, '=', 0)
                                       ->first();

        $this->browse(function (Browser $browser) use ($admin, $deletablePlan) {
            $browser->loginAs($admin)
                    ->visit('/admin/subscription-plans')
                    ->pause(2000);

            if ($deletablePlan) {
                echo "\n=== TESTING DELETABLE PLAN ===\n";
                echo "Plan ID: {$deletablePlan->id}\n";
                echo "Plan Name: {$deletablePlan->name}\n";

                // Find this plan in the table
                $planRow = $browser->element("td:contains('{$deletablePlan->name}')")->getParent();
                if ($planRow) {
                    $browser->scrollIntoView($planRow)
                            ->screenshot('20_deletable_plan_found');

                    // Try to click the delete action for this specific plan
                    $deleteButton = $browser->element("tr:has(td:contains('{$deletablePlan->name}')) button[dusk*='delete-action']");
                    if ($deleteButton) {
                        $browser->click($deleteButton)
                                ->pause(1000)
                                ->screenshot('21_delete_dialog_opened');

                        // Check if we can confirm deletion
                        try {
                            $confirmButton = $browser->element('.filament-modal button:contains("Eliminar")');
                            if ($confirmButton) {
                                echo "Delete confirmation button found\n";
                                $browser->screenshot('22_delete_confirmation_ready');
                                // Don't actually click - just verify it exists
                            }
                        } catch (\Exception $e) {
                            echo "No delete confirmation button found: " . $e->getMessage() . "\n";
                        }
                    } else {
                        echo "No delete button found for deletable plan\n";
                        $browser->screenshot('23_no_delete_button_for_deletable');
                    }
                } else {
                    echo "Deletable plan not found in table\n";
                }
            } else {
                echo "\n=== NO DELETABLE PLAN FOUND ===\n";
                echo "All plans have restrictions preventing deletion\n";
            }

            $browser->screenshot('24_deletion_scenarios_complete');
        });
    }
}