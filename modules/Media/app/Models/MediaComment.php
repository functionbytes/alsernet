<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\Database\Factories\MediaCommentFactory;

class MediaComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'user_id',
        'parent_id',
        'content',
        'mentions',
    ];

    protected function casts(): array
    {
        return [
            'mentions' => 'json',
        ];
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(MediaComment::class, 'parent_id');
    }

    protected static function newFactory(): MediaCommentFactory
    {
        return MediaCommentFactory::new();
    }
}
