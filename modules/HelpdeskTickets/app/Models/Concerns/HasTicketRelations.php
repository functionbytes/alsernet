<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketFollowup;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketScheduledReply;
use Modules\HelpdeskTickets\Models\TicketSideConversation;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;
use Modules\HelpdeskTickets\Models\TicketWatcher;

trait HasTicketRelations
{
    /**
     * Get the category of this ticket
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Get the status of this ticket
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    /**
     * Get the SLA policy applied to this ticket
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(TicketSlaPolicy::class, 'sla_policy_id');
    }

    /**
     * Conversación de origen (inbox/chat/social) desde la que se creó el ticket,
     * si nació de una. Ambas tablas viven en la conexión helpdesk.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Categoría sugerida por la IA (aún no aplicada), para mostrarla en el panel
     * con la opción de aplicarla en un clic.
     */
    public function aiSuggestedCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ai_suggested_category_id');
    }

    /**
     * Get the group assigned to this ticket
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Get the assignee (support agent)
     * Note: User model is in the default connection, not helpdesk
     */
    public function assignee()
    {
        // Create a User instance with the correct database connection
        $instance = (new User)->setConnection(null); // null uses the model's default connection

        // Create the BelongsTo relationship with the properly connected instance
        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            'assignee_id',
            'id',
            'assignee'
        );
    }

    /**
     * Get all messages/items in this ticket
     */
    public function items(): HasMany
    {
        return $this->hasMany(TicketItem::class, 'ticket_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Emails (inbound/outbound) linked to this ticket.
     */
    public function mails(): HasMany
    {
        return $this->hasMany(TicketMail::class, 'ticket_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get only messages (not system events)
     */
    public function messages()
    {
        return $this->items()
            ->where('type', 'message');
    }

    /**
     * Último mensaje del hilo, para la tercera línea de la fila del listado
     * ("solicitud de documentación enviada al cliente" en el mockup).
     *
     * latestOfMany() lo resuelve con una subconsulta única para toda la
     * página, en vez de un SELECT por fila: la lista carga 30 tickets y
     * cargar items() entero para quedarse con el último sería traer el hilo
     * completo de cada uno.
     */
    public function lastMessage(): HasOne
    {
        // Sin mensajes de sistema (ni agente ni cliente: acuse automático,
        // seguimientos): la fila
        // del listado enseñaba "Hemos recibido su solicitud…" en casi todos
        // los tickets en vez de lo último que dijo el cliente o el agente.
        return $this->hasOne(TicketItem::class, 'ticket_id')
            ->ofMany(['id' => 'max'], fn ($q) => $q->where('type', 'message')
                ->where(fn ($w) => $w->whereNotNull('user_id')->orWhereNotNull('author_id')));
    }

    /**
     * Último correo saliente del ticket, para el chip de entrega de la
     * cabecera del detalle ("Email entregado" / "Email rebotado"). Misma
     * estrategia que lastMessage(): una subconsulta, no un SELECT por fila.
     */
    public function lastOutboundMail(): HasOne
    {
        return $this->hasOne(TicketMail::class, 'ticket_id')
            ->where('direction', 'outbound')
            ->latestOfMany();
    }

    /**
     * Get only system events
     */
    public function events()
    {
        return $this->items()
            ->where('type', '!=', 'message');
    }

    /**
     * Get all time entries logged for this ticket.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TicketTimeEntry::class);
    }

    /**
     * Get history records for this ticket
     */
    public function history(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }

    /**
     * Get users watching this ticket
     */
    public function watchers(): HasMany
    {
        return $this->hasMany(TicketWatcher::class, 'ticket_id');
    }

    /**
     * Get SLA breaches for this ticket
     */
    public function slaBreaches(): HasMany
    {
        return $this->hasMany(TicketSlaBreach::class, 'ticket_id');
    }

    /**
     * Links where this ticket is the source
     */
    public function links(): HasMany
    {
        return $this->hasMany(TicketLink::class, 'ticket_id');
    }

    /**
     * Links where this ticket is the target
     */
    public function linkedBy(): HasMany
    {
        return $this->hasMany(TicketLink::class, 'linked_ticket_id');
    }

    /**
     * Tickets bloqueantes que siguen ABIERTOS: este ticket no debería cerrarse
     * mientras existan. Cubre las dos direcciones del enlace:
     *  - este ticket declara `blocked_by` a otro (links)
     *  - otro ticket declara que `blocks` a este (linkedBy)
     *
     * @return Collection<int, Ticket>
     */
    public function openBlockers(): Collection
    {
        $blockedByOpen = $this->links()
            ->where('link_type', 'blocked_by')
            ->whereHas('linkedTicket', fn ($q) => $q->whereNull('closed_at'))
            ->with('linkedTicket:id,ticket_number,subject')
            ->get()
            ->map(fn (TicketLink $l) => $l->linkedTicket);

        $blocksThisOpen = $this->linkedBy()
            ->where('link_type', 'blocks')
            ->whereHas('ticket', fn ($q) => $q->whereNull('closed_at'))
            ->with('ticket:id,ticket_number,subject')
            ->get()
            ->map(fn (TicketLink $l) => $l->ticket);

        // Subtickets abiertos: el padre no se da por cerrado con trabajo
        // derivado pendiente (24-sep-2026).
        $openSubtickets = $this->linkedBy()
            ->where('link_type', 'subticket_of')
            ->whereHas('ticket', fn ($q) => $q->whereNull('closed_at'))
            ->with('ticket:id,ticket_number,subject')
            ->get()
            ->map(fn (TicketLink $l) => $l->ticket);

        return $blockedByOpen->merge($blocksThisOpen)->merge($openSubtickets)->filter()->unique('id')->values();
    }

    public function followups(): HasMany
    {
        return $this->hasMany(TicketFollowup::class, 'ticket_id')->orderBy('scheduled_at');
    }

    public function scheduledReplies(): HasMany
    {
        return $this->hasMany(TicketScheduledReply::class, 'ticket_id')->orderBy('deliver_at');
    }

    public function sideConversations(): HasMany
    {
        return $this->hasMany(TicketSideConversation::class, 'ticket_id')->latest();
    }
}
