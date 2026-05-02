<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Inbox;

class PlatformIntegration extends Model
{
    public const PLATFORM_PRESTASHOP = 'prestashop';

    public const PLATFORM_SHOPIFY = 'shopify';

    public const PLATFORM_WOOCOMMERCE = 'woocommerce';

    public const PLATFORM_CUSTOM = 'custom';

    protected $connection = 'helpdesk';

    protected $table = 'engagement_platform_integrations';

    protected $fillable = [
        'inbox_id',
        'platform',
        'store_url',
        'webhook_secret',
        'config',
        'is_active',
        'last_event_at',
    ];

    protected $hidden = ['webhook_secret'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'last_event_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PlatformIntegration $integration) {
            if (empty($integration->webhook_secret)) {
                $integration->webhook_secret = Str::random(64);
            }
        });
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        return hash_equals(
            hash_hmac('sha256', $payload, $this->webhook_secret),
            $signature,
        );
    }
}
