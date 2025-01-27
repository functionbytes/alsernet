<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAssignment extends Model
{
    use HasFactory;

    protected $table = 'ticket_assigns';

    public function toassign()
    {
        return $this->belongsTo('App\Models\User', 'toassignuser_id');
    }
}
