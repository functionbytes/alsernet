<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRead extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_reads';

    protected $fillable = [
        'ticket_item_id',
        'user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Set read_at timestamp when creating
        static::creating(function ($read) {
            if (! $read->read_at) {
                $read->read_at = now();
            }
        });
    }

    /**
     * Get the ticket item that was read
     */
    public function ticketItem(): BelongsTo
    {
        return $this->belongsTo(TicketItem::class, 'ticket_item_id');
    }

    /**
     * Get the user who read this item
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user()
    {
        // Create instance with explicit mysql connection for cross-database relationship
        $user = new User;
        $user->setConnection('mysql');

        return $this->newBelongsTo(
            $user->newQuery(),
            $this,
            'user_id',
            'id',
            'user'
        );
    }
}
