<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ticket_histories';

    protected $fillable = [
        'ticket_id',
        'ticketactions',
        'ticketstatus',
    ];

    
}
