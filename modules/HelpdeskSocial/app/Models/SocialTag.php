<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\HelpdeskSocial\Database\Factories\SocialTagFactory;

class SocialTag extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function comments(): BelongsToMany
    {
        return $this->belongsToMany(SocialComment::class, 'helpdesk_social_comment_tag', 'social_tag_id', 'social_comment_id')
            ->withPivot('tagged_by_user_id', 'created_at');
    }

    protected static function newFactory(): SocialTagFactory
    {
        return SocialTagFactory::new();
    }
}
