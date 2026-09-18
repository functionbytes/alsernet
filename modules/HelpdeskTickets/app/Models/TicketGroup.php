<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Grupos de tickets (pantalla Ajustes → Grupos).
 *
 * Apunta a `helpdesk_groups`, NO a `helpdesk_ticket_groups`. Durante mucho
 * tiempo escribió en su propia tabla, pero la FK de la columna que de verdad
 * importa —`helpdesk_tickets.group_id`— referencia `helpdesk_groups`, igual que
 * Ticket::group() y el selector de grupo del CRUD. El resultado era que un grupo
 * creado desde Ajustes no se podía asignar a ningún ticket, y una automatización
 * con la acción `assign_group` moría con violación de clave foránea
 * (SQLSTATE 23000, 1452). Se unificó hacia `helpdesk_groups`, que es la tabla
 * que el resto del sistema ya daba por buena.
 *
 * Esa tabla nombra dos columnas distinto que esta pantalla: `default` en vez de
 * `is_default` y `position` en vez de `order`. Se traducen aquí con un
 * accessor/mutator para no arrastrar el renombrado por controlador, requests y
 * vistas; las CONSULTAS sí usan el nombre real de la columna, porque un
 * where() no pasa por el accessor.
 */
class TicketGroup extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_groups';

    protected $fillable = [
        'name',
        'description',
        'assignment_mode',
        'is_default',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'default' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * is_default ↔ columna `default`. La pantalla y sus FormRequest hablan de
     * is_default; la tabla la llama default (palabra reservada, de ahí que no
     * se pueda usar tal cual en según qué sitios).
     */
    protected function isDefault(): Attribute
    {
        return Attribute::make(
            // (bool) ANTES de ?? rompe la protección de ?? sobre un acceso
            // directo a array: (bool) $x['k'] se evalúa como su propia
            // expresión (el cast tiene más precedencia), así que PHP
            // necesita resolver $x['k'] de verdad para castearlo — el ??
            // nunca llega a intervenir y "Undefined array key" salta igual.
            // Detectado creando un TicketGroup sin pasar 'default' (columna
            // sin default en el modelo, aunque sí lo tenga en la migración).
            get: fn () => (bool) ($this->attributes['default'] ?? false),
            set: fn ($value) => ['default' => (bool) $value],
        );
    }

    /**
     * order ↔ columna `position`, por el mismo motivo.
     */
    protected function order(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ($this->attributes['position'] ?? 0),
            set: fn ($value) => ['position' => (int) $value],
        );
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new groups
        static::creating(function ($group) {
            if (is_null($group->attributes['position'] ?? null)) {
                $group->position = (static::max('position') ?? 0) + 1;
            }
        });

        // "Grupo por defecto" es exclusivo: al marcar uno, se desmarca el que
        // lo estuviera. No lo era, pero mientras la pantalla escribia en su
        // propia tabla (vacia) el fallo no se alcanzaba; contra helpdesk_groups,
        // que ya trae uno marcado, marcar un segundo dejaba dos y findDefault()
        // devolvia el de id mas bajo, no el que se acababa de elegir.
        static::saved(function (self $group) {
            if (! $group->is_default) {
                return;
            }

            static::where('id', '!=', $group->id)
                ->where('default', true)
                ->update(['default' => false]);
        });
    }

    /**
     * Get the users (agents) that belong to the group.
     * Cross-database relationship with User model.
     */
    public function users(): BelongsToMany
    {
        $defaultConnection = config('database.default');
        $defaultDatabase = config("database.connections.{$defaultConnection}.database");

        // Pivot de helpdesk_groups. La columna de prioridad se llama
        // conversation_priority (la comparte con el reparto de conversaciones),
        // no `priority` como en la tabla que se retiró.
        $relation = $this->belongsToMany(
            User::class,
            'helpdesk_group_user',
            'group_id',
            'user_id'
        )
            ->withPivot('conversation_priority')
            ->withTimestamps();

        // Override the query to use the correct database for users table
        $relation->getQuery()->from("{$defaultDatabase}.users");

        return $relation;
    }

    /**
     * Get agents with primary priority.
     */
    public function primaryAgents()
    {
        return $this->users()->wherePivot('conversation_priority', 'primary');
    }

    /**
     * Get agents with backup priority.
     */
    public function backupAgents()
    {
        return $this->users()->wherePivot('conversation_priority', 'backup');
    }

    /**
     * Get ticket categories linked to this group
     */
    public function ticketCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketCategory::class,
            'helpdesk_ticket_category_ticket_group',
            'ticket_group_id',
            'ticket_category_id'
        )->withPivot(['is_default', 'priority'])
            ->withTimestamps();
    }

    /**
     * Find the default group.
     */
    public static function findDefault(): ?self
    {
        return static::where('default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * IDs de los equipos a los que pertenece un agente.
     *
     * Consulta directa al pivote (mismo criterio que users(): tabla
     * helpdesk_group_user en la conexión 'helpdesk') en vez de pasar por la
     * relación, para no arrastrar el belongsToMany cruzado entre conexiones
     * —aquí solo hacen falta los IDs, no hidratar modelos de User.
     *
     * La usan TicketPolicy y TicketsCrudController para acotar qué tickets ve
     * un agente sin el permiso helpdesk.tickets.manage: los de su(s)
     * equipo(s), más los que tenga asignados a él directamente aunque el
     * ticket sea de un equipo ajeno (p. ej. reasignado a mano).
     *
     * Memoizada en memoria (no cache compartida) por $userId: la misma
     * request de listado llama a esto hasta 3 veces (scopeToVisibleTickets(),
     * tabCountsScopeKey() y — por cada ticket — TicketPolicy::inScope() en
     * una acción masiva de BulkTicketsController), siempre para el mismo
     * usuario autenticado (14-sep-2026, auditoría de rendimiento). La
     * membresía de grupo no cambia dentro de una misma request/job, así que
     * no hace falta invalidar esta caché en vivo.
     *
     * @return array<int, int>
     */
    public static function idsForUser(int $userId): array
    {
        return self::$idsForUserCache[$userId] ??= DB::connection('helpdesk')
            ->table('helpdesk_group_user')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @var array<int, array<int, int>> */
    private static array $idsForUserCache = [];

    /**
     * Get the next agent for assignment based on assignment mode.
     */
    public function getNextAgent(): ?User
    {
        $agents = $this->primaryAgents()
            ->with('agentSettings')
            ->get()
            ->filter(function ($agent) {
                return $agent->agentSettings
                    && $agent->agentSettings->acceptsConversationsNow()
                    && ! $agent->agentSettings->hasReachedLimit();
            });

        if ($agents->isEmpty()) {
            // Try backup agents
            $agents = $this->backupAgents()
                ->with('agentSettings')
                ->get()
                ->filter(function ($agent) {
                    return $agent->agentSettings
                        && $agent->agentSettings->acceptsConversationsNow()
                        && ! $agent->agentSettings->hasReachedLimit();
                });
        }

        if ($agents->isEmpty()) {
            return null;
        }

        return match ($this->assignment_mode) {
            'round_robin' => $this->getNextAgentRoundRobin($agents),
            'load_balanced' => $this->getNextAgentLoadBalanced($agents),
            default => $agents->first(),
        };
    }

    /**
     * Get next agent using round robin.
     */
    protected function getNextAgentRoundRobin($agents): User
    {
        // Simple implementation: get agent with oldest assignment
        return $agents->sortBy(function ($agent) {
            return $agent->pivot->created_at;
        })->first();
    }

    /**
     * Get next agent using load balancing.
     */
    protected function getNextAgentLoadBalanced($agents): User
    {
        // Get agent with least active tickets
        return $agents->sortBy(function ($agent) {
            return Ticket::where('assignee_id', $agent->id)
                ->whereHas('status', function ($q) {
                    $q->where('is_open', true);
                })
                ->count();
        })->first();
    }

    /**
     * Scope to get only active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get groups ordered by their sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Reorder groups based on an array of IDs.
     */
    public static function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            static::where('id', $id)->update(['position' => $order + 1]);
        }
    }
}
