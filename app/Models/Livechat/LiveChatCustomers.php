<?php

namespace App\Models\Livechat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LiveChatConversations;

class LiveChatCustomers extends Model
{
    use HasFactory;

    protected $table = 'livechat_customers';

    protected $fillable = [
        'cust_unique_id',
        'username',
        'email',
        'chat_flow_messages',
        'engage_conversation',
        'file_upload_permission',
        'mobile_number',
        'country',
        'state',
        'mark_as_unread',
        'city',
        'full_address',
        'full_address',
        'timezone',
        'userType',
        'status',
        'verified',
        'login_at',
        'login_ip',
        'browser_info',
    ];

    protected $dates = ['login_at'];

    public function livechatconversation()
    {
        return $this->hasMany(LiveChatConversations::class,'livechat_cust_id');
    }

    public function latestConversation()
    {
        return $this->hasOne(LiveChatConversations::class, 'livechat_cust_id')
            ->latest('created_at');
    }
}
