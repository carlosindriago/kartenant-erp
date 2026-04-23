<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\TenantSubscription;
use App\Models\TrialIpTracking;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    private const DEFAULT_TRIAL_DAYS = 7;

    /**
     * Register a new tenant with all setup
     */
    public function registerTenant(array $data): array
    {
        $databaseName = 'tenant_'.Str::slug($data['domain'], '_');

        // 1. Create Database (Must be outside transaction for Postgres)
        try {
            // Check if database exists to avoid errors
            $exists = DB::connection('landlord')->select('SELECT 1 FROM pg_database WHERE datname = ?', [$databaseName]);
            if (empty($exists)) {
                DB::connection('landlord')->statement("CREATE DATABASE \"{$databaseName}\"");
            }
        } catch (\Exception $e) {
            Log::error('Failed to create tenant database: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al inicializar la base de datos. Por favor contacte soporte.',
            ];
        }

        DB::connection('landlord')->beginTransaction();

        try {
            // 2. Create Tenant Record
            $tenant = $this->createTenant($data, $databaseName);

            // 3. Create Admin User
            $admin = $this->createAdminUser($tenant, $data);

            // 4. Create Subscription
            $subscription = $this->createSubscription($tenant, $data);

            DB::connection('landlord')->commit();

            // 5. Run Migrations (Tenant Context)
            try {
                $tenant->makeCurrent();
                Artisan::call('tenants:artisan', [
                    'artisanCommand' => 'migrate --database=tenant --path=database/migrations/tenant --force',
                    '--tenant' => $tenant->id,
                ]);

                // Seed basic data
                Artisan::call('tenants:artisan', [
                    'artisanCommand' => 'db:seed --class=MovementReasonsSeeder --database=tenant --force',
                    '--tenant' => $tenant->id,
                ]);

                // Create default Tenant Settings (was in Observer)
                $tenant->execute(function () use ($tenant) {
                    TenantSetting::create([
                        'tenant_id' => $tenant->id, // Although in tenant DB id is usually 1, we keep consistency
                        'allow_cashier_void_last_sale' => true,
                        'cashier_void_time_limit_minutes' => 5,
                        'cashier_void_requires_same_day' => true,
                        'cashier_void_requires_own_sale' => true,
                    ]);
                });

            } catch (\Exception $e) {
                Log::error('Tenant migration failed', ['error' => $e->getMessage()]);
                // We don't rollback the tenant creation here, but we log it.
                // Ideally we might want to mark tenant as "broken" or "setup_failed"
            }

            // 6. Send Welcome Email (Queue)
            // Mail::to($admin->email)->queue(new WelcomeTenantMail($tenant, $admin));

            return [
                'success' => true,
                'tenant_id' => $tenant->id,
                'domain' => $tenant->domain,
                'redirect_to_checkout' => $subscription->payment_status !== 'paid' && $subscription->payment_status !== 'trial',
                'subscription_id' => $subscription->id,
                'message' => 'Cuenta creada exitosamente.',
            ];

        } catch (\Exception $e) {
            DB::connection('landlord')->rollBack();
            Log::error('Tenant registration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            // Optional: Drop database if it was just created?
            // For safety, we might leave it or try to drop it.

            return [
                'success' => false,
                'message' => 'Error al crear la cuenta. Por favor intenta nuevamente.',
            ];
        }
    }

    private function createTenant(array $data, string $databaseName): Tenant
    {
        // $databaseName is passed as argument

        // Use withoutEvents to avoid TenantObserver conflicts (DB creation, migrations inside transaction)
        return Tenant::withoutEvents(function () use ($data, $databaseName) {
            return Tenant::create([
                'name' => $data['company_name'],
                'domain' => $data['domain'],
                'database' => $databaseName,
                'status' => Tenant::STATUS_TRIAL, // Set status explicitly
                'cuit' => $data['cuit'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'timezone' => $data['timezone'] ?? 'America/Argentina/Buenos_Aires',
                'locale' => $data['locale'] ?? 'es',
                'currency' => $data['currency'] ?? 'USD',
                'email_verification_token' => Str::random(64),
                'email_verification_sent_at' => now(),
            ]);
        });
    }

    private function createAdminUser(Tenant $tenant, array $data): User
    {
        \Log::info('Creating admin user', ['email' => $data['email'], 'password_length' => strlen($data['password'])]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Let the model mutator handle hashing
            'must_change_password' => false,
            'is_active' => true,
        ]);

        // Associate user with tenant
        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function createSubscription(Tenant $tenant, array $data): TenantSubscription
    {
        $planType = $data['plan_type'] ?? 'trial';
        $trialAutoEnabled = SystemSetting::get('trial.auto_enable', true);

        if ($planType === 'trial' && $trialAutoEnabled) {
            // Get free plan
            $plan = SubscriptionPlan::where('slug', 'gratuito')->first();
            $trialDays = (int) SystemSetting::get('trial.duration_days', 7);

            return TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'payment_status' => 'paid', // Trial is considered paid
                'billing_cycle' => 'monthly',
                'price' => 0.00, // Free trial
                'currency' => 'USD',
                'trial_ends_at' => now()->addDays($trialDays),
                'starts_at' => now(),
                'ends_at' => now()->addDays($trialDays),
            ]);
        } else {
            // Paid plan - pending payment
            $plan = SubscriptionPlan::findOrFail($data['plan_id']);
            $cycle = $data['billing_cycle'] ?? 'monthly';

            return TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'inactive', // Will be activated after payment
                'payment_status' => 'pending',
                'billing_cycle' => $cycle,
                'starts_at' => now(),
                'ends_at' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]);
        }
    }

    private function trackTrialUsage(Tenant $tenant): void
    {
        TrialIpTracking::create([
            'ip_address' => request()->ip(),
            'tenant_id' => $tenant->id,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(self::DEFAULT_TRIAL_DAYS),
            'status' => 'active',
        ]);
    }

    private function sendVerificationEmail(Tenant $tenant, User $admin): void
    {
        // TODO: Implement email sending with verification token
        // For now, we'll skip this as it requires Mail configuration
    }
}
