<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class TicketPriority extends Model
{

    use HasFactory;

    protected $table = 'ticket_priorities';

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'color',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }
    public function scopeSlug($query ,$slug)
    {
        return $query->where('slug', $slug)->first();
    }
    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }
    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }
    public function tickets(): HasMany
    {
        return $this->hasMany('App\Models\Ticket\Ticket');
    }

}
