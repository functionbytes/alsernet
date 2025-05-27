<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCustomField extends Model
{
    use HasFactory;

    protected $table = 'ticket_fieldcustoms';

    protected $fillable = [
        'cust_id',
        'fieldnames',
        'fieldtypes',
        'fieldtypes',
        'values',
        'privacymode',
        'ticket_id',
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function toassign()
    {
        return $this->belongsTo('App\Models\User', 'toassignuser_id');
    }
}
