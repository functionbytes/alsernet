<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Database\Factories\TicketMessageFactory;

class TicketMessage extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_messages';

    protected static function newFactory(): TicketMessageFactory
    {
        return TicketMessageFactory::new();
    }

    protected $fillable = [
        'uid',
        'ticket_id',
        'user_id',
        'is_internal',
        'message',
        'message_html',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    /**
     * Ticket this message belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * User who sent the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Attachments for this message
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }
}
