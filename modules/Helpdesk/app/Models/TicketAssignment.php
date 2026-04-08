<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAssignment extends Model
{
    use HasUid;

    protected $table = 'helpdesk_ticket_assignments';

    protected $fillable = [
        'uid',
        'ticket_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    /**
     * Ticket this assignment belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * User assigned to the ticket
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * User who made the assignment
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Boot method to set assigned_at timestamp
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TicketAssignment $assignment) {
            if (empty($assignment->assigned_at)) {
                $assignment->assigned_at = now();
            }
        });
    }
}
