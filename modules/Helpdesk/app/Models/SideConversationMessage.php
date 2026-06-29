<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SideConversationMessage extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_side_conversation_messages';

    protected $fillable = [
        'side_conversation_id',
        'author_type',
        'author_id',
        'from_email',
        'body',
        'attachments',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_read' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sideConversation(): BelongsTo
    {
        return $this->belongsTo(SideConversation::class, 'side_conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isFromAgent(): bool
    {
        return $this->author_type === 'agent';
    }
}
