<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiBatch extends Model
{
    use HasFactory;

    protected $table = 'mails_api_batches';

    protected $fillable = [
        'batch_data',
        'executed_at',
    ];
}
