<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\PaymentSettings;
use App\Models\User;
use App\Models\Module;
use App\Models\TenantModule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BillingService
{
    public function __construct(
        private PaymentSettings $paymentSettings
    ) {}

    /**
     * Generate monthly invoices for all active subscriptions
     */
    public function generateMonthlyInvoices(Carbon $billingDate = null): array
    {
        $billingDate = $billingDate ?? now();
        $results = [
            'subscription_invoices' => 0,
            'module_invoices' => 0,
            'errors' => 0,
            'skipped' => 0,
            'total_amount' => 0,
        ];

        // Generate subscription invoices
        TenantSubscription::active()
            ->where('billing_cycle', 'monthly')
            ->whereDay('next_billing_at', $billingDate->day)
            ->whereMonth('next_billing_at', $billingDate->month)
            ->whereYear('next_billing_at', $billingDate->year)
            ->chunk(50, function ($subscriptions) use (&$results, $billingDate) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $invoice = $this->generateSubscriptionInvoice($subscription, $billingDate);
                        if ($invoice) {
                            $results['subscription_invoices']++;
                            $results['total_amount'] += (float) $invoice->total_amount;
                        } else {
                            $results['skipped']++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error generating monthly subscription invoice', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                        $results['errors']++;
                    }
                }
            });

        // Generate module invoices
        $this->generateModuleInvoices($billingDate, $results);

        $results['generated'] = $results['subscription_invoices'] + $results['module_invoices'];

        Log::info('Monthly invoices generated', [
            'date' => $billingDate->toDateString(),
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Generate yearly invoices for all active subscriptions
     */
    public function generateYearlyInvoices(Carbon $billingDate = null): array
    {
        $billingDate = $billingDate ?? now();
        $results = [
            'subscription_invoices' => 0,
            'module_invoices' => 0,
            'errors' => 0,
            'skipped' => 0,
            'total_amount' => 0,
        ];

        // Generate subscription invoices
        TenantSubscription::active()
            ->where('billing_cycle', 'yearly')
            ->whereDay('next_billing_at', $billingDate->day)
            ->whereMonth('next_billing_at', $billingDate->month)
            ->whereYear('next_billing_at', $billingDate->year)
            ->chunk(50, function ($subscriptions) use (&$results, $billingDate) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $invoice = $this->generateSubscriptionInvoice($subscription, $billingDate);
                        if ($invoice) {
                            $results['subscription_invoices']++;
                            $results['total_amount'] += (float) $invoice->total_amount;
                        } else {
                            $results['skipped']++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error generating yearly subscription invoice', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                        $results['errors']++;
                    }
                }
            });

        // Generate module invoices
        $this->generateModuleInvoices($billingDate, $results);

        $results['generated'] = $results['subscription_invoices'] + $results['module_invoices'];

        Log::info('Yearly invoices generated', [
            'date' => $billingDate->toDateString(),
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Generate module invoices (helper method)
     */
    private function generateModuleInvoices(Carbon $billingDate, array &$results): void
    {
        // Get tenants with monthly modules that need billing
        $tenantsWithMonthlyModules = TenantModule::active()
            ->where('billing_cycle', 'monthly')
            ->whereNull('expires_at')
            ->where(function ($query) use ($billingDate) {
                $query->whereNull('next_billing_at')
                      ->orWhereDate('next_billing_at', '<=', $billingDate);
            })
            ->distinct('tenant_id')
            ->pluck('tenant_id');

        foreach ($tenantsWithMonthlyModules as $tenantId) {
            try {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) {
                    continue;
                }

                $moduleInvoice = $this->generateModulesInvoice($tenant, $billingDate);
                if ($moduleInvoice) {
                    $results['module_invoices']++;
                    $results['total_amount'] += (float) $moduleInvoice->total_amount;
                }
            } catch (\Exception $e) {
                Log::error('Error generating monthly module invoice', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
                $results['errors']++;
            }
        }
    }

    
    /**
     * Generate invoice for a specific subscription
     */
    public function generateSubscriptionInvoice(
        TenantSubscription $subscription,
        Carbon $billingDate = null
    ): ?Invoice {
        return DB::connection('landlord')->transaction(function () use ($subscription, $billingDate) {
            $billingDate = $billingDate ?? now();
            $tenant = $subscription->tenant;
            $plan = $subscription->plan;

            // Check if invoice already exists for this period
            $existingInvoice = Invoice::where('tenant_id', $tenant->id)
                ->where('subscription_id', $subscription->id)
                ->where('billing_period_start', $subscription->next_billing_at)
                ->first();

            if ($existingInvoice) {
                Log::info('Invoice already exists', [
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $existingInvoice->id,
                ]);
                return null;
            }

            // Calculate billing period
            $periodStart = $subscription->next_billing_at;
            $periodEnd = $subscription->billing_cycle === 'yearly'
                ? $periodStart->copy()->addYear()
                : $periodStart->copy()->addMonth();

            // Calculate amounts
            $subtotal = $subscription->price;
            $taxAmount = $this->calculateTax($subtotal);
            $totalAmount = $subtotal + $taxAmount;

            // Create invoice
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'status' => Invoice::STATUS_DRAFT,
                'type' => 'subscription',
                'billing_period_start' => $periodStart,
                'billing_period_end' => $periodEnd,
                'due_date' => $billingDate->addDays(30),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $subscription->currency,
                'plan_name' => $plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'plan_price' => $subscription->price,
                'customer_data' => [
                    'name' => $tenant->name,
                    'email' => $tenant->owner_email,
                    'phone' => $tenant->phone ?? null,
                    'address' => $tenant->address ?? null,
                ],
                'line_items' => [
                    [
                        'description' => $plan->name . ' - ' . ucfirst($subscription->billing_cycle),
                        'quantity' => 1,
                        'unit_price' => (float) $subscription->price,
                        'total' => (float) $subscription->price,
                    ],
                ],
            ]);

            // Update subscription next billing date
            $subscription->update([
                'next_billing_at' => $periodEnd,
            ]);

            Log::info('Subscription invoice generated', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'amount' => $totalAmount,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate setup fee invoice
     */
    public function generateSetupFeeInvoice(
        Tenant $tenant,
        SubscriptionPlan $plan,
        float $setupFee
    ): Invoice {
        return DB::connection('landlord')->transaction(function () use ($tenant, $plan, $setupFee) {
            $taxAmount = $this->calculateTax($setupFee);
            $totalAmount = $setupFee + $taxAmount;

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => Invoice::generateInvoiceNumber('SETUP'),
                'status' => Invoice::STATUS_DRAFT,
                'type' => 'setup_fee',
                'billing_period_start' => now(),
                'billing_period_end' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $setupFee,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $plan->currency,
                'plan_name' => $plan->name,
                'billing_cycle' => 'once',
                'plan_price' => $setupFee,
                'customer_data' => [
                    'name' => $tenant->name,
                    'email' => $tenant->owner_email,
                    'phone' => $tenant->phone ?? null,
                    'address' => $tenant->address ?? null,
                ],
                'line_items' => [
                    [
                        'description' => 'Cuota de configuración - ' . $plan->name,
                        'quantity' => 1,
                        'unit_price' => $setupFee,
                        'total' => $setupFee,
                    ],
                ],
            ]);

            Log::info('Setup fee invoice generated', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'amount' => $totalAmount,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate modules invoice for a tenant
     */
    public function generateModulesInvoice(
        Tenant $tenant,
        Carbon $billingDate = null
    ): ?Invoice {
        return DB::connection('landlord')->transaction(function () use ($tenant, $billingDate) {
            $billingDate = $billingDate ?? now();

            // Get active modules that need billing
            $modulesToBill = $tenant->activeTenantModules()
                ->where(function ($query) use ($billingDate) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', $billingDate);
                })
                ->where(function ($query) use ($billingDate) {
                    $query->whereNull('next_billing_at')
                          ->orWhereDate('next_billing_at', '<=', $billingDate);
                })
                ->get();

            if ($modulesToBill->isEmpty()) {
                return null;
            }

            $lineItems = [];
            $subtotal = 0;

            foreach ($modulesToBill as $tenantModule) {
                $price = $tenantModule->getPrice();
                $billingCycle = $tenantModule->billing_cycle;

                $lineItems[] = [
                    'description' => $tenantModule->module->name . ' - ' . $tenantModule->getDisplayBillingCycle(),
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total' => $price,
                    'metadata' => [
                        'module_id' => $tenantModule->module_id,
                        'tenant_module_id' => $tenantModule->id,
                        'billing_cycle' => $billingCycle,
                    ],
                ];

                $subtotal += $price;

                // Update next billing date
                $nextBillingDate = match($billingCycle) {
                    'monthly' => $billingDate->copy()->addMonth(),
                    'yearly' => $billingDate->copy()->addYear(),
                    'once' => null, // One-time payment, no next billing
                    default => $billingDate->copy()->addMonth(),
                };

                $tenantModule->update([
                    'next_billing_at' => $nextBillingDate,
                ]);
            }

            $taxAmount = $this->calculateTax($subtotal);
            $totalAmount = $subtotal + $taxAmount;

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => Invoice::generateInvoiceNumber('MODULE'),
                'status' => Invoice::STATUS_DRAFT,
                'type' => 'modules',
                'billing_period_start' => $billingDate,
                'billing_period_end' => $billingDate->copy()->addMonth(),
                'due_date' => $billingDate->addDays(30),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $modulesToBill->first()->getCurrency(),
                'plan_name' => 'Módulos Adicionales',
                'billing_cycle' => 'monthly',
                'plan_price' => $subtotal,
                'customer_data' => [
                    'name' => $tenant->name,
                    'email' => $tenant->owner_email,
                    'phone' => $tenant->phone ?? null,
                    'address' => $tenant->address ?? null,
                ],
                'line_items' => $lineItems,
                'metadata' => [
                    'type' => 'modules_billing',
                    'modules_count' => $modulesToBill->count(),
                ],
            ]);

            Log::info('Modules invoice generated', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'modules_count' => $modulesToBill->count(),
                'amount' => $totalAmount,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate module setup fee invoice
     */
    public function generateModuleSetupFeeInvoice(
        Tenant $tenant,
        Module $module,
        ?TenantModule $tenantModule = null
    ): Invoice {
        return DB::connection('landlord')->transaction(function () use ($tenant, $module, $tenantModule) {
            if (!$module->hasSetupFee()) {
                throw new \InvalidArgumentException('El módulo no tiene cuota de configuración');
            }

            $setupFee = (float) $module->setup_fee;
            $taxAmount = $this->calculateTax($setupFee);
            $totalAmount = $setupFee + $taxAmount;

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => Invoice::generateInvoiceNumber('MODULE_SETUP'),
                'status' => Invoice::STATUS_DRAFT,
                'type' => 'module_setup_fee',
                'billing_period_start' => now(),
                'billing_period_end' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $setupFee,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $module->currency,
                'plan_name' => $module->name,
                'billing_cycle' => 'once',
                'plan_price' => $setupFee,
                'customer_data' => [
                    'name' => $tenant->name,
                    'email' => $tenant->owner_email,
                    'phone' => $tenant->phone ?? null,
                    'address' => $tenant->address ?? null,
                ],
                'line_items' => [
                    [
                        'description' => 'Cuota de configuración - Módulo: ' . $module->name,
                        'quantity' => 1,
                        'unit_price' => $setupFee,
                        'total' => $setupFee,
                    ],
                ],
                'metadata' => [
                    'type' => 'module_setup_fee',
                    'module_id' => $module->id,
                    'tenant_module_id' => $tenantModule?->id,
                ],
            ]);

            // Update tenant module with invoice reference
            if ($tenantModule) {
                $tenantModule->update(['invoice_line_item_id' => $invoice->id]);
            }

            Log::info('Module setup fee invoice generated', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'invoice_id' => $invoice->id,
                'amount' => $totalAmount,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate penalty invoice for late payment
     */
    public function generatePenaltyInvoice(
        Invoice $overdueInvoice,
        float $penaltyAmount
    ): Invoice {
        return DB::connection('landlord')->transaction(function () use ($overdueInvoice, $penaltyAmount) {
            $taxAmount = $this->calculateTax($penaltyAmount);
            $totalAmount = $penaltyAmount + $taxAmount;

            $invoice = Invoice::create([
                'tenant_id' => $overdueInvoice->tenant_id,
                'subscription_id' => $overdueInvoice->subscription_id,
                'invoice_number' => Invoice::generateInvoiceNumber('PENALTY'),
                'status' => Invoice::STATUS_DRAFT,
                'type' => 'penalty',
                'billing_period_start' => now(),
                'billing_period_end' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $penaltyAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $overdueInvoice->currency,
                'plan_name' => $overdueInvoice->plan_name,
                'billing_cycle' => 'once',
                'plan_price' => $penaltyAmount,
                'customer_data' => $overdueInvoice->customer_data,
                'line_items' => [
                    [
                        'description' => 'Cargo por mora - Factura ' . $overdueInvoice->invoice_number,
                        'quantity' => 1,
                        'unit_price' => $penaltyAmount,
                        'total' => $penaltyAmount,
                    ],
                ],
                'metadata' => [
                    'related_invoice_id' => $overdueInvoice->id,
                    'overdue_days' => $overdueInvoice->daysUntilDue() * -1,
                ],
            ]);

            Log::info('Penalty invoice generated', [
                'overdue_invoice_id' => $overdueInvoice->id,
                'penalty_invoice_id' => $invoice->id,
                'amount' => $totalAmount,
            ]);

            return $invoice;
        });
    }

    /**
     * Send invoice via email
     */
    public function sendInvoiceEmail(Invoice $invoice): bool
    {
        try {
            $tenant = $invoice->tenant;

            if (!$tenant->owner_email) {
                Log::warning('No email address for tenant', [
                    'tenant_id' => $tenant->id,
                    'invoice_id' => $invoice->id,
                ]);
                return false;
            }

            // Generate PDF
            $pdfPath = $this->generateInvoicePDF($invoice);

            // Send email
            Mail::to($tenant->owner_email)
                ->send(new \App\Mail\InvoiceEmail($invoice, $pdfPath));

            // Mark as sent
            $invoice->update([
                'is_sent' => true,
                'sent_at' => now(),
                'sent_via' => 'email',
            ]);

            Log::info('Invoice sent via email', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'email' => $tenant->owner_email,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error sending invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate PDF for invoice
     */
    public function generateInvoicePDF(Invoice $invoice): string
    {
        $pdf = \PDF::loadView('pdf.invoices.invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'settings' => $this->paymentSettings,
        ]);

        $filename = "invoices/{$invoice->invoice_number}.pdf";
        $pdf->storeAs($filename, 'public');

        return $filename;
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax(float $amount): float
    {
        $taxRate = $this->paymentSettings->tax_rate ?? 0;

        if ($this->paymentSettings->tax_included ?? false) {
            return 0; // Tax is already included in amount
        }

        return $amount * ($taxRate / 100);
    }

    /**
     * Get total amount including tax
     */
    public function getTotalWithTax(float $amount): float
    {
        return $amount + $this->calculateTax($amount);
    }

    /**
     * Process overdue invoices
     */
    public function processOverdueInvoices(): array
    {
        $results = [
            'processed' => 0,
            'penalties' => 0,
            'reminders' => 0,
            'suspensions' => 0,
        ];

        Invoice::where('due_date', '<', now())
            ->where('status', Invoice::STATUS_SENT)
            ->chunk(50, function ($invoices) use (&$results) {
                foreach ($invoices as $invoice) {
                    $this->processOverdueInvoice($invoice);
                    $results['processed']++;

                    $overdueDays = abs($invoice->daysUntilDue());

                    if ($overdueDays >= 30) {
                        $this->applyPenalty($invoice);
                        $results['penalties']++;
                    }

                    if ($overdueDays >= 60) {
                        $this->suspendTenant($invoice);
                        $results['suspensions']++;
                    } else {
                        $this->sendOverdueReminder($invoice);
                        $results['reminders']++;
                    }
                }
            });

        Log::info('Overdue invoices processed', $results);

        return $results;
    }

    /**
     * Process individual overdue invoice
     */
    private function processOverdueInvoice(Invoice $invoice): void
    {
        // Update status to overdue
        $invoice->update(['status' => Invoice::STATUS_OVERDUE]);

        Log::info('Invoice marked as overdue', [
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'overdue_days' => abs($invoice->daysUntilDue()),
        ]);
    }

    /**
     * Apply penalty to overdue invoice
     */
    private function applyPenalty(Invoice $invoice): void
    {
        // Check if penalty already applied
        $penaltyExists = Invoice::where('tenant_id', $invoice->tenant_id)
            ->where('type', 'penalty')
            ->whereJsonContains('metadata->related_invoice_id', $invoice->id)
            ->exists();

        if (!$penaltyExists) {
            $penaltyAmount = $invoice->total_amount * 0.10; // 10% penalty
            $this->generatePenaltyInvoice($invoice, $penaltyAmount);
        }
    }

    /**
     * Send overdue reminder
     */
    private function sendOverdueReminder(Invoice $invoice): void
    {
        try {
            $tenant = $invoice->tenant;

            if ($tenant->owner_email) {
                Mail::to($tenant->owner_email)
                    ->send(new \App\Mail\OverdueInvoiceReminder($invoice));
            }
        } catch (\Exception $e) {
            Log::error('Error sending overdue reminder', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Suspend tenant for non-payment
     */
    private function suspendTenant(Invoice $invoice): void
    {
        $tenant = $invoice->tenant;

        // Update tenant status
        $tenant->update(['status' => 'suspended']);

        // Update subscription status
        if ($invoice->subscription) {
            $invoice->subscription->update(['status' => 'suspended']);
        }

        Log::warning('Tenant suspended for non-payment', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Get billing statistics
     */
    public function getBillingStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Invoice::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalInvoices = $query->count();
        $paidInvoices = (clone $query)->where('status', Invoice::STATUS_PAID)->count();
        $overdueInvoices = (clone $query)->where('status', Invoice::STATUS_OVERDUE)->count();
        $totalRevenue = (clone $query)->where('status', Invoice::STATUS_PAID)->sum('total_amount');
        $outstandingAmount = (clone $query)->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->sum('total_amount');

        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'payment_rate' => $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0,
            'total_revenue' => (float) $totalRevenue,
            'outstanding_amount' => (float) $outstandingAmount,
            'average_invoice_amount' => $totalInvoices > 0 ? (float) ($totalRevenue / $paidInvoices) : 0,
        ];
    }

    /**
     * Get tenant billing summary
     */
    public function getTenantBillingSummary(Tenant $tenant): array
    {
        $invoices = Invoice::where('tenant_id', $tenant->id)->get();

        $totalInvoices = $invoices->count();
        $paidInvoices = $invoices->where('status', Invoice::STATUS_PAID)->count();
        $overdueInvoices = $invoices->where('status', Invoice::STATUS_OVERDUE)->count();
        $totalPaid = $invoices->where('status', Invoice::STATUS_PAID)->sum('total_amount');
        $outstanding = $invoices->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->sum('total_amount');

        // Module-specific billing information
        $activeModules = $tenant->activeTenantModules();
        $modulesMonthlyCost = $activeModules->sum(fn($tm) => $tm->getMonthlyCost());
        $modulesYearlyCost = $activeModules->sum(fn($tm) => $tm->getYearlyCost());
        $modulesCount = $activeModules->count();

        $subscriptionBillingDate = $tenant->subscriptions()->active()->value('next_billing_at');
        $moduleBillingDate = $activeModules
            ->whereNotNull('next_billing_at')
            ->min('next_billing_at');

        $nextBillingDate = $subscriptionBillingDate && $moduleBillingDate
            ? min($subscriptionBillingDate, $moduleBillingDate)
            : ($subscriptionBillingDate ?: $moduleBillingDate);

        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'payment_rate' => $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0,
            'total_paid' => (float) $totalPaid,
            'outstanding' => (float) $outstanding,
            'next_billing_date' => $nextBillingDate,

            // Module-specific information
            'modules' => [
                'active_count' => $modulesCount,
                'monthly_cost' => (float) $modulesMonthlyCost,
                'yearly_cost' => (float) $modulesYearlyCost,
                'expiring_soon' => $tenant->getModulesExpiringSoon(7)->count(),
                'over_limits' => count($tenant->checkModuleLimits()),
            ],

            // Billing breakdown by type
            'billing_breakdown' => [
                'subscriptions' => $invoices->where('type', 'subscription')->count(),
                'modules' => $invoices->where('type', 'modules')->count(),
                'setup_fees' => $invoices->where('type', 'setup_fee')->count(),
                'module_setup_fees' => $invoices->where('type', 'module_setup_fee')->count(),
                'penalties' => $invoices->where('type', 'penalty')->count(),
            ],
        ];
    }
}