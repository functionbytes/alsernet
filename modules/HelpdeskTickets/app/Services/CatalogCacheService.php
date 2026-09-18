<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class CatalogCacheService
{
    private const TTL = 3600;

    /**
     * Short TTL: agent availability changes often, catalogs do not.
     */
    private const AGENTS_TTL = 60;

    /**
     * Las etiquetas cambian con cada guardado de ticket; TTL corto para que la
     * lista no se quede vieja si alguna vía de escritura olvida invalidar.
     */
    private const TAGS_TTL = 300;

    public static function defaultStatus(): ?TicketStatus
    {
        return Cache::remember('helpdesk:catalogs:default-status', self::TTL, fn () => TicketStatus::where('is_default', true)->first());
    }

    public static function statuses(): Collection
    {
        return Cache::remember('helpdesk:catalogs:statuses', self::TTL, fn () => TicketStatus::active()->ordered()->get());
    }

    public static function categories(): Collection
    {
        return Cache::remember('helpdesk:catalogs:categories', self::TTL, fn () => TicketCategory::active()->ordered()->get());
    }

    public static function groups(): Collection
    {
        return Cache::remember('helpdesk:catalogs:groups', self::TTL, fn () => Group::orderBy('name')->get());
    }

    /**
     * Usuarios asignables como agente en el CRUD de tickets (index/create/
     * edit/show/filtro). Cacheado brevemente para no re-consultar en cada
     * request.
     *
     * Bug real encontrado en QA visual (ago-2026), en dos partes:
     * 1) el filtro solo exigía available+verified, sin exigir el rol
     *    helpdesk-agent — en un sistema con ~1000 usuarios (fixtures de
     *    otros módulos) el selector "Agente" mostraba prácticamente a todo
     *    el mundo en vez de a los agentes reales del equipo (limpiados de
     *    la base de datos aparte). Mismo criterio de rol que ya usa
     *    AssignmentService::getAvailableAgents().
     * 2) el propio requisito verified=true dejaba la lista VACÍA: las
     *    cuentas de agentes reales se crean directamente (no vía registro
     *    público con verificación de email) y tienen verified=0. Ninguna
     *    otra fuente de "quién es agente" de este módulo (AssignmentService,
     *    ConversationInboxMetricsService::agentWorkload() en Conversaciones)
     *    exige verified — se retira aquí para la misma consistencia.
     */
    public static function agents(): Collection
    {
        // last_login_at entra en el select porque AgentAvailabilityService lo
        // necesita para distinguir a un agente real de una cuenta que nunca ha
        // llegado a usarse; sin ella todos parecían recién creados.
        return Cache::remember('helpdesk:catalogs:agents', self::AGENTS_TTL, fn () => User::select(['id', 'firstname', 'lastname', 'email', 'last_login_at'])
            ->whereHas('roles', fn ($q) => $q->where('name', 'helpdesk-agent'))
            ->where('available', true)
            ->orderBy('firstname')
            ->get());
    }

    /**
     * Etiquetas distintas en uso, para el desplegable de filtro del listado.
     *
     * Antes se calculaba en TicketsCrudController::index() con un pluck('tags')
     * sobre la tabla completa en cada carga de la página. Es la misma lista
     * para todos los agentes y cambia poco: se cachea con TTL corto y se
     * invalida explícitamente al guardar etiquetas.
     *
     * @return SupportCollection<int, string>
     */
    public static function ticketTags(): SupportCollection
    {
        return Cache::remember('helpdesk:catalogs:ticket-tags', self::TAGS_TTL, fn () => Ticket::query()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values());
    }

    public static function invalidateTags(): void
    {
        Cache::forget('helpdesk:catalogs:ticket-tags');
    }

    public static function invalidate(): void
    {
        Cache::forget('helpdesk:catalogs:default-status');
        Cache::forget('helpdesk:catalogs:statuses');
        Cache::forget('helpdesk:catalogs:categories');
        Cache::forget('helpdesk:catalogs:groups');
        Cache::forget('helpdesk:catalogs:agents');
        Cache::forget('helpdesk:catalogs:status-ids-by-slug');
        self::invalidateTags();
    }
}
