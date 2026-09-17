<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketRead extends Model
{
    use BelongsToHelpdeskUser;

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
     * Mark every item on the ticket as read for the given user (bulk insert,
     * skipping items already marked read). It queries only item IDs, so the
     * result is correct even when a caller loaded a filtered thread.
     *
     * @return int Number of items newly marked as read (0 if the user had
     *             already read everything — TicketDetailDataController usa
     *             esto para no registrar "Ticket visto" en la pestaña
     *             Actividad en cada apertura, solo cuando de verdad había
     *             algo nuevo que leer).
     */
    public static function markAllReadFor(Ticket $ticket, int $userId): int
    {
        // No uses la relación potencialmente filtrada que haya cargado la
        // pantalla. El detalle admite ?thread_search y, si se reutilizaba
        // $ticket->items, abrir una búsqueda marcaba leídos solo los matches;
        // los mensajes restantes seguían apareciendo como no leídos al volver
        // al listado. Solo necesitamos los IDs y esta consulta evita además
        // hidratar el hilo entero.
        $itemIds = TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            return 0;
        }

        $alreadyRead = static::where('user_id', $userId)
            ->whereIn('ticket_item_id', $itemIds)
            ->pluck('ticket_item_id');

        $toInsert = $itemIds
            ->diff($alreadyRead)
            ->map(fn ($id) => [
                'ticket_item_id' => $id,
                'user_id' => $userId,
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($toInsert !== []) {
            static::insert($toInsert);
        }

        return count($toInsert);
    }

    /**
     * Get the user who read this item
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user()
    {
        // Create instance with explicit mysql connection for cross-database relationship

        return $this->belongsToHelpdeskUser('user_id', 'user');
    }
}
