<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConversationContinuation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_continuations';

    protected $fillable = [
        'conversation_id',
        'email',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Create a continuation token for a conversation.
     * Generates a unique 64-char token, valid for 7 days.
     */
    public static function generateForConversation(Conversation $conversation, string $email): self
    {
        return static::create([
            'conversation_id' => $conversation->id,
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
