<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationRead extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_reads';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the conversation this read receipt belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the user who read the message
     * Note: User model is in the default connection, not helpdesk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->setConnection(config('database.default'));
    }
}
