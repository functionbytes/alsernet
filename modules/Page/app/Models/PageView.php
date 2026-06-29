<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Page\Database\Factories\PageViewFactory;

class PageView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory(): PageViewFactory
    {
        return PageViewFactory::new();
    }

    protected $fillable = [
        'page_id',
        'session_id',
        'ip_hash',
        'locale',
        'referrer',
        'referrer_domain',
        'device_type',
        'browser',
        'country_code',
        'viewed_date',
        'viewed_at',
        'time_on_page',
        'bounced',
    ];

    protected $casts = [
        'viewed_date' => 'date',
        'viewed_at' => 'datetime',
        'time_on_page' => 'integer',
        'bounced' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public static function detectDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|phone|windows phone/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function extractBrowser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'OPR') => 'Opera',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Other',
        };
    }

    public static function extractReferrerDomain(string $referrer): ?string
    {
        if (empty($referrer)) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return $host ?: null;
    }
}
