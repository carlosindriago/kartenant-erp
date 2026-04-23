<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSettings extends Model
{
    protected $connection = 'landlord';

    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_settings.{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $description = null): self
    {
        $stringValue = is_array($value) || is_object($value)
            ? json_encode($value)
            : (string) $value;

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'group' => $group,
                'type' => $type,
                'description' => $description,
            ]
        );

        Cache::forget("system_settings.{$key}");

        return $setting;
    }

    /**
     * Check if a setting exists
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Delete a setting
     */
    public static function forget(string $key): bool
    {
        Cache::forget("system_settings.{$key}");

        return (bool) self::where('key', $key)->delete();
    }

    /**
     * Get all settings in a group
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("system_settings.{$setting->key}");
        }
    }

    /**
     * Get all public settings (for frontend)
     */
    public static function getPublicSettings(): array
    {
        $settings = self::where('is_public', true)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Helper methods for common settings
     */
    public static function getAppTimezone(): string
    {
        return self::get('app_timezone', config('app.timezone', 'UTC'));
    }

    public static function getSlackWebhook(): ?string
    {
        return self::get('slack_webhook_url');
    }

    public static function isSlackEnabled(): bool
    {
        return (bool) self::get('slack_enabled', false) && ! empty(self::getSlackWebhook());
    }

    public static function getSmtpConfig(): array
    {
        return [
            'host' => self::get('smtp_host'),
            'port' => self::get('smtp_port', 587),
            'username' => self::get('smtp_username'),
            'password' => self::get('smtp_password'),
            'encryption' => self::get('smtp_encryption', 'tls'),
            'from_address' => self::get('smtp_from_address'),
            'from_name' => self::get('smtp_from_name'),
        ];
    }
}
