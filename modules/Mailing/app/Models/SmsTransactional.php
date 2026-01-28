<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTransactional extends Model
{
    use HasFactory;

    protected $table = 'mails_sms_transactionals';

    protected $fillable = [
        'phone_number',
        'message',
        'sent_at',
    ];
}
