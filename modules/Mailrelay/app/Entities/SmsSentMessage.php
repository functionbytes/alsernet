<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSentMessage extends Model
{
    use HasFactory;

    protected $table = 'mails_sms_sent_messages';

    protected $fillable = [
        'phone_number',
        'message',
        'status',
        'sent_at',
    ];
}
