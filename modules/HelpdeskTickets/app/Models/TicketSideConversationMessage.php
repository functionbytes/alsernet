<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSideConversationMessage extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_side_conversation_messages';

    protected $fillable = [
        'side_conversation_id',
        'user_id',
        'from_email',
        'direction',
        'body',
    ];

    public function sideConversation(): BelongsTo
    {
        return $this->belongsTo(TicketSideConversation::class, 'side_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
