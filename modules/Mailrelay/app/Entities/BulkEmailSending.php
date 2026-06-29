<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkEmailSending extends Model
{
    use HasFactory;

    protected $table = 'mails_bulk_email_sendings';

    protected $fillable = [
        'status',
        'bulk_email_id',
    ];

    public function bulkEmail()
    {
        return $this->belongsTo(BulkEmail::class);
    }
}
