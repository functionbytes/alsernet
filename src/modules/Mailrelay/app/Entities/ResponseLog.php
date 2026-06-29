<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponseLog extends Model
{
    use HasFactory;

    protected $table = 'mails_response_logs';

    protected $fillable = [
        'subscriber_id',
        'campaign_id',
        'response_type',
        'url',
    ];

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
