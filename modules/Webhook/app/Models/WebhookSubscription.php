<?php

namespace Modules\Webhook\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Webhook\Traits\HasUid;

class WebhookSubscription extends Model
{
    use HasFactory, HasUid;

    protected $table = 'webhook_subscriptions';

    protected $fillable = [
        'uid',
        'integration_id',
        'name',
        'url',
        'is_active',
        'subscribed_events',
        'auth_type',
        'auth_config',
        'signing_secret',
        'timeout_ms',
        'max_attempts',
        'backoff_policy',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_events' => 'array',
        'auth_config' => 'array',
        'backoff_policy' => 'array',
        'timeout_ms' => 'integer',
        'max_attempts' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'auth_type' => 'none',
        'timeout_ms' => 10000,
        'max_attempts' => 6,
    ];

    protected $hidden = [
        'auth_config',
        'signing_secret',
    ];

    /**
     * Get the integration that owns this subscription.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(WebhookIntegration::class, 'integration_id');
    }

    /**
     * Scope: only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: subscriptions listening to a specific event.
     */
    public function scopeListeningTo($query, string $event)
    {
        return $query->whereJsonContains('subscribed_events', $event);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if subscription listens to a specific event.
     */
    public function listensTo(string $event): bool
    {
        return in_array($event, $this->subscribed_events ?? []);
    }

    /**
     * Activate the subscription.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the subscription.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }
}
