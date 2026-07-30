<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Concerns\HasMessageThread;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskTickets\Database\Factories\TicketFactory;
use Modules\HelpdeskTickets\Models\Concerns\HasCustomAttributes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasCustomAttributes, HasFactory, HasMessageThread, LogsActivity, SoftDeletes;

    /**
     * Bootstrap contextual color per priority slug (single source of truth for
     * badges in views/JSON payloads).
     *
     * @var array<string, string>
     */
    public const PRIORITY_COLORS = [
        'urgent' => 'danger',
        'high' => 'warning',
        'normal' => 'info',
        'low' => 'secondary',
    ];

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_tickets';

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'conversation_id',
        'category_id',
        'status_id',
        'sla_policy_id',
        'group_id',
        'assignee_id',
        'subject',
        'description',
        'priority',
        'source',
        'custom_fields',
        'tags',
        'assigned_at',
        'closed_at',
        'resolved_at',
        'first_response_at',
        'last_message_at',
        'last_activity_at',
        'sla_first_response_due_at',
        'sla_next_response_due_at',
        'sla_resolution_due_at',
        'sla_first_response_breached',
        'sla_next_response_breached',
        'sla_resolution_breached',
        'sla_paused_at',
        'sla_paused_duration_minutes',
        'is_archived',
        'snoozed_until',
        'snoozed_by',
        'rating',
        'rating_comment',
        'rating_reason',
        'rated_at',
        'escalated_at',
        'escalation_count',
        'ai_suggested_category_id',
        'ai_suggested_priority',
        'customer_sentiment_avg',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'tags' => 'array',
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_message_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'sla_first_response_due_at' => 'datetime',
            'sla_next_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_first_response_breached' => 'boolean',
            'sla_next_response_breached' => 'boolean',
            'sla_resolution_breached' => 'boolean',
            'is_archived' => 'boolean',
            'snoozed_until' => 'datetime',
            'rating' => 'integer',
            'rated_at' => 'datetime',
            'escalated_at' => 'datetime',
            'escalation_count' => 'integer',
            'sla_paused_duration_minutes' => 'integer',
            'ai_suggested_category_id' => 'integer',
            'customer_sentiment_avg' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get activity log options configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'subject', 'status_id', 'priority', 'category_id',
                'assignee_id', 'group_id', 'custom_fields', 'tags',
                'closed_at', 'resolved_at',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $eventName) {
                return match ($eventName) {
                    'created' => 'Ticket creado',
                    'updated' => 'Ticket actualizado',
                    'deleted' => 'Ticket eliminado',
                    default => $eventName,
                };
            })
            ->dontSubmitEmptyLogs();
    }

    /**
     * Autogenera el número de ticket al crear si el caller no lo fijó, para que
     * cualquier vía de creación (TicketService, ingesta de emails, tests) obtenga
     * uno válido sin depender de un default en BD.
     */
    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Generate unique ticket number (TCK-YYYY-#####)
     */
    public static function generateTicketNumber(): string
    {
        $year = now()->year;
        $prefix = "TCK-{$year}-";

        // Get the last ticket number for this year
        $lastTicket = static::where('ticket_number', 'like', "{$prefix}%")
            ->orderBy('ticket_number', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastTicket) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($lastTicket->ticket_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            // First ticket of the year
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

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
     * Bootstrap contextual color for the ticket priority badge.
     */
    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? 'secondary';
    }

    /**
     * Get total logged time in minutes.
     */
    public function getTotalTimeMinutesAttribute(): int
    {
        return (int) $this->timeEntries()->sum('minutes');
    }

    /**
     * Get total logged time as a human-readable string.
     */
    public function getFormattedTotalTimeAttribute(): string
    {
        $total = $this->total_time_minutes;
        $hours = intdiv($total, 60);
        $mins = $total % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$mins}m";
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

        return $blockedByOpen->merge($blocksThisOpen)->filter()->unique('id')->values();
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

    /**
     * Scope: Get open tickets
     */
    public function scopeOpen($query)
    {
        $openIds = Cache::remember('helpdesk:open-status-ids', 3600, fn () => TicketStatus::where('is_open', true)->pluck('id')->toArray());

        return $query->whereIn('status_id', $openIds);
    }

    /**
     * Scope: tickets pospuestos (snooze activo — reaparecen en el futuro).
     */
    public function scopeSnoozed(Builder $query): Builder
    {
        return $query->whereNotNull('snoozed_until')->where('snoozed_until', '>', now());
    }

    /**
     * Scope: excluye los pospuestos de las colas activas (snooze vencido o nulo
     * cuenta como no-pospuesto). Úsalo en los listados por defecto.
     */
    public function scopeNotSnoozed(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    /**
     * Scope: Get closed tickets
     */
    public function scopeClosed($query)
    {
        $closedIds = Cache::remember('helpdesk:closed-status-ids', 3600, fn () => TicketStatus::where('is_open', false)->pluck('id')->toArray());

        return $query->whereIn('status_id', $closedIds);
    }

    /**
     * Scope: Get resolved tickets
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope: Get tickets by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Get tickets by source
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Get tickets with SLA breaches
     */
    public function scopeSlaBreach($query)
    {
        return $query->where(function ($q) {
            $q->where('sla_first_response_breached', true)
                ->orWhere('sla_next_response_breached', true)
                ->orWhere('sla_resolution_breached', true);
        });
    }

    /**
     * Scope: Get tickets near SLA breach (within threshold)
     */
    public function scopeSlaWarning($query, $minutesThreshold = 30)
    {
        $now = Carbon::now();
        $warningTime = $now->copy()->addMinutes($minutesThreshold);

        return $query->where(function ($q) use ($now, $warningTime) {
            $q->whereBetween('sla_first_response_due_at', [$now, $warningTime])
                ->orWhereBetween('sla_next_response_due_at', [$now, $warningTime])
                ->orWhereBetween('sla_resolution_due_at', [$now, $warningTime]);
        });
    }

    /**
     * Scope: Tickets past their SLA resolution due date
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNull('closed_at');
    }

    /**
     * Scope: Tickets needing attention — SLA breached OR unassigned
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereNull('closed_at')
            ->where(function (Builder $q) {
                $q->where('sla_resolution_breached', true)
                    ->orWhereNull('assignee_id');
            });
    }

    /**
     * Scope: Filter by date range on created_at
     */
    public function scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Filter by group
     */
    public function scopeAssignedToGroup(Builder $query, int $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope: Search by ticket number, subject or customer name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('ticket_number', 'like', "%{$term}%")
            ->orWhere('subject', 'like', "%{$term}%")
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%"));
    }

    /**
     * Check if ticket is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Check if SLA is currently paused
     */
    public function isSlaPaused(): bool
    {
        return $this->sla_paused_at !== null;
    }

    /**
     * Get unread messages count for a user
     */
    public function getUnreadCountForUser($userId): int
    {
        return $this->messages()
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))
            ->count();
    }

    /**
     * Calculate SLA due dates based on policy
     */
    public function calculateSlaDueDates(): self
    {
        if (! $this->slaPolicy) {
            return $this;
        }

        $policy = $this->slaPolicy;
        $now = Carbon::now();

        // Get priority multiplier
        $priorityMultipliers = $policy->priority_multipliers ?? [
            'urgent' => 0.25,
            'high' => 0.5,
            'normal' => 1.0,
            'low' => 2.0,
        ];
        $multiplier = $priorityMultipliers[$this->priority] ?? 1.0;

        // Calculate first response due date (if not already responded)
        if (! $this->first_response_at && $policy->first_response_time) {
            $minutes = (int) ($policy->first_response_time * $multiplier);
            $this->sla_first_response_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // Calculate next-response due date (agente debe responder de nuevo tras
        // una réplica del cliente). Antes la columna sla_next_response_due_at
        // existía pero nunca se rellenaba: el vencimiento de siguiente respuesta
        // quedaba sin control.
        if ($policy->next_response_time) {
            $minutes = (int) ($policy->next_response_time * $multiplier);
            $this->sla_next_response_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // Calculate resolution due date
        if ($policy->resolution_time) {
            $minutes = (int) ($policy->resolution_time * $multiplier);
            $this->sla_resolution_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        $this->saveQuietly();

        return $this;
    }

    /**
     * Calculate business time (respecting business hours if enabled)
     */
    protected function calculateBusinessTime(Carbon $start, int $minutes, TicketSlaPolicy $policy): Carbon
    {
        if (! $policy->business_hours_only) {
            return $start->copy()->addMinutes($minutes);
        }

        // Parse business hours from policy
        $businessHours = $policy->business_hours ?? [
            'monday' => ['start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00'],
            'thursday' => ['start' => '09:00', 'end' => '17:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
        ];

        $current = $start->copy()->setTimezone($policy->timezone ?? 'UTC');
        $remainingMinutes = $minutes;

        // Festivos del calendario de negocio (dependencia blanda con HelpdeskSla:
        // sin ese módulo, el cálculo sigue sólo con días de la semana).
        $calculator = class_exists(BusinessHoursCalculator::class)
            ? app(BusinessHoursCalculator::class)
            : null;
        $holidays = $calculator?->holidays() ?? ['recurring' => [], 'dates' => []];

        while ($remainingMinutes > 0) {
            $dayOfWeek = strtolower($current->format('l'));

            // Skip if not a business day or a holiday
            if (! isset($businessHours[$dayOfWeek]) || ($calculator && $calculator->isHoliday($current, $holidays))) {
                $current->addDay()->setTime(0, 0);

                continue;
            }

            $dayHours = $businessHours[$dayOfWeek];
            [$startHour, $startMinute] = explode(':', $dayHours['start']);
            [$endHour, $endMinute] = explode(':', $dayHours['end']);

            $dayStart = $current->copy()->setTime((int) $startHour, (int) $startMinute);
            $dayEnd = $current->copy()->setTime((int) $endHour, (int) $endMinute);

            // If current time is before business hours, move to start
            if ($current->lessThan($dayStart)) {
                $current = $dayStart->copy();
            }

            // If current time is after business hours, move to next day
            if ($current->greaterThanOrEqualTo($dayEnd)) {
                $current->addDay()->setTime(0, 0);

                continue;
            }

            // Calculate available minutes in this business day
            $availableMinutes = $current->diffInMinutes($dayEnd);

            if ($availableMinutes >= $remainingMinutes) {
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutes;
                $current->addDay()->setTime(0, 0);
            }
        }

        return $current;
    }

    /**
     * Pause SLA timer (when status stops SLA)
     */
    public function pauseSla(): self
    {
        if (! $this->isSlaPaused() && $this->status?->stops_sla_timer) {
            $this->update(['sla_paused_at' => now()]);
        }

        return $this;
    }

    /**
     * Resume SLA timer
     */
    public function resumeSla(): self
    {
        if ($this->isSlaPaused()) {
            $pausedMinutes = $this->sla_paused_at->diffInMinutes(now());
            $totalPausedMinutes = $this->sla_paused_duration_minutes + $pausedMinutes;

            // Extend all SLA due dates by the paused duration
            $updates = [
                'sla_paused_at' => null,
                'sla_paused_duration_minutes' => $totalPausedMinutes,
            ];

            if ($this->sla_first_response_due_at) {
                $updates['sla_first_response_due_at'] = $this->sla_first_response_due_at->addMinutes($pausedMinutes);
            }
            if ($this->sla_next_response_due_at) {
                $updates['sla_next_response_due_at'] = $this->sla_next_response_due_at->addMinutes($pausedMinutes);
            }
            if ($this->sla_resolution_due_at) {
                $updates['sla_resolution_due_at'] = $this->sla_resolution_due_at->addMinutes($pausedMinutes);
            }

            $this->update($updates);
        }

        return $this;
    }

    /**
     * Assign ticket to agent
     */
    public function assignTo($userId): self
    {
        $this->update([
            'assignee_id' => $userId,
            'assigned_at' => now(),
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'assigned',
            'user_id' => $userId,
            'body' => "Ticket assigned to {$this->assignee?->name}",
            'metadata' => ['assignee_id' => $userId],
        ]);

        return $this;
    }

    /**
     * Close ticket
     */
    public function close(): self
    {
        $closedStatus = Cache::remember('helpdesk:closed-status', 3600, fn () => TicketStatus::where('is_open', false)->orderBy('order')->first());

        $this->update([
            'status_id' => $closedStatus->id ?? $this->status_id,
            'closed_at' => now(),
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'closed',
            'body' => 'Ticket closed',
        ]);

        return $this;
    }

    /**
     * Resolve ticket
     */
    public function resolve(): self
    {
        $this->update([
            'resolved_at' => now(),
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'status_change',
            'body' => 'Ticket resolved',
            'metadata' => ['resolved_at' => now()->toIso8601String()],
        ]);

        return $this;
    }

    /**
     * Reopen ticket
     */
    public function reopen(): self
    {
        $openStatus = Cache::remember('helpdesk:open-status', 3600, fn () => TicketStatus::where('is_open', true)->orderBy('order')->first());

        $this->update([
            'status_id' => $openStatus->id ?? $this->status_id,
            'closed_at' => null,
            'resolved_at' => null,
        ]);

        // Recalculate SLA if policy exists
        if ($this->sla_policy_id) {
            $this->calculateSlaDueDates();
        }

        // Create system event
        $this->items()->create([
            'type' => 'reopened',
            'body' => 'Ticket reopened',
        ]);

        return $this;
    }

    /**
     * Get time to first response (in minutes)
     */
    public function getTimeToFirstResponse(): ?int
    {
        if (! $this->first_response_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->first_response_at);
    }

    /**
     * Get time to resolution (in minutes)
     */
    public function getTimeToResolution(): ?int
    {
        if (! $this->resolved_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at) - $this->sla_paused_duration_minutes;
    }

    /**
     * Get ticket duration (if closed)
     */
    public function getDuration(): int
    {
        $end = $this->closed_at ?? now();

        return $this->created_at->diffInMinutes($end);
    }

    /**
     * Get message count
     */
    public function getMessageCount(): int
    {
        return $this->messages()->count();
    }

    /**
     * Get latest message
     */
    public function getLatestMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Check if ticket has SLA breach
     */
    public function hasSlaBreach(): bool
    {
        return $this->sla_first_response_breached
            || $this->sla_next_response_breached
            || $this->sla_resolution_breached;
    }

    /**
     * Get the number of days this ticket has been open.
     */
    public function getDaysOpenAttribute(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }

    /**
     * Determine whether the ticket is currently overdue on SLA resolution.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->sla_resolution_due_at !== null
            && $this->sla_resolution_due_at->isPast()
            && $this->closed_at === null;
    }

    /**
     * SLA status for list badges: on_track, warning, breached, none.
     *
     * Warning fires when less than 25% of the SLA window remains, or the
     * effective due date is under 2 hours away. Uses sla_resolution_breached
     * and sla_resolution_due_at, falling back to the pause-aware effective
     * due date when the timer is paused.
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->sla_resolution_due_at === null) {
            return 'none';
        }

        if ($this->sla_resolution_breached) {
            return 'breached';
        }

        $due = $this->slaEffectiveDueDate();

        if ($due === null) {
            return 'none';
        }

        $now = Carbon::now();

        if ($due->isPast()) {
            return 'breached';
        }

        $minutesLeft = $now->diffInMinutes($due, false);

        if ($minutesLeft < 120) {
            return 'warning';
        }

        $totalMinutes = $this->created_at?->diffInMinutes($due, false) ?? 0;

        if ($totalMinutes > 0 && ($minutesLeft / $totalMinutes) < 0.25) {
            return 'warning';
        }

        return 'on_track';
    }

    /**
     * Effective SLA resolution due date, accounting for any active pause.
     */
    public function slaEffectiveDueDate(): ?Carbon
    {
        if ($this->sla_resolution_due_at === null) {
            return null;
        }

        if ($this->sla_paused_at !== null) {
            $pausedMinutes = $this->sla_paused_at->diffInMinutes(Carbon::now());

            return $this->sla_resolution_due_at->copy()->addMinutes($pausedMinutes);
        }

        return $this->sla_resolution_due_at;
    }

    /**
     * Get SLA status (ok, warning, breach)
     */
    public function getSlaStatus(): string
    {
        if ($this->hasSlaBreach()) {
            return 'breach';
        }

        $now = Carbon::now();
        $warningThreshold = 30; // 30 minutes before due

        $dueDates = array_filter([
            $this->sla_first_response_due_at,
            $this->sla_next_response_due_at,
            $this->sla_resolution_due_at,
        ]);

        foreach ($dueDates as $dueDate) {
            if ($dueDate && $dueDate->diffInMinutes($now, false) <= $warningThreshold) {
                return 'warning';
            }
        }

        return 'ok';
    }

    /**
     * Laravel no puede adivinar la ruta de la factory a partir del namespace
     * del modulo (Modules\X\Models\Y no encaja con la convencion App\Models\Y
     * que usa la resolucion por defecto de HasFactory) — sin este override,
     * Ticket::factory() lanzaba "Class ...\Models\TicketFactory not found".
     */
    protected static function newFactory(): TicketFactory
    {
        return new TicketFactory;
    }
}
