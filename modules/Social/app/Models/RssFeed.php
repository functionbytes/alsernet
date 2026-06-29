<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Accounts\Account;

class RssFeed extends Model
{
    protected $table = 'social_rss_feeds';

    protected $fillable = [
        'account_id',
        'social_account_id',
        'campaign_id',
        'name',
        'feed_url',
        'auto_publish',
        'post_template',
        'hashtags',
        'fetch_interval',
        'last_fetch_at',
        'next_fetch_at',
        'posts_created',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'auto_publish' => 'boolean',
            'hashtags' => 'array',
            'last_fetch_at' => 'datetime',
            'next_fetch_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'account_id', 'account_id')
            ->whereNotNull('external_id');
    }

    public function isDueForFetch(): bool
    {
        return $this->active
            && $this->next_fetch_at
            && $this->next_fetch_at->isPast();
    }

    public function scheduleNextFetch(): void
    {
        $this->update([
            'last_fetch_at' => now(),
            'next_fetch_at' => now()->addMinutes($this->fetch_interval),
        ]);
    }

    public function generatePostContent(array $feedItem): string
    {
        $content = $this->post_template ?? '{title}\n\n{description}\n\n{link}';

        $replacements = [
            '{title}' => $feedItem['title'] ?? '',
            '{description}' => $feedItem['description'] ?? '',
            '{link}' => $feedItem['link'] ?? '',
            '{author}' => $feedItem['author'] ?? '',
            '{published}' => $feedItem['published'] ?? '',
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        if ($this->hashtags) {
            $content .= '\n\n'.implode(' ', $this->hashtags);
        }

        return $content;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeDueForFetch($query)
    {
        return $query->active()
            ->whereNotNull('next_fetch_at')
            ->where('next_fetch_at', '<=', now());
    }
}
