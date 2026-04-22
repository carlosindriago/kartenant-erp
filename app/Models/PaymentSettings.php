<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentSettings extends Model
{
    protected $connection = 'landlord';
    protected $table = 'payment_settings';

    protected $fillable = [
        // Bank transfer details
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'bank_routing_number',
        'bank_swift_code',
        'bank_iban',

        // Payment instructions
        'payment_instructions',
        'payment_note',

        // Business identification
        'business_name',
        'business_tax_id',
        'business_address',
        'business_phone',
        'business_email',

        // Currency and locale
        'default_currency',
        'locale',

        // Payment processing settings
        'manual_approval_required',
        'approval_timeout_hours',
        'auto_reminder_enabled',
        'reminder_interval_hours',

        // File upload settings
        'max_file_size_mb',
        'allowed_file_types',

        // Receipt and invoice settings
        'invoice_prefix',
        'receipt_prefix',
        'invoice_footer_text',
        'receipt_footer_text',

        // Legal and tax
        'tax_rate',
        'tax_included',
        'legal_terms',
        'privacy_policy',
    ];

    protected $casts = [
        'manual_approval_required' => 'boolean',
        'auto_reminder_enabled' => 'boolean',
        'tax_included' => 'boolean',
        'allowed_file_types' => 'array',
        'tax_rate' => 'decimal:4',
    ];

    /**
     * Get all payment proofs for these settings
     */
    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    /**
     * Get formatted bank details
     */
    public function getFormattedBankDetailsAttribute(): string
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
    public function getMaxFileSizeBytesAttribute(): int
    {
        return $this->max_file_size_mb * 1024 * 1024;
    }

    /**
     * Get allowed file extensions for validation
     */
    public function getAllowedFileExtensionsAttribute(): string
    {
        return implode(',', array_map(fn($ext) => ".{$ext}", $this->allowed_file_types ?? []));
    }

    /**
     * Get default settings instance
     */
    public static function getDefault(): ?self
    {
        return self::first();
    }
}