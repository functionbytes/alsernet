<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageWebhook extends Model
{
    protected $table = 'page_webhooks';

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'timeout',
        'success_count',
        'fail_count',
        'last_triggered_at',
        'last_success_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    /** Scope to only active webhooks. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Check if this webhook listens to the given event. */
    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }

    /**
     * Generate HMAC-SHA256 signature for the given payload.
     *
     * The timestamp is included in the signed payload to prevent replay attacks.
     * Receivers must reject requests where the timestamp is older than a threshold (e.g. 5 minutes).
     */
    public function generateSignature(string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$payload, (string) $this->secret);
    }
}
