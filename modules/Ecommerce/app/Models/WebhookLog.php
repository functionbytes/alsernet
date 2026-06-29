<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'ecommerce_webhook_logs';

    protected $fillable = [
        'event',
        'url',
        'payload',
        'response_status',
        'response_body',
        'attempts',
        'success',
        'error',
    ];

    public function casts(): array
    {
        return [
            'payload' => 'array',
            'success' => 'boolean',
        ];
    }
}
