<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vista guardada de la bandeja "Emails enviados" — clon deliberado del
 * patrón ya usado por Modules\Helpdesk\Models\ConversationView (mismo
 * esquema/scopes), no una extensión de esa clase: los filtros de una
 * bandeja de emails no tienen nada que ver con los de conversaciones.
 */
class TicketMailView extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_mail_views';

    protected $fillable = [
        'name',
        'filters',
        'user_id',
        'is_public',
        'is_system',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_public' => 'boolean',
            'is_system' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $view) {
            if (is_null($view->order)) {
                $view->order = (static::where('user_id', $view->user_id)->max('order') ?? 0) + 1;
            }
        });
    }

    /**
     * User model vive en la conexión por defecto, no en 'helpdesk' — mismo
     * patrón que Ticket::assignee()/TicketMail::user().
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('is_public', true);
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function canDelete(int $userId): bool
    {
        return ! $this->is_system && $this->user_id === $userId;
    }
}
