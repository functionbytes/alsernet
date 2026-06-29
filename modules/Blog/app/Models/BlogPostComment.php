<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Blog\Database\Factories\BlogPostCommentFactory;
use Modules\Blog\Enums\CommentStatus;

class BlogPostComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'blog_post_comments';

    protected $fillable = [
        'blog_post_id',
        'parent_id',
        'author_name',
        'author_email',
        'content',
        'status',
        'ip_address',
        'ip_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'ip_expires_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BlogPostCommentFactory
    {
        return BlogPostCommentFactory::new();
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('status', CommentStatus::Approved)
            ->orderBy('created_at');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Approved);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeExpiredIps(Builder $query): Builder
    {
        return $query->whereNotNull('ip_address')
            ->whereNotNull('ip_expires_at')
            ->where('ip_expires_at', '<=', now());
    }

    public function getAvatarUrlAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->author_name).'&size=70&background=90bb13&color=fff&rounded=true';
    }
}
