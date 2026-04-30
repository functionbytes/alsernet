<?php

namespace Modules\Chat\Models\Campaigns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Customers\Customer;

class CampaignImpression extends Model
{
    use HasFactory;

    protected $table = 'chat_campaign_impressions';

    protected $casts = [
        'clicked_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'campaign_id',
        'customer_id',
        'account_id',
        'session_id',
        'type', // 'view', 'click', 'dismiss'
        'device_type', // 'desktop', 'mobile', 'tablet'
        'browser',
        'platform',
        'country',
        'city',
        'ip_address',
        'user_agent',
        'referrer_url',
        'current_url',
        'clicked_at',
        'metadata',
    ];

    // ==================== Relationships ====================

    /**
     * Impression belongs to a campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Impression belongs to a customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ==================== Scopes ====================

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeViewed($query)
    {
        return $query->where('type', 'view');
    }

    public function scopeDismissed($query)
    {
        return $query->where('type', 'dismiss');
    }

    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    // ==================== Accessors ====================

    /**
     * Get time to click in seconds (time between impression and click)
     */
    public function getTimeToClickAttribute(): ?float
    {
        if (! $this->clicked_at) {
            return null;
        }

        return $this->clicked_at->diffInSeconds($this->created_at);
    }

    /**
     * Get device type label
     */
    public function getDeviceTypeLabelAttribute(): string
    {
        return match ($this->device_type) {
            'desktop' => 'Escritorio',
            'mobile' => 'Móvil',
            'tablet' => 'Tablet',
            default => $this->device_type ?? 'Desconocido',
        };
    }

    /**
     * Get impression type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'view' => 'Vista',
            'click' => 'Clic',
            'dismiss' => 'Cerrado',
            default => $this->type,
        };
    }
}
