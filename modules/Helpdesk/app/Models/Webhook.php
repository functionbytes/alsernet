<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_webhooks';

    protected $fillable = [
        'name',
        'url',
        'integration_type',
        'events',
        'secret',
        'headers',
        'is_active',
        'user_id',
        'success_count',
        'failure_count',
        'last_triggered_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'headers' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function shouldFireFor(string $event): bool
    {
        return $this->is_active && in_array($event, $this->events ?? []);
    }
}
