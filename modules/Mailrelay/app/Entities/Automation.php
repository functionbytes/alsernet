<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;

    protected $table = 'mails_automations';

    protected $fillable = [
        'name',
        'trigger',
        'action',
    ];
}
