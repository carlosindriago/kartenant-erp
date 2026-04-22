<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BillingSettings extends Settings
{
    // General billing settings
    public bool $auto_invoicing_enabled;
    public bool $automatic_suspension_enabled;
    public int $grace_period_days;
    public int $suspension_after_days;
    public int $deletion_after_days;

    // Reminder settings
    public bool $payment_reminders_enabled;
    public int $first_reminder_days_before;
    public int $second_reminder_days_before;
    public int $final_reminder_days_after;

    // Trial settings
    public bool $trial_extensions_enabled;
    public int $max_trial_extension_days;
    public bool $auto_trial_extension_enabled;
    public int $auto_trial_extension_days;

    // Currency and tax
    public string $default_currency;
    public float $tax_rate;
    public bool $tax_included;
    public string $tax_name;

    // Invoice settings
    public bool $auto_generate_invoices;
    public int $invoice_generation_days_before;
    public bool $email_invoices_enabled;
    public bool $email_receipts_enabled;

    // Notification settings
    public array $notification_emails;
    public bool $slack_notifications_enabled;
    public ?string $slack_webhook_url;

    // Subscription limits
    public int $max_active_subscriptions_per_tenant;
    public bool $allow_plan_changes;
    public bool $allow_multiple_subscriptions;

    // Payment processing
    public bool $manual_approval_required;
    public int $approval_timeout_hours;
    public string $default_payment_method;

    public static function group(): string
    {
        return 'billing';
    }

    public static function repository(): ?string
    {
        return 'landlord';
    }

    /**
     * Get grace period end date
     */
    public function getGracePeriodEndDate(\DateTime $dueDate): \DateTime
    {
        return (clone $dueDate)->modify("+{$this->grace_period_days} days");
    }

    /**
     * Get suspension date
     */
    public function getSuspensionDate(\DateTime $dueDate): \DateTime
    {
        return (clone $dueDate)->modify("+{$this->suspension_after_days} days");
    }

    /**
     * Get deletion date
     */
    public function getDeletionDate(\DateTime $dueDate): \DateTime
    {
        return (clone $dueDate)->modify("+{$this->deletion_after_days} days");
    }

    /**
     * Check if tenant should be suspended
     */
    public function shouldSuspendTenant(\DateTime $dueDate): bool
    {
        $now = new \DateTime();
        return $now > $this->getSuspensionDate($dueDate);
    }

    /**
     * Check if tenant should be deleted
     */
    public function shouldDeleteTenant(\DateTime $dueDate): bool
    {
        $now = new \DateTime();
        return $now > $this->getDeletionDate($dueDate);
    }

    /**
     * Get reminder dates
     */
    public function getReminderDates(\DateTime $dueDate): array
    {
        $reminders = [];

        if ($this->first_reminder_days_before > 0) {
            $reminders['first'] = (clone $dueDate)->modify("-{$this->first_reminder_days_before} days");
        }

        if ($this->second_reminder_days_before > 0) {
            $reminders['second'] = (clone $dueDate)->modify("-{$this->second_reminder_days_before} days");
        }

        if ($this->final_reminder_days_after > 0) {
            $reminders['final'] = (clone $dueDate)->modify("+{$this->final_reminder_days_after} days");
        }

        return $reminders;
    }

    /**
     * Get tax rate as percentage
     */
    public function getTaxRatePercentage(): string
    {
        return ($this->tax_rate * 100) . '%';
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax(float $amount): float
    {
        if ($this->tax_included) {
            return $amount - ($amount / (1 + $this->tax_rate));
        }

        return $amount * $this->tax_rate;
    }

    /**
     * Get total amount including tax
     */
    public function getTotalWithTax(float $amount): float
    {
        if ($this->tax_included) {
            return $amount;
        }

        return $amount + $this->calculateTax($amount);
    }
}