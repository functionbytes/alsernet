<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailrelaySetting extends Model
{
    protected $fillable = [
        'api_url',
        'api_key',
        'cache_enabled',
        'cache_ttl',
        'retry_enabled',
    ];

    protected $casts = [
        'cache_enabled' => 'boolean',
        'retry_enabled' => 'boolean',
        'cache_ttl' => 'integer',
    ];

    /**
     * Get the first (and usually only) settings record.
     */
    public static function getInstance(): self
    {
        return self::firstOrCreate([], [
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'retry_enabled' => true,
        ]);
    }
}
