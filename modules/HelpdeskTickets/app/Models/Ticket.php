<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Concerns\HasMessageThread;
use Modules\HelpdeskTickets\Database\Factories\TicketFactory;
use Modules\HelpdeskTickets\Models\Concerns\HasCustomAttributes;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketActivityLog;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketLifecycle;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketNumber;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketPresentation;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketRelations;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketScopes;
use Modules\HelpdeskTickets\Models\Concerns\HasTicketSla;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasCustomAttributes, HasFactory, HasMessageThread, HasTicketActivityLog, HasTicketLifecycle, HasTicketNumber, HasTicketPresentation, HasTicketRelations, HasTicketScopes, HasTicketSla, LogsActivity, SoftDeletes;

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
        'cc_emails',
        'assigned_at',
        'closed_at',
        'close_reason',
        'close_root_cause',
        'close_summary',
        'close_skip_survey',
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
        'ai_suggested_category_confidence',
        'ai_suggested_priority',
        'ai_suggested_priority_confidence',
        'customer_sentiment_avg',
        'detected_language',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'tags' => 'array',
            'cc_emails' => 'array',
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
            'close_skip_survey' => 'boolean',
            'rating' => 'integer',
            'rated_at' => 'datetime',
            'escalated_at' => 'datetime',
            'escalation_count' => 'integer',
            'sla_paused_duration_minutes' => 'integer',
            'ai_suggested_category_id' => 'integer',
            'ai_suggested_category_confidence' => 'decimal:2',
            'ai_suggested_priority_confidence' => 'decimal:2',
            'customer_sentiment_avg' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Atributos vigilados por LogsActivity — misma lista usada tanto en
     * logOnly() como para calcular el diff legible en describeUpdateEvent().
     * Fuente única para que ambos no puedan divergir.
     *
     * @var array<int, string>
     */
    private const ACTIVITY_LOGGED_ATTRIBUTES = [
        'subject', 'status_id', 'priority', 'category_id',
        'assignee_id', 'group_id', 'custom_fields', 'tags',
        'closed_at', 'resolved_at',
    ];

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
