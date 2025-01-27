<?php

namespace App\Models\Livechat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveChatFlow extends Model
{
    use HasFactory;

    protected $table = 'livechat_flows';

    protected $fillable = [
        'liveshatflow',
        'active',
        'active_draft',
        'responseName'
    ];
}
