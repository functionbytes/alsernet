<?php

namespace Modules\HelpdeskSocial\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskSocial\Database\Factories\SocialCommentNoteFactory;

class SocialCommentNote extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_comment_notes';

    protected $fillable = [
        'social_comment_id',
        'user_id',
        'body',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class, 'social_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): SocialCommentNoteFactory
    {
        return SocialCommentNoteFactory::new();
    }
}
