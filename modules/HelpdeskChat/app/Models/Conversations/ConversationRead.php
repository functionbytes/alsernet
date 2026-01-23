<?php

namespace Modules\HelpdeskChat\Models\Conversations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationRead extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_conversation_reads';

    public $timestamps = false;

    protected $fillable = [
        'conversation_item_id',
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
     * Get the conversation item this read receipt belongs to
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ConversationItem::class, 'conversation_item_id');
    }

    /**
     * Get the user who read the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
