<?php

namespace Modules\HelpdeskEmailLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vista guardada del log de emails — mismo patrón ya usado por
 * Modules\Helpdesk\Models\ConversationView y
 * Modules\HelpdeskTickets\Models\TicketMailView (clon deliberado, no
 * herencia: los filtros de cada bandeja no tienen nada que ver entre sí).
 * A diferencia de esos dos, esta vive en la conexión por defecto de la app
 * (igual que EmailLog, sin `protected $connection`) — no en 'helpdesk'.
 */
class EmailLogView extends Model
{
    protected $table = 'email_log_views';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
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
