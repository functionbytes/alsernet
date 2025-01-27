<?php

namespace App\Models\Livechat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LiveChatCustomers;

class LiveChatConversations extends Model
{
    use HasFactory;

    public  $table = 'livechat_conversations';

    protected $fillable = [
        'livechat_cust_id',
        'livechat_username',
        'message',
        'message_type',
        'status',
        'delete',
        'sender_image'
    ];

    public function livechatcust()
    {
        return $this->belongsTo(LiveChatCustomers::class, 'livechat_cust_id');
    }
    
}
