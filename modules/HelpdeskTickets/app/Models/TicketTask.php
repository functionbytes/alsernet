<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarea de la checklist interna de un ticket.
 */
class TicketTask extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_tasks';

    protected $fillable = ['ticket_id', 'title', 'is_done', 'done_by', 'done_at', 'position', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return array<string, mixed> */
    public function toPanelRow(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'is_done' => $this->is_done,
            'done_at_human' => $this->done_at?->diffForHumans(),
        ];
    }
}
