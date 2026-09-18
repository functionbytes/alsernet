<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Webhook extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_webhooks';

    protected $hidden = ['secret'];

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

    protected function secret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
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
