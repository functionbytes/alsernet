<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Helpdesk\Models\Customer;

class WidgetSession extends Model
{
    use HasFactory;

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

    protected function timeOnSite(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) ($this->started_at?->diffInSeconds(now()) ?? 0),
        );
    }

    /**
     * GDPR-friendly IP anonymization: zero-out last IPv4 octet or last 80 bits
     * of IPv6 before persisting. Preserves country/region geolocation while
     * removing identifying host information.
     */
    protected function ipAddress(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => self::anonymizeIp($value),
        );
    }

    public static function anonymizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed === false) {
                return null;
            }
            // Keep the first 48 bits (network prefix), zero out the rest.
            $masked = substr($packed, 0, 6).str_repeat("\0", 10);

            return inet_ntop($masked) ?: null;
        }

        return null;
    }
}
