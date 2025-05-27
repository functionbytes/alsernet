<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class Chat extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table ="tickets";

    protected $fillable = [
        'cust_id',
        'category_id',
        'image',
        'ticket_id',
        'title',
        'priority_id',
        'message',
        'status_id',
        'subject',
        'user_id',
        'project_id',
        'auto_close_ticket',
        'purchasecode',
        'subcategory'
    ];

    protected $dates = [
        'closing_ticket',
        'last_reply',
        'craeted_at',
        'updated_at',
        'auto_replystatus',
        'auto_close_ticket',
        'auto_overdue_ticket'
    ];

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}

    public function toassignuser(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'toassignuser_id');
    }
    public function myassignuser(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'myassignuser_id');
    }
    public function comments(): HasMany
    {
        return $this->hasMany('App\Models\Ticket\TicketComment')->latest('created_at');
    }

    public function cust(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'cust_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Ticket\TicketCategorie', 'category_id');
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo('App\Models\Ticket\TicketStatus', 'status_id');
    }
    public function priority(): BelongsTo
    {
        return $this->belongsTo('App\Models\Ticket\TicketPriority', 'priority_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany('App\Models\Ticket\TicketNote', 'ticket_id');
    }

    public function self(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'selfassignuser_id');
    }

    public function closed(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'closedby_user');
    }

    public function assign(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Ticket\TicketAssignment', 'ticketassignchildren', 'ticket_id', 'toassignuser_id');
    }

    public function assigns(): HasMany
    {
        return $this->hasMany('App\Models\Ticket\TicketAssignment');
    }

    public function historys(): HasMany
    {
        return $this->hasMany('App\Models\Ticket\TicketHistory', 'ticket_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ticket');
    }

}
