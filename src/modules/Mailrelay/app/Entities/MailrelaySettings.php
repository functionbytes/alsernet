<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Model;

class MailrelaySettings extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'mailrelay_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Sender settings
        'sender_name',
        'sender_email',
        'reply_to_email',

        // Sync settings
        'auto_sync_enabled',
        'sync_frequency',
        'sync_deleted',

        // Limits
        'emails_per_campaign',
        'retry_attempts',
        'timeout',

        // Privacy
        'double_optin',
        'allow_unsubscribe',
        'unsubscribe_footer',

        // Advanced
        'detailed_logging',
        'log_retention_days',
        'sandbox_mode',

        // API settings
        'api_key',
        'api_url',
        'cache_enabled',
        'cache_ttl',
        'retry_enabled',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'auto_sync_enabled' => 'boolean',
            'sync_deleted' => 'boolean',
            'double_optin' => 'boolean',
            'allow_unsubscribe' => 'boolean',
            'detailed_logging' => 'boolean',
            'sandbox_mode' => 'boolean',
            'cache_enabled' => 'boolean',
            'retry_enabled' => 'boolean',
            'sync_frequency' => 'integer',
            'emails_per_campaign' => 'integer',
            'retry_attempts' => 'integer',
            'timeout' => 'integer',
            'log_retention_days' => 'integer',
            'cache_ttl' => 'integer',
            'api_key' => 'encrypted',
        ];
    }

    /**
     * Get the singleton settings instance
     * Uses first-or-create pattern to ensure only one settings record exists
     */
    public static function instance(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                // Default values
                'sender_name' => config('mail.from.name', 'Example'),
                'sender_email' => config('mail.from.address', 'hello@example.com'),
                'auto_sync_enabled' => false,
                'sync_frequency' => 60,
                'sync_deleted' => false,
                'emails_per_campaign' => 1000,
                'retry_attempts' => 3,
                'timeout' => 30,
                'double_optin' => false,
                'allow_unsubscribe' => true,
                'detailed_logging' => false,
                'log_retention_days' => 30,
                'sandbox_mode' => false,
                'cache_enabled' => true,
                'cache_ttl' => 60,
                'retry_enabled' => true,
            ]
        );
    }

    /**
     * Get a specific setting value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::instance();

        return $settings->$key ?? $default;
    }

    /**
     * Set a specific setting value
     */
    public static function set(string $key, mixed $value): bool
    {
        $settings = static::instance();
        $settings->$key = $value;

        return $settings->save();
    }

    /**
     * Update multiple settings at once
     */
    public static function updateSettings(array $data): bool
    {
        $settings = static::instance();

        return $settings->update($data);
    }
}
