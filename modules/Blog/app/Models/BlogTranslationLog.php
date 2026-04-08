<?php

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogTranslationLog extends Model
{
    protected $table = 'blog_translation_logs';

    protected $fillable = [
        'blog_post_id',
        'locale',
        'action',
        'user_id',
        'fields_changed',
        'provider',
        'characters_used',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'fields_changed' => 'array',
            'metadata' => 'array',
            'characters_used' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
