<?php

namespace Modules\HelpdeskTickets\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasUid;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_attachments';

    protected $fillable = [
        'uid',
        'ticket_message_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Message this attachment belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    /**
     * URL de descarga del adjunto.
     *
     * Storage::url() no sirve aquí: el disco de adjuntos es privado
     * (storage/app, fuera de public/), así que devolvía una URL que siempre
     * daba 404. La descarga real pasa por una ruta autorizada — la del panel si
     * hay agente en sesión, la del portal si es el cliente.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ticketId = $this->message?->ticket_id;

                if (! $ticketId) {
                    return null;
                }

                return route('manager.helpdesk.tickets.message-attachments.download', [$ticketId, $this->id]);
            },
        );
    }

    /**
     * Get the size in KB
     */
    protected function sizeInKb(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->size / 1024, 2),
        );
    }
}
