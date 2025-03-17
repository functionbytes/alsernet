<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ChatAssignment extends Model
{
    use HasFactory;

    protected $table = 'chat_assigns';

    public function toassign(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'toassignuser_id');
    }

}
