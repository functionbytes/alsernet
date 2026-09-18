<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Vector del texto de un ticket. Ver la migración que crea la tabla para el
 * porqué de vivir separada de helpdesk_tickets.
 */
class TicketEmbedding extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_embeddings';

    protected $fillable = [
        'ticket_id',
        'embedding',
        'vector_norm',
        'embedding_model',
        'category_id',
        'customer_id',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'vector_norm' => 'float',
        ];
    }
}
