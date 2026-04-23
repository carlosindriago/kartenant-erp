<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubscriptionPlansQuickTest extends DuskTestCase
{
    public function test_subscription_plans_quick_analysis()
    {
        $admin = User::where('email', 'admin@emporiodigital.com')->first();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/subscription-plans')
                ->pause(3000)
                ->screenshot('01_initial_page_load');

            // Check what's actually visible in the table
            $browser->assertSee('Planes de Suscripción');

            // Count visible rows
            $visibleRows = $browser->elements('tbody tr');
            echo 'Visible rows: '.count($visibleRows)."\n";

            // Check if sort_order shows 0 for any visible rows
            for ($i = 1; $i <= min(count($visibleRows), 5); $i++) {
                try {
                    $firstCell = $browser->element("tbody tr:nth-child({$i}) td:first-child");
                    if ($firstCell) {
                        $text = $firstCell->getText();
                        echo "Row {$i} first cell: '{$text}'\n";

                        if ($text === '0') {
                            echo "ISSUE FOUND: sort_order shows 0 in row {$i}\n";
                            $browser->screenshot("02_sort_order_zero_issue_row_{$i}");
                        }
                    }
                } catch (\Exception $e) {
                    echo "Error reading row {$i}: ".$e->getMessage()."\n";
                }
            }

            // Try bulk selection
            try {
                $selectAllCheckbox = $browser->element('input[type="checkbox"]');
                if ($selectAllCheckbox) {
                    $browser->click($selectAllCheckbox)
                        ->pause(1000)
                        ->screenshot('03_bulk_selection_attempt');

                    // Check bulk actions dropdown
                    $bulkActionsButton = $browser->element('button[aria-label="Bulk actions"]');
                    if ($bulkActionsButton) {
                        $browser->click($bulkActionsButton)
                            ->pause(1000)
                            ->screenshot('04_bulk_actions_dropdown');

                        // Check if delete bulk action is visible/enabled
                        $deleteBulkAction = $browser->elements('button');
                        $deleteButtonFound = false;
                        foreach ($deleteBulkAction as $button) {
                            $buttonText = $button->getText();
                            if (stripos($buttonText, 'delete') !== false || stripos($buttonText, 'eliminar') !== false) {
                                $deleteButtonFound = true;
                                echo "Found delete button with text: '{$buttonText}'\n";

                                $isDisabled = $button->getAttribute('disabled') || $button->getAttribute('aria-disabled');
                                echo 'Delete button disabled: '.($isDisabled ? 'YES' : 'NO')."\n";

                                $browser->screenshot('05_delete_bulk_action_found');
                                break;
                            }
                        }

                        if (! $deleteButtonFound) {
                            echo "No delete bulk action found in dropdown\n";
                            $browser->screenshot('06_no_delete_bulk_action');
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Error during bulk actions test: '.$e->getMessage()."\n";
            }

            // Check pagination info
            try {
                $paginationElements = $browser->elements('.filament-tables-pagination, [data-pagination]');
                foreach ($paginationElements as $element) {
                    $paginationText = $element->getText();
                    echo "Pagination text: '{$paginationText}'\n";
                }
                $browser->screenshot('07_pagination_check');
            } catch (\Exception $e) {
                echo "No pagination found\n";
            }

            // Final screenshot
            $browser->screenshot('08_final_state');
        });
    }
}
