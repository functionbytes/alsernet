<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Helpdesk\Concerns\HasMessageThread;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskTickets\Database\Factories\TicketFactory;
use Modules\HelpdeskTickets\Http\Controllers\SharedTicketController;
use Modules\HelpdeskTickets\Models\Concerns\HasCustomAttributes;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\SlaService;
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
     * Get activity log options configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(self::ACTIVITY_LOGGED_ATTRIBUTES)
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $eventName) {
                // $this aquí es el propio Ticket que disparó el evento: un
                // closure "function(){}" (no arrow, no static) definido
                // dentro de un método de instancia se vincula
                // automáticamente a $this en PHP — no hace falta bindTo().
                return match ($eventName) {
                    'created' => 'Ticket creado',
                    'updated' => $this->describeUpdateEvent(),
                    'deleted' => 'Ticket eliminado',
                    default => $eventName,
                };
            })
            ->dontSubmitEmptyLogs();
    }

    /**
     * Descripción específica del cambio para la pestaña Actividad, a partir
     * del diff real que LogsActivity ya calcula vía logOnly()/logOnlyDirty()
     * — antes se descartaba por completo y la pestaña mostraba el mismo
     * texto estático "Ticket actualizado" en el 100% de las entradas de
     * cualquier ticket con más de un cambio, indistinguibles entre sí salvo
     * por el timestamp (bug real de QA: 20 y 12 entradas idénticas en 2
     * tickets de prueba).
     *
     * getDirty()/getOriginal() siguen siendo válidos aquí: el evento
     * Eloquent 'updated' (que dispara este closure vía
     * LogsActivity::bootLogsActivity()) se lanza DESDE
     * Model::performUpdate(), DESPUÉS del UPDATE en BD pero ANTES de
     * Model::finishSave()->syncOriginal() — el snapshot "original" todavía
     * tiene los valores previos al guardado.
     */
    private function describeUpdateEvent(): string
    {
        $dirty = array_intersect_key($this->getDirty(), array_flip(self::ACTIVITY_LOGGED_ATTRIBUTES));

        if ($dirty === []) {
            return 'Ticket actualizado';
        }

        $parts = [];

        if (array_key_exists('priority', $dirty)) {
            $parts[] = "Prioridad cambiada de '{$this->activityPriorityLabel($this->getOriginal('priority'))}' a '{$this->activityPriorityLabel($dirty['priority'])}'";
        }
        if (array_key_exists('status_id', $dirty)) {
            $parts[] = "Estado cambiado de '{$this->activityStatusLabel($this->getOriginal('status_id'))}' a '{$this->activityStatusLabel($dirty['status_id'])}'";
        }
        if (array_key_exists('category_id', $dirty)) {
            $parts[] = "Categoría cambiada de '{$this->activityCategoryLabel($this->getOriginal('category_id'))}' a '{$this->activityCategoryLabel($dirty['category_id'])}'";
        }
        if (array_key_exists('assignee_id', $dirty)) {
            $parts[] = "Asignación cambiada de '{$this->activityAssigneeLabel($this->getOriginal('assignee_id'))}' a '{$this->activityAssigneeLabel($dirty['assignee_id'])}'";
        }
        if (array_key_exists('group_id', $dirty)) {
            $parts[] = "Equipo cambiado de '{$this->activityGroupLabel($this->getOriginal('group_id'))}' a '{$this->activityGroupLabel($dirty['group_id'])}'";
        }
        if (array_key_exists('subject', $dirty)) {
            $parts[] = 'Asunto actualizado';
        }
        if (array_key_exists('tags', $dirty)) {
            $parts[] = 'Etiquetas actualizadas';
        }
        if (array_key_exists('custom_fields', $dirty)) {
            $parts[] = 'Campos personalizados actualizados';
        }
        if (array_key_exists('closed_at', $dirty)) {
            $parts[] = $dirty['closed_at'] ? 'Ticket cerrado' : 'Ticket reabierto';
        }
        if (array_key_exists('resolved_at', $dirty)) {
            $parts[] = $dirty['resolved_at'] ? 'Ticket marcado como resuelto' : 'Resolución de ticket revertida';
        }

        // Fallback: campo vigilado mutó pero no encaja en ningún caso de
        // arriba (no debería pasar salvo que ACTIVITY_LOGGED_ATTRIBUTES
        // crezca sin actualizar este método) — mismo texto genérico de
        // siempre en vez de una descripción vacía.
        return $parts === [] ? 'Ticket actualizado' : implode('; ', $parts);
    }

    private function activityPriorityLabel(mixed $value): string
    {
        return match ($value) {
            'urgent' => 'Urgente',
            'high' => 'Alta',
            'normal' => 'Normal',
            'low' => 'Baja',
            default => $value !== null ? (string) $value : '—',
        };
    }

    private function activityStatusLabel(mixed $id): string
    {
        if (! $id) {
            return '—';
        }

        return CatalogCacheService::statuses()->firstWhere('id', (int) $id)?->name ?? "#{$id}";
    }

    private function activityCategoryLabel(mixed $id): string
    {
        if (! $id) {
            return 'Sin categoría';
        }

        return CatalogCacheService::categories()->firstWhere('id', (int) $id)?->name ?? "#{$id}";
    }

    private function activityGroupLabel(mixed $id): string
    {
        if (! $id) {
            return 'Sin equipo';
        }

        return CatalogCacheService::groups()->firstWhere('id', (int) $id)?->name ?? "#{$id}";
    }

    /**
     * Memo por petición de activityAssigneeLabel() para los asignatarios que
     * ya no salen en CatalogCacheService::agents().
     *
     * @var array<int, string>
     */
    private static array $assigneeLabelCache = [];

    private function activityAssigneeLabel(mixed $id): string
    {
        if (! $id) {
            return 'Sin asignar';
        }

        $agent = CatalogCacheService::agents()->firstWhere('id', (int) $id);

        if ($agent) {
            return trim($agent->firstname.' '.$agent->lastname);
        }

        // CatalogCacheService::agents() solo incluye agentes con
        // available=true, así que un asignatario dado de baja o marcado como
        // no disponible no aparece ahí. Antes se caía a "Usuario #id", que
        // dejaba ilegible justo el historial de los tickets más antiguos
        // (que son los que más se consultan por auditoría). Se resuelve
        // contra la tabla de usuarios y se memoiza por petición: son ids
        // sueltos repetidos muchas veces dentro de un mismo historial, no un
        // listado, así que el coste real es una consulta por agente
        // desaparecido y por request.
        if (array_key_exists((int) $id, self::$assigneeLabelCache)) {
            return self::$assigneeLabelCache[(int) $id];
        }

        $user = User::select(['id', 'firstname', 'lastname', 'email'])->find((int) $id);
        $label = $user
            ? (trim($user->firstname.' '.$user->lastname) ?: (string) $user->email)
            : "Usuario #{$id}";

        return self::$assigneeLabelCache[(int) $id] = $label;
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

        // El lockForUpdate() de abajo SOLO surte efecto dentro de una
        // transacción: en autocommit, MySQL adquiere y suelta el lock en el
        // acto y dos creaciones simultáneas leen el mismo último número.
        // Siete de los once caminos de creación del módulo envolvían la
        // llamada en DB::transaction(); cuatro no (alta desde el panel de
        // agentes, ProcessRecurringTicketsJob, y las dos vías de
        // HelpdeskTicketBridgeService), y ahí el bloqueo era decorativo:
        // colisión contra el índice UNIQUE de ticket_number.
        //
        // Abrir aquí la transacción cubre los once de una vez y hace que
        // cualquier punto de creación futuro herede la protección sin tener
        // que acordarse.
        $connection = DB::connection(static::make()->getConnectionName());

        $generate = function () use ($prefix) {
            // withTrashed() es obligatorio: el índice UNIQUE de ticket_number
            // es a nivel de BD y no distingue soft-deleted — un ticket
            // fusionado/archivado-y-eliminado (Ticket::merge()/destroy())
            // sigue ocupando su número. Sin esto, generateTicketNumber() podía
            // "retroceder" tras el primer soft-delete y chocar con
            // UniqueConstraintViolationException (bug real encontrado probando
            // la ingesta de emails).
            //
            // El orden va por la parte NUMÉRICA, no por la cadena: ordenar el
            // string dejaba 'TCK-2026-100000' por debajo de 'TCK-2026-99999'
            // en cuanto se pasara de cinco cifras, y el contador retrocedía.
            $lastTicket = static::withTrashed()
                ->where('ticket_number', 'like', "{$prefix}%")
                ->orderByRaw('CAST(SUBSTRING(ticket_number, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
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
        };

        // Si el PDO ya está dentro de una transacción, el SELECT … FOR UPDATE
        // de arriba ya es efectivo y no hay que abrir otra. Se mira el PDO y no
        // transactionLevel() porque varias conexiones lógicas pueden compartir
        // el mismo PDO: los tests del módulo lo hacen a propósito
        // (Tests\Concerns\SharesHelpdeskPdo apunta "helpdesk" al PDO de
        // "mariadb" para que los FK entre ambas no se bloqueen entre sí), y ahí
        // Laravel cree que "helpdesk" está a nivel 0 mientras el PDO ya tiene
        // una transacción abierta — pedirle otra revienta con
        // "There is already an active transaction".
        return $connection->getPdo()->inTransaction()
            ? $generate()
            : $connection->transaction($generate);
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
        return $this->hasOne(TicketItem::class, 'ticket_id')
            ->where('type', 'message')
            ->latestOfMany();
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
     * Scope: excluye tickets archivados — misma condición por defecto que
     * TicketFilter::applyArchived() SIEMPRE aplica a la query real del
     * listado de Gestión de tickets. Fuente única para que
     * TicketsCrudController::tabCounts() (badges de tabs/vistas) cuente
     * exactamente lo mismo que esa lista, sin duplicar la condición a mano
     * en dos sitios (bug real de QA: un ticket archivado seguía sumando en
     * los badges pese a no poder verse nunca en ninguna pestaña de esa
     * pantalla).
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
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
    /**
     * @param  bool  $onlyMissing  no pisar los vencimientos que ya vengan
     *                             puestos. Lo usa TicketObserver::creating():
     *                             quien crea un ticket pasando una fecha de SLA
     *                             explícita —una importación, una migración, la
     *                             corrección de un caso concreto— la está
     *                             fijando a propósito, y sobrescribirla dejaba
     *                             el valor pedido en nada sin decir una palabra.
     *                             Los recálculos (cambio de política o de
     *                             prioridad) siguen sobrescribiendo, que es su
     *                             trabajo.
     */
    public function calculateSlaDueDates(bool $persist = true, bool $onlyMissing = false): self
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
        if (! $this->first_response_at && $policy->first_response_time
            && ! ($onlyMissing && $this->sla_first_response_due_at !== null)) {
            $minutes = (int) ($policy->first_response_time * $multiplier);
            $this->sla_first_response_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // Calculate next-response due date (agente debe responder de nuevo tras
        // una réplica del cliente). Antes la columna sla_next_response_due_at
        // existía pero nunca se rellenaba: el vencimiento de siguiente respuesta
        // quedaba sin control.
        if ($policy->next_response_time && ! ($onlyMissing && $this->sla_next_response_due_at !== null)) {
            $minutes = (int) ($policy->next_response_time * $multiplier);
            $this->sla_next_response_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // Calculate resolution due date
        if ($policy->resolution_time && ! ($onlyMissing && $this->sla_resolution_due_at !== null)) {
            $minutes = (int) ($policy->resolution_time * $multiplier);
            $this->sla_resolution_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // $persist = false para poder calcular desde TicketObserver::creating()
        // y que las fechas entren en el INSERT. Antes esto se llamaba siempre
        // desde created(), así que cada alta de ticket costaba dos escrituras
        // (INSERT + UPDATE) en el camino más caliente del módulo: la ingesta de
        // correo crea un ticket por mensaje entrante.
        if ($persist && $this->exists) {
            $this->saveQuietly();
        }

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
     *
     * Fachada sobre SlaService, que es el dueño de la aritmética de SLA. Se
     * mantiene el método porque lo llaman TicketUpdateService y las vistas; la
     * condición del estado (stops_sla_timer) es específica de esta vía y por
     * eso se queda aquí.
     */
    public function pauseSla(): self
    {
        if (! $this->isSlaPaused() && $this->status?->stops_sla_timer) {
            app(SlaService::class)->pauseSla($this);
        }

        return $this;
    }

    /**
     * Resume SLA timer. Ver pauseSla(): la implementación vive en SlaService.
     */
    public function resumeSla(): self
    {
        app(SlaService::class)->resumeSla($this);

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
            // fullName() y no ->name: el User de esta app no tiene atributo
            // 'name' (guarda firstname/lastname), así que la línea del hilo
            // salía literalmente "Ticket assigned to " sin nadie detrás.
            // Se resuelve por $userId y no por la relación $this->assignee,
            // que sigue cacheada con el agente ANTERIOR justo después del
            // update() de arriba.
            'body' => 'Ticket asignado a '.(User::find($userId)?->fullName() ?: 'un agente'),
            'metadata' => ['assignee_id' => $userId],
        ]);

        return $this;
    }

    /**
     * Close ticket
     *
     * $reason es una key de config('helpdesktickets.close_reasons') (o texto
     * libre si viene del "Otro motivo" del modal) — puede venir vacío cuando
     * se cierra desde bulk actions o el flujo de un agente IA, que no piden
     * motivo.
     *
     * Bug real encontrado al probar el modal "Cerrar ticket" (ago-2026): la
     * caché resolvía el status por is_open=false + order ASC, que da "En
     * Espera" (order=4, is_open=false) en vez de "Cerrado" (order=8, slug
     * closed) — "Cerrar ticket" dejaba el ticket en En Espera, no Cerrado.
     * Mismo criterio por slug que ya usa resolve() (ver su docblock).
     */
    /**
     * @param  array{root_cause?: ?string, summary?: ?string, skip_survey?: bool}  $analysis
     *                                                                                        Clasificación del cierre para los informes: causa raíz y resumen del
     *                                                                                        agente. Va aparte de $reason porque el motivo dice cómo acabó el
     *                                                                                        ticket y la causa raíz por qué existió.
     */
    public function close(?string $reason = null, array $analysis = []): self
    {
        $closedStatus = Cache::remember('helpdesk:closed-status', 3600, fn () => TicketStatus::where('slug', 'closed')->first());

        $this->update([
            'status_id' => $closedStatus->id ?? $this->status_id,
            'closed_at' => now(),
            'close_reason' => $reason ?: $this->close_reason,
            'close_root_cause' => $analysis['root_cause'] ?? $this->close_root_cause,
            'close_summary' => $analysis['summary'] ?? $this->close_summary,
            // ?? false al final: close_skip_survey es NOT NULL, y en una
            // instancia recién creada con Ticket::create() el atributo todavía
            // no está hidratado desde la BD — cerrarlo ahí mismo (lo hace la
            // unificación de duplicados) reventaba con "cannot be null".
            'close_skip_survey' => $analysis['skip_survey'] ?? $this->close_skip_survey ?? false,
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'closed',
            'body' => 'Ticket closed',
        ]);

        return $this;
    }

    /**
     * Resolve ticket.
     *
     * Bug real encontrado en QA de la pantalla "Gestión de tickets" (ago-2026):
     * este método solo marcaba resolved_at sin tocar status_id — a diferencia
     * de close()/reopen(), que sí resuelven y asignan el TicketStatus real.
     * Resultado: el botón "Resolver" (bulk y ficha) respondía success:true
     * pero el ticket seguía apareciendo "Abierto" en cualquier listado, porque
     * el estado visible se deriva de status_id, no de resolved_at. Mismo
     * patrón de caché que close()/reopen().
     */
    public function resolve(): self
    {
        $resolvedStatus = Cache::remember('helpdesk:resolved-status', 3600, fn () => TicketStatus::where('slug', 'resolved')->first());

        $this->update([
            'status_id' => $resolvedStatus->id ?? $this->status_id,
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
        return app(SlaService::class)->getEffectiveDueDate($this);
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
     * Slug "lógico" de estado para el frontend, a partir del TicketStatus
     * real. Mapea a los slugs canónicos que usa el JS (open, progress,
     * pending, resolved, closed) — antes vivía como closure inline en
     * index.blade.php; se extrae aquí para que SSR y el futuro JSON de
     * refetch (TicketsCrudController::index() con wantsJson()) no diverjan,
     * mismo patrón que TicketMail::toListRow().
     */
    public function statusSlug(): string
    {
        return static::canonicalStatusSlug($this->status);
    }

    /**
     * Misma normalización que statusSlug() pero sobre un TicketStatus suelto,
     * para poder agrupar el CATÁLOGO (una tabla de decenas de filas) en vez de
     * recorrer los tickets uno a uno. Es lo que permite a
     * TicketsCrudController::tabCounts() contar con una sola agregación SQL en
     * lugar de hidratar la tabla entera.
     */
    public static function canonicalStatusSlug(?TicketStatus $status): string
    {
        if (! $status) {
            return 'open';
        }
        $raw = $status->slug ?? str($status->name ?? '')->slug()->toString();
        $known = ['open', 'progress', 'pending', 'resolved', 'closed'];
        if (in_array($raw, $known, true)) {
            return $raw;
        }

        // El catálogo real (HelpdeskTicketStatusSeeder / gestión de estados
        // en Settings) admite más estados que los 5 "canónicos" que usan las
        // tabs/kanban del listado — se agrupan aquí para no perder ninguno
        // en un bucket erróneo (antes cualquier slug no reconocido caía
        // ciegamente en open/closed según is_open, metiendo "en espera del
        // cliente"/"en pausa" dentro de "Abiertos" en vez de "Pendientes").
        $aliases = [
            'waiting-customer' => 'pending',
            'waiting_customer' => 'pending',
            'on-hold' => 'pending',
            'on_hold' => 'pending',
            'escalated' => 'open',
            'reopened' => 'open',
            'new' => 'open',
        ];
        if (isset($aliases[$raw])) {
            return $aliases[$raw];
        }

        return ($status->is_open ?? true) ? 'open' : 'closed';
    }

    /**
     * Slug de origen normalizado para el badge de canal. Cualquier source no
     * reconocido se deja tal cual (nunca cae silenciosamente en 'email' —
     * bug real que hubo antes de extraer esto).
     */
    public function sourceSlug(): string
    {
        $known = ['email', 'widget', 'wa', 'fb', 'ig', 'whatsapp', 'facebook', 'instagram', 'agent', 'formulario', 'web_form'];
        $aliases = ['whatsapp' => 'wa', 'facebook' => 'fb', 'instagram' => 'ig'];
        $s = strtolower((string) $this->source);

        return $aliases[$s] ?? $s;
    }

    /**
     * Categoría cualitativa de urgencia SLA para colorear la fila (ok/warn/
     * breach) — distinta de getSlaStatusAttribute() (on_track/warning/
     * breached/none), que es la usada por el panel lateral; esta es más
     * simple y pensada solo para el punto de color de la lista.
     */
    public function slaRowKind(): string
    {
        if ($this->sla_resolution_breached || $this->sla_first_response_breached) {
            return 'breach';
        }
        $due = $this->sla_resolution_due_at;
        if (! $due) {
            return 'ok';
        }
        $minutes = now()->diffInMinutes($due, false);
        if ($minutes < 0) {
            return 'breach';
        }

        return $minutes < 60 ? 'warn' : 'ok';
    }

    /**
     * Texto corto de SLA para la fila de la lista ("2h 10m", "12m vencido").
     */
    public function slaRowText(): string
    {
        $due = $this->sla_resolution_due_at;
        if (! $due) {
            return $this->resolved_at ? 'resuelto' : '—';
        }
        // (int) es obligatorio aquí: en esta versión de Carbon,
        // diffInMinutes() devuelve float (p. ej. 29.767743866667) en vez de
        // minutos enteros — bug real que expuso el QA con datos de prueba
        // realistas ("29.767743866667m" en la fila del listado).
        $minutes = (int) now()->diffInMinutes($due, false);

        return $minutes < 0
            ? self::humanizeMinutes(abs($minutes)).' vencido'
            : self::humanizeMinutes($minutes);
    }

    /**
     * Minutos → "45m" / "2h 10m" / "3d 6h", el formato del listado.
     *
     * La rama de vencidos no escalaba y escupía el total en minutos crudos
     * ("1842m vencido", visto en el Kanban); ahora las dos direcciones pasan
     * por aquí y usan la misma escala.
     */
    private static function humanizeMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours.'h '.($minutes % 60).'m';
        }

        return intdiv($hours, 24).'d '.($hours % 24).'h';
    }

    /**
     * Plantillas de las URLs de acción del listado — se generan UNA sola vez
     * por respuesta (no por fila) y viajan en el contenedor
     * (data-ticket-url-templates en index.blade.php); tickets-app.js las
     * expande sustituyendo '__TICKET__' por el id real de cada ticket al
     * hidratar TKA.state.tickets. Antes cada fila de toListRow() llamaba a
     * route() ~27 veces con el mismo patrón salvo el id — con paginación de
     * 50, un refetch completo son ~1.350 generaciones de ruta redundantes.
     * Las que ya tenían un segundo placeholder para su sub-recurso
     * (__MACRO__/__NOTE__/__SIDE__/__FOLLOWUP__) lo conservan sin cambios.
     *
     * @return array<string, string>
     */
    public static function listRowUrlTemplates(): array
    {
        return [
            'url' => route('manager.helpdesk.tickets.show', ['ticket' => '__TICKET__']),
            'url_data' => route('manager.helpdesk.tickets.data', ['ticket' => '__TICKET__']),
            // Sonda del refresco automático; ver TicketDetailDataController::pulse().
            'url_pulse' => route('manager.helpdesk.tickets.pulse', ['ticket' => '__TICKET__']),
            'url_message_store' => route('manager.helpdesk.tickets.messages.store', ['ticket' => '__TICKET__']),
            'url_update' => route('manager.helpdesk.tickets.update', ['ticket' => '__TICKET__']),
            'url_close' => route('manager.helpdesk.tickets.close', ['ticket' => '__TICKET__']),
            'url_resolve' => route('manager.helpdesk.tickets.resolve', ['ticket' => '__TICKET__']),
            'url_reopen' => route('manager.helpdesk.tickets.reopen', ['ticket' => '__TICKET__']),
            'url_tags' => route('manager.helpdesk.tickets.tags', ['ticket' => '__TICKET__']),
            'url_snooze' => route('manager.helpdesk.tickets.snooze', ['ticket' => '__TICKET__']),
            'url_link' => route('manager.helpdesk.tickets.link', ['ticket' => '__TICKET__']),
            'url_merge' => route('manager.helpdesk.tickets.merge', ['ticket' => '__TICKET__']),
            'url_side_conversations_store' => route('manager.helpdesk.tickets.side-conversations.store', ['ticket' => '__TICKET__']),
            'url_side_conversations_message_template' => route('manager.helpdesk.tickets.side-conversations.messages.store', ['ticket' => '__TICKET__', 'sideConversation' => '__SIDE__']),
            'url_macro_apply_template' => route('manager.helpdesk.tickets.macros.apply', ['ticket' => '__TICKET__', 'macro' => '__MACRO__']),
            'url_followups_store' => route('manager.helpdesk.tickets.followups.store', ['ticket' => '__TICKET__']),
            'url_followup_destroy_template' => route('manager.helpdesk.tickets.followups.destroy', ['ticket' => '__TICKET__', 'followup' => '__FOLLOWUP__']),
            'url_followups_destroy_all' => route('manager.helpdesk.tickets.followups.destroy-all', ['ticket' => '__TICKET__']),
            'url_ai_apply' => route('manager.helpdesk.tickets.apply-ai-suggestion', ['ticket' => '__TICKET__']),
            // Borrador sugerido de la franja de IA del composer.
            'url_ai_suggest_reply' => route('manager.helpdesk.tickets.ai.suggest-reply', ['ticket' => '__TICKET__']),
            // Modal 46: candidatos a duplicado del mismo cliente.
            'url_duplicates' => route('manager.helpdesk.tickets.ai.duplicates', ['ticket' => '__TICKET__']),
            // Unificar duplicados (v2): resumen de cada ticket y unificación en
            // bloque. La v1 (url_duplicates + url_merge) se mantiene intacta.
            'url_unify_summary' => route('manager.helpdesk.tickets.unify.summary', ['ticket' => '__TICKET__']),
            'url_unify' => route('manager.helpdesk.tickets.unify', ['ticket' => '__TICKET__']),
            // Lista negra desde el propio ticket (correo y/o dominio) + borrado.
            'url_blacklist' => route('manager.helpdesk.tickets.blacklist', ['ticket' => '__TICKET__']),
            'url_note_destroy_template' => route('manager.helpdesk.tickets.notes.destroy', ['ticket' => '__TICKET__', 'note' => '__NOTE__']),
            'url_note_pin_template' => route('manager.helpdesk.tickets.notes.pin', ['ticket' => '__TICKET__', 'note' => '__NOTE__']),
            'url_note_color_template' => route('manager.helpdesk.tickets.notes.color', ['ticket' => '__TICKET__', 'note' => '__NOTE__']),
            'url_summary' => route('manager.helpdesk.tickets.summary', ['ticket' => '__TICKET__']),
            'url_watch' => route('manager.helpdesk.tickets.watch', ['ticket' => '__TICKET__']),
            'url_unwatch' => route('manager.helpdesk.tickets.unwatch', ['ticket' => '__TICKET__']),
            'url_destroy' => route('manager.helpdesk.tickets.destroy', ['ticket' => '__TICKET__']),
            'url_archive' => route('manager.helpdesk.tickets.archive', ['ticket' => '__TICKET__']),
            'url_send_csat' => route('manager.helpdesk.tickets.csat.send', ['ticket' => '__TICKET__']),
            // Modal 40: la valoración recibida y su contexto.
            'url_csat' => route('manager.helpdesk.tickets.csat.show', ['ticket' => '__TICKET__']),
            // Modal 50 "Traducir respuesta": traduce un texto suelto antes de
            // enviarlo (distinto de emails.translate, que traduce un correo ya
            // registrado).
            'url_translate_text' => route('manager.helpdesk.tickets.translate', ['ticket' => '__TICKET__']),
            // Modal 39: separar mensajes en un ticket nuevo.
            'url_split' => route('manager.helpdesk.tickets.split', ['ticket' => '__TICKET__']),
            // Modal 23: avisar a un agente presente en el ticket.
            'url_presence_nudge' => route('manager.helpdesk.tickets.presence.nudge', ['ticket' => '__TICKET__']),
            // Modal 25: pedidos PrestaShop del cliente, bajo demanda.
            'url_customer_orders' => route('manager.helpdesk.tickets.customer-360.orders', ['ticket' => '__TICKET__']),
            // Modal 32: enviar el enlace mágico de acceso al portal.
            'url_portal_send_access' => route('manager.helpdesk.tickets.portal.send-access', ['ticket' => '__TICKET__']),
        ];
    }

    /**
     * Contrato único de fila para el listado — lo usan tanto la hidratación
     * SSR de index.blade.php como el futuro JSON de refetch
     * (TicketsCrudController::index() con wantsJson()), igual que
     * TicketMail::toListRow() para la bandeja de emails. Tenerlo en un solo
     * sitio evita que SSR y AJAX diverjan en los nombres de campo.
     *
     * Las URLs de acción NO viajan aquí: son derivables del id y se generan
     * una sola vez como plantilla en listRowUrlTemplates(); tickets-app.js
     * las expande por fila. Solo queda url_shared_ticket, que es una URL
     * firmada (temporarySignedRoute) y por tanto no se puede plantillar.
     *
     * @return array<string, mixed>
     */
    /**
     * Texto de la tercera línea de la fila del listado: el último mensaje del
     * hilo o, a falta de él, la descripción original del ticket.
     */
    private function listSnippet(): ?string
    {
        $source = null;

        if ($this->relationLoaded('lastMessage') && $this->lastMessage) {
            $source = $this->lastMessage->body ?: $this->lastMessage->html_body;
        }

        // Sin mensajes en el hilo se cae a la descripción, pero en los
        // tickets de formulario ésa es el VOLCADO de campos
        // ("Firstname: …\nLastname: …\nPhone: …"): la fila del listado
        // acababa enseñando datos de contacto cortados a mitad en vez de
        // qué pide el cliente. Se busca primero el campo de texto libre del
        // propio formulario, que es el equivalente real al último mensaje.
        $source ??= $this->formMessageField();
        $source ??= $this->description;

        $clean = trim(strip_tags((string) $source));

        // Los saltos de línea del volcado se compactan con separador: si
        // aun así toca mostrarlo, que se lea de corrido y no se corte en
        // mitad de una etiqueta de campo.
        $clean = trim((string) preg_replace('/\s*\n+\s*/u', ' · ', $clean));

        return $clean === '' ? null : Str::limit($clean, 90);
    }

    /**
     * Texto libre que escribió el cliente en un formulario, si lo hay.
     *
     * Los formularios guardan cada campo por separado en custom_fields; solo
     * uno de ellos es el mensaje. Se prueban los nombres habituales y se
     * exige una longitud mínima para no confundir un "Motivo: Envío" (una
     * opción de un desplegable) con lo que el cliente escribió.
     */
    private function formMessageField(): ?string
    {
        $fields = $this->custom_fields;

        if (! is_array($fields) || $fields === []) {
            return null;
        }

        foreach (['message', 'mensaje', 'comment', 'comments', 'comentario', 'comentarios',
            'observations', 'observaciones', 'consulta', 'descripcion', 'description',
            'body', 'texto', 'pregunta'] as $key) {
            $value = $fields[$key] ?? null;

            if (is_string($value) && mb_strlen(trim($value)) >= 20) {
                return trim($value);
            }
        }

        return null;
    }

    public function toListRow(): array
    {
        $assignee = null;
        if ($this->assignee) {
            $name = $this->assignee->fullName() ?: 'Agente';
            $assignee = ['id' => $this->assignee->id, 'name' => $name];
        }

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject ?? $this->title,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority ?? 'normal',
            'source' => $this->sourceSlug(),
            'tags' => is_array($this->tags) ? array_values($this->tags) : [],
            'status_id' => $this->status_id,
            'status_slug' => $this->statusSlug(),
            'status_name' => $this->status?->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'group_id' => $this->group_id,
            'group_name' => $this->group?->name,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                // Segunda línea de la fila en el mockup: "Gabriel Morales ·
                // Construcinsa S.A. de C.V." — el nombre solo cuando el
                // contacto no está asociado a ninguna empresa.
                'company' => $this->customer->relationLoaded('company') ? $this->customer->company?->name : null,
            ] : null,
            // Tercera línea de la fila: resumen del último mensaje del hilo,
            // o la descripción con la que se abrió el ticket si aún no hay
            // ninguno. En el mockup TODAS las filas tienen esta línea; sin el
            // respaldo, la lista alternaba filas de 96 y 79 px y perdía la
            // sensación de rejilla.
            'last_message_snippet' => $this->listSnippet(),
            // Clip de adjuntos junto al asunto. attachment_urls del último
            // mensaje es lo que ya usa el hilo para pintar los ficheros.
            'has_attachments' => $this->relationLoaded('lastMessage') && $this->lastMessage
                ? ! empty($this->lastMessage->attachment_urls)
                : false,
            // Chip de entrega de la cabecera del detalle. null cuando el
            // ticket no ha generado ningún correo saliente (widget, WhatsApp,
            // ticket creado a mano): entonces no hay entrega que reportar y
            // el chip no se pinta.
            'last_mail_status' => $this->relationLoaded('lastOutboundMail')
                ? $this->lastOutboundMail?->status
                : null,
            'assignee' => $assignee,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'close_reason' => $this->close_reason,
            'close_reason_label' => $this->close_reason
                ? ((array) config('helpdesktickets.close_reasons', []))[$this->close_reason] ?? $this->close_reason
                : null,
            'unread_count' => (int) ($this->unread_count ?? $this->getUnreadCountForUser(auth()->id())),
            // Contador total de mensajes (mockup: 💬 N) — distinto del
            // punto rojo de "no leído", que se conserva porque transmite
            // algo que el conteo no dice (hay algo nuevo que mirar).
            'message_count' => $this->message_count ?? null,
            'sla_kind' => $this->slaRowKind(),
            'sla_text' => $this->slaRowText(),
            'sla_status' => $this->sla_status,
            'sla_due_at' => $this->slaEffectiveDueDate()?->toIso8601String(),
            // "Ver como el cliente" del mockup, versión segura: enlace
            // firmado de solo lectura (SharedTicketController), no
            // suplantación de sesión — ver su docblock para el porqué.
            'url_shared_ticket' => SharedTicketController::signedShowUrl($this),
        ];
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
