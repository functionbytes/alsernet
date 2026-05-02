<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Helpdesk\Models\Customer;

class WidgetSession extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_widget_sessions';

    protected $fillable = [
        'customer_id',
        'session_token',
        'current_url',
        'referrer',
        'device',
        'ip_address',
        'country_code',
        'started_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'device' => 'array',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(WidgetPageView::class, 'session_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes(5));
    }

    public function getTimeOnSiteAttribute(): int
    {
        return (int) $this->started_at->diffInSeconds(now());
    }
}
