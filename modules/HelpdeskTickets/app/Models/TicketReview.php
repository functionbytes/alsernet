<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Revisión de calidad de un ticket cerrado. Ver la migración para el porqué de
 * muestrear en vez de esperar al CSAT.
 */
class TicketReview extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_reviews';

    protected $fillable = [
        'ticket_id',
        'agent_id',
        'score',
        'dimensions',
        'summary',
        'issues',
        'disputed',
        'dispute_note',
        'disputed_by',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'issues' => 'array',
            'disputed' => 'boolean',
            'score' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Revisiones que cuentan para una media: las disputadas se excluyen porque
     * alguien ya dijo que la evaluación estaba mal, y arrastrarlas hace que la
     * media mida al revisor y no al equipo.
     */
    public function scopeCounted($query)
    {
        return $query->where('disputed', false);
    }
}
