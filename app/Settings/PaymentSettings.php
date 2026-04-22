<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    // Bank transfer details
    public ?string $bank_name;
    public ?string $bank_account_number;
    public ?string $bank_account_holder;
    public ?string $bank_routing_number;
    public ?string $bank_swift_code;
    public ?string $bank_iban;

    // Payment instructions
    public ?string $payment_instructions;
    public ?string $payment_note;

    // Business identification
    public ?string $business_name;
    public ?string $business_tax_id;
    public ?string $business_address;
    public ?string $business_phone;
    public ?string $business_email;

    // Currency and locale
    public string $default_currency;
    public string $locale;

    // Payment processing settings
    public bool $manual_approval_required;
    public int $approval_timeout_hours;
    public bool $auto_reminder_enabled;
    public int $reminder_interval_hours;

    // File upload settings
    public int $max_file_size_mb;
    public array $allowed_file_types;

    // Receipt and invoice settings
    public string $invoice_prefix;
    public string $receipt_prefix;
    public ?string $invoice_footer_text;
    public ?string $receipt_footer_text;

    // Legal and tax
    public float $tax_rate;
    public bool $tax_included;
    public ?string $legal_terms;
    public ?string $privacy_policy;

    public static function group(): string
    {
        return 'payment';
    }

    public static function repository(): ?string
    {
        return 'landlord';
    }

    /**
     * Get formatted bank details for display
     */
    public function getFormattedBankDetails(): string
    {
        $details = [];

        if ($this->bank_name) {
            $details[] = "Banco: {$this->bank_name}";
        }

        if ($this->bank_account_holder) {
            $details[] = "Titular: {$this->bank_account_holder}";
        }

        if ($this->bank_account_number) {
            $details[] = "Cuenta: {$this->bank_account_number}";
        }

        if ($this->bank_routing_number) {
            $details[] = "Routing: {$this->bank_routing_number}";
        }

        if ($this->bank_swift_code) {
            $details[] = "SWIFT: {$this->bank_swift_code}";
        }

        if ($this->bank_iban) {
            $details[] = "IBAN: {$this->bank_iban}";
        }

        return implode("\n", $details);
    }

    /**
     * Get formatted payment instructions
     */
    public function getFormattedInstructions(): string
    {
        $instructions = [];

        if ($this->payment_instructions) {
            $instructions[] = $this->payment_instructions;
        }

        if ($this->bank_name || $this->bank_account_number) {
            $instructions[] = $this->getFormattedBankDetails();
        }

        if ($this->payment_note) {
            $instructions[] = "Nota: {$this->payment_note}";
        }

        return implode("\n\n", $instructions);
    }

    /**
     * Check if bank transfer is configured
     */
    public function isBankTransferConfigured(): bool
    {
        return !empty($this->bank_name) &&
               !empty($this->bank_account_number) &&
               !empty($this->bank_account_holder);
    }

    /**
     * Get file size limit in bytes
     */
    public function getMaxFileSizeBytes(): int
    {
        return $this->max_file_size_mb * 1024 * 1024;
    }

    /**
     * Get allowed file extensions for validation
     */
    public function getAllowedFileExtensions(): string
    {
        return implode(',', array_map(fn($ext) => ".{$ext}", $this->allowed_file_types));
    }
}