<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'mails_activity_logs';

    protected $fillable = [
        'activity',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
