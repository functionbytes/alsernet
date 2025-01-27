<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMail extends Model
{
    use HasFactory;

    protected $table = 'ticket_mails';

    protected $fillable = [
        'ticket_id',
        'ccemails',
    ];

}
