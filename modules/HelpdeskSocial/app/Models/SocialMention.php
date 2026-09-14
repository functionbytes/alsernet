<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskSocial\Database\Factories\SocialMentionFactory;

class SocialMention extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_mentions';

    protected $fillable = [
        'platform',
        'external_id',
        'author_name',
        'author_username',
        'body',
        'url',
        'matched_keywords',
        'sentiment',
        'sentiment_score',
        'engagement_count',
        'status',
        'discovered_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_keywords' => 'array',
            'sentiment_score' => 'decimal:3',
            'engagement_count' => 'integer',
            'discovered_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeNegative($query)
    {
        return $query->where('sentiment', 'negative');
    }

    protected static function newFactory(): SocialMentionFactory
    {
        return SocialMentionFactory::new();
    }
}
