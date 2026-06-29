<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Chat\Models\Accounts\Account;

class ShortUrl extends Model
{
    protected $table = 'social_short_urls';

    protected $fillable = [
        'account_id',
        'post_id',
        'original_url',
        'short_code',
        'custom_alias',
        'clicks',
        'last_clicked_at',
        'utm_parameters',
    ];

    protected function casts(): array
    {
        return [
            'utm_parameters' => 'array',
            'last_clicked_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public static function generateShortCode(): string
    {
        do {
            $code = Str::random(6);
        } while (self::where('short_code', $code)->exists());

        return $code;
    }

    public function getShortUrlAttribute(): string
    {
        $domain = config('app.url');

        return $this->custom_alias
            ? "{$domain}/s/{$this->custom_alias}"
            : "{$domain}/s/{$this->short_code}";
    }

    public function trackClick(): void
    {
        $this->increment('clicks');
        $this->update(['last_clicked_at' => now()]);
    }

    public function getFullUrlAttribute(): string
    {
        $url = $this->original_url;

        if ($this->utm_parameters) {
            $params = http_build_query($this->utm_parameters);
            $separator = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
            $url .= $separator.$params;
        }

        return $url;
    }
}
