<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignImpression extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_campaign_impressions';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'clicked_at' => 'datetime',
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'campaign_id',
        'customer_id',
        'customer_session_id',
        'page_url',
        'device_type', // 'mobile', 'tablet', 'desktop'
        'browser',
        'ip_address',
        'country',
        'viewed_at',
        'clicked_at',
        'metadata',
    ];

    // ==================== Relationships ====================

    /**
     * The campaign this impression belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * The customer who saw the campaign (if logged in)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The customer session this impression is from
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CustomerSession::class, 'customer_session_id');
    }

    // ==================== Scopes ====================

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeNotClicked($query)
    {
        return $query->whereNull('clicked_at');
    }

    public function scopeByDevice($query, $device)
    {
        return $query->where('device_type', $device);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByPageUrl($query, $url)
    {
        return $query->where('page_url', 'like', "%{$url}%");
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== Accessors ====================

    /**
     * Check if this impression resulted in a click
     */
    public function getWasClickedAttribute()
    {
        return ! is_null($this->clicked_at);
    }

    /**
     * Get time from view to click in seconds
     */
    public function getTimeToClickAttribute()
    {
        if (! $this->was_clicked) {
            return null;
        }

        return $this->clicked_at->diffInSeconds($this->viewed_at);
    }

    /**
     * Get device type label in Spanish
     */
    public function getDeviceLabelAttribute()
    {
        return match ($this->device_type) {
            'mobile' => 'Móvil',
            'tablet' => 'Tablet',
            'desktop' => 'Escritorio',
            default => $this->device_type,
        };
    }

    // ==================== Methods ====================

    /**
     * Record a click for this impression
     */
    public function recordClick()
    {
        $this->update(['clicked_at' => now()]);

        return $this;
    }

    /**
     * Get the conversion rate (0-100)
     */
    public function getConversionRate()
    {
        return $this->was_clicked ? 100 : 0;
    }
}
