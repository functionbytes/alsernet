<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TicketNote extends Model
{
    use HasFactory;

    protected $table = 'ticket_notes';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'notes',
    ];

    public function users(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

}
