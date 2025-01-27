<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentConversation extends Model
{
    use HasFactory;

    protected $table = 'agent_conversations';

    protected $fillable  = [
        'unique_id',
        'sender_user_id',
        'receiver_user_id',
        'message',
        'message_type',
        "mark_as_unread",
        "delete_status",
    ];
    
}
