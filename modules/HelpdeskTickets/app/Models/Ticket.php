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
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskTickets\Database\Factories\TicketFactory;
use Modules\HelpdeskTickets\Models\Concerns\HasCustomAttributes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasCustomAttributes, HasFactory, LogsActivity, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_tickets';

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'category_id',
        'status_id',
        'sla_policy_id',
        'group_id',
        'assignee_id',
        'title',
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
        'sla_first_response_due_at',
        'sla_next_response_due_at',
        'sla_resolution_due_at',
        'sla_first_response_breached',
        'sla_next_response_breached',
        'sla_resolution_breached',
        'sla_paused_at',
        'sla_paused_duration_minutes',
        'is_archived',
        'rating',
        'rating_comment',
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
            'sla_first_response_due_at' => 'datetime',
            'sla_next_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_first_response_breached' => 'boolean',
            'sla_next_response_breached' => 'boolean',
            'sla_resolution_breached' => 'boolean',
            'is_archived' => 'boolean',
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
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-generate ticket number
        static::creating(function ($ticket) {
            if (! $ticket->ticket_number) {
                $ticket->ticket_number = static::generateTicketNumber();
            }

            // Set default status if not provided
            if (! $ticket->status_id) {
                $defaultStatus = Cache::remember('helpdesk:default-status', 3600, fn () => TicketStatus::where('is_default', true)->first());
                if ($defaultStatus) {
                    $ticket->status_id = $defaultStatus->id;
                }
            }
        });

        // Create TicketHistory on creation
        static::created(function ($ticket) {
            if ($ticket->sla_policy_id) {
                $ticket->calculateSlaDueDates();
            }

            // Log initial creation
            TicketHistory::logTicketCreated($ticket, auth()->user());
        });

        // Track field changes and create TicketHistory records
        static::updated(function ($ticket) {
            $changes = $ticket->getDirty();

            foreach ($changes as $field => $newValue) {
                // Skip timestamp fields
                if (in_array($field, ['updated_at', 'created_at', 'deleted_at'])) {
                    continue;
                }

                $oldValue = $ticket->getOriginal($field);

                // Handle status changes specially
                if ($field === 'status_id' && $oldValue !== $newValue) {
                    $statusIds = array_filter([$oldValue, $newValue]);
                    $statuses = TicketStatus::whereIn('id', $statusIds)->get()->keyBy('id');
                    $oldStatus = $statuses[$oldValue] ?? null;
                    $newStatus = $statuses[$newValue] ?? null;

                    if ($oldStatus && $newStatus) {
                        TicketHistory::logStatusChange(
                            $ticket,
                            $oldStatus,
                            $newStatus,
                            auth()->user()
                        );
                    }
                } elseif ($field === 'assignee_id') {
                    // Handle assignment changes
                    if ($newValue && ! $oldValue) {
                        TicketHistory::logAssigned(
                            $ticket,
                            auth()->user(),
                            User::find($newValue)
                        );
                    } elseif (! $newValue && $oldValue) {
                        TicketHistory::logUnassigned($ticket, auth()->user());
                    }
                } else {
                    // Log generic field changes
                    TicketHistory::logFieldChange(
                        $ticket,
                        $field,
                        $oldValue,
                        $newValue,
                        auth()->user()
                    );
                }
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
     * Get the customer that owns this ticket
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the category of this ticket
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function getCategoryAttribute($value)
    {
        if (! empty($this->attributes['category_id'])) {
            if (! $this->relationLoaded('category')) {
                $this->load('category');
            }

            return $this->getRelation('category');
        }

        return $value;
    }

    /**
     * Get the status of this ticket
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function getStatusAttribute($value)
    {
        if (! empty($this->attributes['status_id'])) {
            if (! $this->relationLoaded('status')) {
                $this->load('status');
            }

            return $this->getRelation('status');
        }

        return $value;
    }

    /**
     * Get the SLA policy applied to this ticket
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(TicketSlaPolicy::class, 'sla_policy_id');
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

    public function followups(): HasMany
    {
        return $this->hasMany(TicketFollowup::class, 'ticket_id')->orderBy('scheduled_at');
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
     * Scope: Get tickets assigned to a user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    /**
     * Scope: Get unassigned tickets
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assignee_id');
    }

    /**
     * Scope: Get tickets by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
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
     * Scope: Get archived tickets
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Get active tickets
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
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
     * Check if ticket is open
     */
    public function isOpen(): bool
    {
        return $this->status && $this->status->is_open;
    }

    /**
     * Check if ticket is closed
     */
    public function isClosed(): bool
    {
        return ! $this->isOpen();
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

        while ($remainingMinutes > 0) {
            $dayOfWeek = strtolower($current->format('l'));

            // Skip if not a business day
            if (! isset($businessHours[$dayOfWeek])) {
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
     * Archive ticket
     */
    public function archive(): self
    {
        $this->update(['is_archived' => true]);

        return $this;
    }

    /**
     * Unarchive ticket
     */
    public function unarchive(): self
    {
        $this->update(['is_archived' => false]);

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

        return $this->first_response_at->diffInMinutes($this->created_at);
    }

    /**
     * Get time to resolution (in minutes)
     */
    public function getTimeToResolution(): ?int
    {
        if (! $this->resolved_at) {
            return null;
        }

        return $this->resolved_at->diffInMinutes($this->created_at) - $this->sla_paused_duration_minutes;
    }

    /**
     * Get ticket duration (if closed)
     */
    public function getDuration(): int
    {
        $end = $this->closed_at ?? now();

        return $end->diffInMinutes($this->created_at);
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
}
