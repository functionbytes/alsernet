<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketField extends Model
{
    use HasFactory;

    protected $table = 'ticket_fields';

    protected $fillable = [
        'cust_id',
        'fieldnames',
        'fieldtypes',
        'fieldtypes',
        'values',
        'privacymode',
        'ticket_id',

    ];

    public function toassign()
    {
        return $this->belongsTo('App\Models\User', 'toassignuser_id');
    }
}
