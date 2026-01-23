<?php

namespace Modules\Webhook\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Webhook\Traits\HasUid;

class WebhookIntegration extends Model
{
    use HasFactory, HasUid;

    protected $table = 'webhook_integrations';

    protected $fillable = [
        'uid',
        'name',
        'status',
        'plan',
        'daily_limit',
        'allowed_ips',
        'allowed_domains',
        'notes',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'allowed_domains' => 'array',
        'daily_limit' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
        'plan' => 'free',
        'daily_limit' => 1000,
    ];

    /**
     * Get all subscriptions for this integration.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'integration_id');
    }

    /**
     * Get active subscriptions only.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('is_active', true);
    }

    /**
     * Scope: only active integrations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: only suspended integrations.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Check if integration is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if integration is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if integration is disabled.
     */
    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }
}
