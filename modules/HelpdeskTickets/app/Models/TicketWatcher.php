<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketWatcher extends Model
{
    use BelongsToHelpdeskUser;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_watchers';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'notify_customer_replies',
        'notify_internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'notify_customer_replies' => 'boolean',
            'notify_internal_notes' => 'boolean',
        ];
    }

    /**
     * Get the ticket being watched
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the user watching this ticket
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user()
    {
        // Create instance with explicit mysql connection for cross-database relationship

        return $this->belongsToHelpdeskUser('user_id', 'user');
    }

    /**
     * Scope to get watchers for a specific ticket
     */
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope to get tickets watched by a specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if a user is watching a ticket
     */
    public static function isWatching($ticketId, $userId): bool
    {
        return static::where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Add a watcher to a ticket
     */
    public static function addWatcher($ticketId, $userId): ?self
    {
        return static::firstOrCreate([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Remove a watcher from a ticket
     */
    public static function removeWatcher($ticketId, $userId): bool
    {
        return static::where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }
}
