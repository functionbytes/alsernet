<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SideConversation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_side_conversations';

    protected $fillable = [
        'parent_conversation_id',
        'subject',
        'participant_type',
        'participant_email',
        'participant_user_id',
        'status',
        'created_by',
        'reply_token',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function parentConversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'parent_conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SideConversationMessage::class, 'side_conversation_id');
    }

    public function participantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
