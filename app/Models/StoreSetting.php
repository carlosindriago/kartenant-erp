<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreSetting extends Model
{
    use HasFactory;

    /**
     * The database connection that should be used by the model.
     */
    protected $connection = 'tenant';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'logo_path',
        'background_image_path',
        'brand_color',
        'welcome_message',
        'store_name',
        'store_slogan',
        'is_active',
        'show_background_image',
        'primary_font',
        'facebook_url',
        'instagram_url',
        'whatsapp_number',
        'contact_email',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'show_background_image' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating a new store setting
        static::creating(function ($storeSetting) {
            $storeSetting->brand_color = $storeSetting->brand_color ?? '#2563eb';
            $storeSetting->welcome_message = $storeSetting->welcome_message ?? '¡Bienvenido a tu tienda! Gestiona tu inventario de forma sencilla y eficiente.';
            $storeSetting->store_name = $storeSetting->store_name ?? config('app.name', 'Mi Tienda');
            $storeSetting->primary_font = $storeSetting->primary_font ?? 'Inter';
            $storeSetting->is_active = $storeSetting->is_active ?? true;
            $storeSetting->show_background_image = $storeSetting->show_background_image ?? true;
        });
    }

    /**
     * Get the full URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Get the full URL for the background image.
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        if (!$this->background_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->background_image_path);
    }

    /**
     * Get the effective welcome message with fallback.
     */
    public function getEffectiveWelcomeMessageAttribute(): string
    {
        if ($this->is_active && $this->welcome_message) {
            return $this->welcome_message;
        }

        return '¡Bienvenido a tu tienda! Gestiona tu inventario de forma sencilla y eficiente.';
    }

    /**
     * Get the effective brand color with fallback.
     */
    public function getEffectiveBrandColorAttribute(): string
    {
        if ($this->is_active && $this->brand_color) {
            return $this->brand_color;
        }

        return '#2563eb'; // Default blue color
    }

    /**
     * Get the effective store name with fallback.
     */
    public function getEffectiveStoreNameAttribute(): string
    {
        if ($this->is_active && $this->store_name) {
            return $this->store_name;
        }

        return config('app.name', 'Mi Tienda');
    }

    /**
     * Get the effective store slogan with fallback.
     */
    public function getEffectiveStoreSloganAttribute(): string
    {
        if ($this->is_active && $this->store_slogan) {
            return $this->store_slogan;
        }

        return 'Tu sistema de gestión comercial';
    }

    /**
     * Get the effective primary font with fallback.
     */
    public function getEffectivePrimaryFontAttribute(): string
    {
        if ($this->is_active && $this->primary_font) {
            return $this->primary_font;
        }

        return 'Inter';
    }

    /**
     * Set the brand_color attribute with validation.
     */
    public function setBrandColorAttribute($value): void
    {
        if ($value && !Str::startsWith($value, '#')) {
            $value = '#' . $value;
        }

        // Validate hex color format
        if ($value && !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            throw new \InvalidArgumentException('El color debe estar en formato hexadecimal (#FF5733)');
        }

        $this->attributes['brand_color'] = $value;
    }

    /**
     * Set the welcome_message attribute with sanitization.
     */
    public function setWelcomeMessageAttribute($value): void
    {
        $this->attributes['welcome_message'] = trim(strip_tags($value));
    }

    /**
     * Set the store_name attribute with sanitization.
     */
    public function setStoreNameAttribute($value): void
    {
        $this->attributes['store_name'] = trim(strip_tags($value));
    }

    /**
     * Set the store_slogan attribute with sanitization.
     */
    public function setStoreSloganAttribute($value): void
    {
        $this->attributes['store_slogan'] = trim(strip_tags($value));
    }

    /**
     * Set the whatsapp_number attribute with normalization.
     */
    public function setWhatsappNumberAttribute($value): void
    {
        // Remove non-digit characters
        $this->attributes['whatsapp_number'] = preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Get the WhatsApp URL for the stored number.
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        if (!$this->whatsapp_number) {
            return null;
        }

        return "https://wa.me/{$this->whatsapp_number}";
    }

    /**
     * Check if the store has social media configured.
     */
    public function hasSocialMedia(): bool
    {
        return !empty($this->facebook_url) ||
               !empty($this->instagram_url) ||
               !empty($this->whatsapp_number);
    }

    /**
     * Get all social media links as an array.
     */
    public function getSocialMediaLinksAttribute(): array
    {
        return [
            'facebook' => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'whatsapp' => $this->whatsapp_url,
            'email' => $this->contact_email,
        ];
    }

    /**
     * Get the CSS color variables for the theme.
     */
    public function getCssVariablesAttribute(): string
    {
        $color = $this->effective_brand_color;

        return "--primary-color: {$color}; --primary-hover: " . $this->adjustBrightness($color, -20) . ";";
    }

    /**
     * Adjust the brightness of a hex color.
     */
    public function adjustBrightness(string $hex, int $percent): string
    {
        $hex = str_replace('#', '', $hex);

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
               str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
               str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get the current store settings for the tenant.
     * Creates default settings if none exist.
     */
    public static function current(): self
    {
        $settings = static::first();

        if (!$settings) {
            // Get current tenant using Spatie's helper
            $containerKey = config('multitenancy.current_tenant_container_key', 'currentTenant');
            $currentTenant = app()->bound($containerKey) ? app($containerKey) : null;

            $settings = static::create([
                'store_name' => $currentTenant?->name ?? config('app.name'),
                'welcome_message' => '¡Bienvenido a ' . ($currentTenant?->name ?? 'tu tienda') . '! Gestiona tu inventario de forma sencilla y eficiente.',
            ]);
        }

        return $settings;
    }

    /**
     * Get the file path for storing logos.
     */
    public static function getLogoStoragePath(): string
    {
        return 'store-settings/logos';
    }

    /**
     * Get the file path for storing background images.
     */
    public static function getBackgroundStoragePath(): string
    {
        return 'store-settings/backgrounds';
    }

    /**
     * Update logo with new uploaded file.
     */
    public function updateLogo(string $logoPath): self
    {
        // Delete old logo if exists
        if ($this->logo_path) {
            $this->deleteLogo();
        }

        $this->logo_path = $logoPath;
        $this->save();

        return $this;
    }

    /**
     * Update background with new uploaded file.
     */
    public function updateBackground(string $backgroundPath): self
    {
        // Delete old background if exists
        if ($this->background_image_path) {
            $this->deleteBackground();
        }

        $this->background_image_path = $backgroundPath;
        $this->save();

        return $this;
    }

    /**
     * Delete logo file and update database.
     */
    public function deleteLogo(): self
    {
        if ($this->logo_path) {
            try {
                $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
                \Storage::disk($disk)->delete($this->logo_path);
            } catch (\Exception $e) {
                \Log::warning('Error deleting logo file', [
                    'path' => $this->logo_path,
                    'error' => $e->getMessage()
                ]);
            }

            $this->logo_path = null;
            $this->save();
        }

        return $this;
    }

    /**
     * Delete background file and update database.
     */
    public function deleteBackground(): self
    {
        if ($this->background_image_path) {
            try {
                $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
                \Storage::disk($disk)->delete($this->background_image_path);
            } catch (\Exception $e) {
                \Log::warning('Error deleting background file', [
                    'path' => $this->background_image_path,
                    'error' => $e->getMessage()
                ]);
            }

            $this->background_image_path = null;
            $this->save();
        }

        return $this;
    }

    /**
     * Get public URL for logo.
     */
    public function getLogoPublicUrl(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
        return \Storage::disk($disk)->url($this->logo_path);
    }

    /**
     * Get public URL for background image.
     */
    public function getBackgroundPublicUrl(): ?string
    {
        if (!$this->background_image_path) {
            return null;
        }

        $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
        return \Storage::disk($disk)->url($this->background_image_path);
    }

    /**
     * Check if logo exists in storage.
     */
    public function hasLogo(): bool
    {
        if (!$this->logo_path) {
            return false;
        }

        $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
        return \Storage::disk($disk)->exists($this->logo_path);
    }

    /**
     * Check if background image exists in storage.
     */
    public function hasBackground(): bool
    {
        if (!$this->background_image_path) {
            return false;
        }

        $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
        return \Storage::disk($disk)->exists($this->background_image_path);
    }

    /**
     * Get logo file size in human readable format.
     */
    public function getLogoSize(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        try {
            $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
            $size = \Storage::disk($disk)->size($this->logo_path);
            return $this->formatBytes($size);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get background file size in human readable format.
     */
    public function getBackgroundSize(): ?string
    {
        if (!$this->background_image_path) {
            return null;
        }

        try {
            $disk = config('multitenancy.tenant_uploads_disk', 'tenant_uploads');
            $size = \Storage::disk($disk)->size($this->background_image_path);
            return $this->formatBytes($size);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Generate a unique filename for uploaded files.
     */
    public static function generateUniqueFilename(string $originalName, string $prefix = ''): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(6);

        return ($prefix ? $prefix . '_' : '') . $timestamp . '_' . $random . '.' . $extension;
    }
}