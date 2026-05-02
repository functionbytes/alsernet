<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class HashtagGroup extends Model
{
    protected $table = 'social_hashtag_groups';

    protected $fillable = [
        'account_id',
        'name',
        'category',
        'hashtags',
        'color',
        'usage_count',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getHashtagsArrayAttribute(): array
    {
        return array_map('trim', explode(',', $this->hashtags));
    }

    public function getFormattedHashtagsAttribute(): string
    {
        $tags = $this->hashtags_array;

        return implode(' ', array_map(function ($tag) {
            return str_starts_with($tag, '#') ? $tag : '#'.$tag;
        }, $tags));
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
