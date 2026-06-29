<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Social\Enums\SocialNetwork;

class PostVariation extends Model
{
    protected $table = 'social_post_variations';

    protected $fillable = [
        'post_id',
        'network',
        'content',
        'media',
        'hashtags',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'network' => SocialNetwork::class,
            'media' => 'array',
            'hashtags' => 'array',
            'settings' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeForNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }
}
