<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskSocial\Database\Factories\SocialConversationFactory;

class SocialConversation extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_conversations';

    protected $fillable = [
        'social_account_id',
        'platform',
        'external_conversation_id',
        'external_post_id',
        'participant_external_id',
        'participant_name',
        'status',
        'message_count',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'message_count' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'social_conversation_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeForParticipant($query, string $externalId)
    {
        return $query->where('participant_external_id', $externalId);
    }

    protected static function newFactory(): SocialConversationFactory
    {
        return SocialConversationFactory::new();
    }
}
