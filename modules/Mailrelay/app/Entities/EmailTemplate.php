<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'mails_email_templates';

    protected $fillable = [
        'name',
        'html_content',
        'text_content',
        'subject',
    ];
}
