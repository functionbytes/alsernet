<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Enums\SuppressionReason;
use Modules\HelpdeskEmailLog\Http\Requests\BulkDeleteEmailLogsRequest;
use Modules\HelpdeskEmailLog\Http\Requests\BulkResendEmailLogsRequest;
use Modules\HelpdeskEmailLog\Http\Requests\BulkRestoreEmailLogsRequest;
use Modules\HelpdeskEmailLog\Http\Requests\ExportSelectedEmailLogsRequest;
use Modules\HelpdeskEmailLog\Http\Requests\LinkEmailLogEntityRequest;
use Modules\HelpdeskEmailLog\Http\Requests\ResendEmailLogRequest;
use Modules\HelpdeskEmailLog\Http\Requests\ResolveBounceEmailLogRequest;
use Modules\HelpdeskEmailLog\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogClick;
use Modules\HelpdeskEmailLog\Models\EmailLogOpen;
use Modules\HelpdeskEmailLog\Services\EmailSuppressionService;
use Modules\HelpdeskEmailLog\Services\EntityPanelRegistry;
use Modules\HelpdeskEmailLog\Support\EngagementBotHeuristics;
use Modules\HelpdeskTickets\Models\Ticket;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmailLogController extends Controller
{
    /** @var array<string, string> */
    private const SORTABLE = [
        'date' => 'created_at',
        'subject' => 'subject',
        'status' => 'status',
        'module' => 'module',
    ];

    private const EXPORT_HARD_LIMIT = 50000;

    /**
     * Icono por status para la cabecera del panel de detalle — mismo mapa
     * que emails/preview.blade.php calculaba inline antes de esta extracción
     * (ver resolveDetailData()).
     *
     * @var array<string, string>
     */
    private const STATUS_ICONS = [
        'sent' => 'fa-check',
        'failed' => 'fa-xmark',
        'queued' => 'fa-clock',
        'bounced' => 'fa-triangle-exclamation',
        'complained' => 'fa-flag',
        'suppressed' => 'fa-ban',
    ];

    /** Columnas ligeras (sin cuerpo) compartidas por export() y exportSelected(). */
    private const EXPORT_COLUMNS = [
        'id', 'uid', 'created_at', 'sent_at', 'status', 'subject', 'from_address',
        'to_addresses', 'cc_addresses', 'module', 'entity_type', 'entity_id',
        'mailable_class', 'error_message', 'metadata',
    ];

    /**
     * Ventana por defecto (sin filtro de fecha activo) para el delta de KPIs
     * frente al periodo anterior — misma ventana que ya usa el gráfico de
     * tendencia (ver computeTrend()), para que ambos bloques del dashboard
     * hablen del mismo rango cuando no hay filtro.
     */
    private const DELTA_DEFAULT_WINDOW_DAYS = 14;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmailLog::class);

        abort_if(! helpdesk_emaillog_enabled(), 404);

        return $this->renderWorkspace($request, selected: null);
    }

    /**
     * Deep-link a un email concreto — pinta el MISMO workspace combinado que
     * index() (lista + detalle + sidebar en una sola pantalla), con ese email
     * ya seleccionado. Ver renderWorkspace().
     *
     * Cuando la petición es AJAX (ver renderWorkspace()), devuelve solo el
     * fragmento de detalle+sidebar: es el camino que usa el clic sobre una
     * fila del listado para cargar el detalle sin navegar de página.
     */
    public function show(EmailLog $emailLog, Request $request): View
    {
        $this->authorize('view', $emailLog);

        // Solo se registra 'viewed' en este método (navegación/clic explícito
        // a ESTE email) — nunca cuando index() autoselecciona la primera fila
        // del listado únicamente para no dejar la columna de detalle vacía
        // (eso no es un "view" real del usuario, ver renderWorkspace()).
        $this->logActivity('viewed', $emailLog);

        return $this->renderWorkspace($request, selected: $emailLog);
    }

    /**
     * Punto único de render para index() y show(): ambas rutas pintan la
     * MISMA vista combinada (lista + detalle + sidebar) — show() solo añade
     * una selección explícita por UID sobre exactamente los mismos datos de
     * lista que index() ya construye.
     *
     * Sin selección explícita (index() puro) se autoselecciona la primera
     * fila del listado, si existe alguna, para que la columna de detalle
     * nunca quede vacía en la carga inicial (mockup: siempre hay algo
     * seleccionado). Con $logs vacío no hay nada que autoseleccionar — la
     * columna de detalle queda en su estado vacío (lo maneja el frontend).
     */
    private function renderWorkspace(Request $request, ?EmailLog $selected): View
    {
        $listData = $this->buildListData($request);

        $selectedLog = $selected ?? $this->autoSelectFirst($listData['logs']);

        $detailData = $selectedLog
            ? $this->resolveDetailData($selectedLog, $request, $listData['logs']->total())
            : $this->emptyDetailData($request);

        $viewData = $listData + $detailData;

        // Fragmento AJAX: solo para show() con una selección EXPLÍCITA (nunca
        // para index() ni para el auto-select de la primera fila) — el
        // frontend hace clic en una fila y pide SOLO el detalle+sidebar, sin
        // repintar la columna de lista que ya tiene cargada. Mismo criterio
        // de detección AJAX que ConversationsController::update() (Helpdesk),
        // el único otro punto del código que decide entre devolver un
        // fragmento parcial o una respuesta de navegación completa.
        if ($selected !== null && ($request->ajax() || $request->wantsJson())) {
            return view('helpdeskemaillog::emails.partials.detail-panel', $viewData);
        }

        return view('helpdeskemaillog::emails.index', $viewData);
    }

    /**
     * Misma forma que index() pasaba a la vista antes de esta extracción —
     * ver renderWorkspace().
     *
     * @return array<string, mixed>
     */
    private function buildListData(Request $request): array
    {
        $perPage = $this->resolvePerPage($request);
        [$sortCol, $sortDir] = $this->resolveSort($request);

        // withCount en vez de N+1 por fila: genera una única subquery
        // correlacionada por columna (Laravel la resuelve también para
        // hasManyThrough), sin más peso que una columna extra en el SELECT.
        //
        // El extracto de fila (body_snippet_raw) se trae con LEFT(body_text, N)
        // en vez de cargar la columna completa — LIST_COLUMNS sigue sin
        // incluir body_text/body_html a propósito (ver su docblock). Se limpia
        // y recorta en PHP dentro de EmailLog::bodySnippet(), nunca aquí sobre
        // el listado entero.
        $logs = $this->applyFilters(
            EmailLog::query()
                ->select(EmailLog::LIST_COLUMNS)
                ->selectRaw('LEFT(body_text, ?) as body_snippet_raw', [EmailLog::BODY_SNIPPET_RAW_LENGTH]),
            $request,
        )
            ->withCount(['opens', 'clicks'])
            ->orderBy($sortCol, $sortDir)
            ->orderBy('id', $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $stats = Cache::remember(
            'helpdeskemaillog:stats',
            now()->addSeconds(60),
            fn () => $this->computeStats(),
        );

        // Sin cache: a diferencia de 'stats' (foto global, misma para todos),
        // el delta depende del filtro de fecha de ESTA request — cachearlo
        // bajo una clave fija contaminaría entre usuarios con rangos
        // distintos, y una clave dinámica por rango crecería sin límite.
        $statsDelta = $this->computeStatsDelta($request);

        $modules = Cache::remember(
            'helpdeskemaillog:modules',
            now()->addMinutes(10),
            fn () => EmailLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module')->all(),
        );

        // Opciones del filtro "Agente" (quién lo envió) — solo los usuarios
        // que REALMENTE aparecen como causer en el log, no todos los usuarios
        // del sistema (ver computeAgentOptions()). Mismo TTL/patrón de caché
        // que 'modules'.
        $agents = Cache::remember(
            'helpdeskemaillog:causers',
            now()->addMinutes(10),
            fn () => $this->computeAgentOptions(),
        );

        // Opciones del filtro "Buzón remitente" — remitentes distintos que ya
        // existen en el log, mismo patrón que 'modules'.
        $fromAddresses = Cache::remember(
            'helpdeskemaillog:from-addresses',
            now()->addMinutes(10),
            fn () => EmailLog::query()->whereNotNull('from_address')->distinct()->orderBy('from_address')->pluck('from_address')->all(),
        );

        $trend = Cache::remember(
            'helpdeskemaillog:trend',
            now()->addSeconds(300),
            fn () => $this->computeTrend(),
        );

        $staleHours = $this->resolveStaleHours();
        $staleCount = $staleHours > 0
            ? (int) Cache::remember('helpdeskemaillog:stale', now()->addSeconds(120), fn () => EmailLog::query()->staleQueued($staleHours)->count())
            : 0;

        return [
            'logs' => $logs,
            'stats' => $stats,
            'statsDelta' => $statsDelta,
            'trend' => $trend,
            'staleCount' => $staleCount,
            'staleHours' => $staleHours,
            'modules' => $modules,
            'agents' => $agents,
            'fromAddresses' => $fromAddresses,
            'statuses' => EmailStatus::options(),
            'perPage' => $perPage,
            'perPageOptions' => config('helpdeskemaillog.per_page_options', [25]),
            'sortBy' => $request->input('sort_by', 'date'),
            'sortDir' => $sortDir,
        ];
    }

    /**
     * Usuarios que realmente aparecen como causer del log (no todos los
     * usuarios del sistema) — mismo patrón que
     * Modules\Activity\Http\Controllers\ActivityController::availableCausers().
     * causer_type siempre es App\Models\User en la práctica (ver
     * InspectsMailMessage::causerAttributes(), que guarda Auth::user()::class),
     * así que no hace falta resolver otros tipos de causer aquí.
     *
     * @return Collection<int, User>
     */
    private function computeAgentOptions(): Collection
    {
        $ids = EmailLog::query()
            ->whereNotNull('causer_id')
            ->where('causer_type', User::class)
            ->distinct()
            ->pluck('causer_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->orderBy('firstname')->get(['id', 'firstname', 'lastname', 'email']);
    }

    /**
     * Primera fila del listado ya paginado/filtrado, recargada SIN la
     * restricción de columnas de EmailLog::LIST_COLUMNS — el detalle necesita
     * columnas que el listado no trae (p. ej. body_html, causer_type/
     * causer_id para loadMissing('causer') en resolveDetailData()).
     */
    private function autoSelectFirst(LengthAwarePaginator $logs): ?EmailLog
    {
        $first = $logs->first();

        return $first ? EmailLog::query()->find($first->id) : null;
    }

    /**
     * Extraído de show() (mismo comportamiento) más las variables que
     * emails/preview.blade.php y emails/index.blade.php calculaban inline
     * (canManage/staleHours/isStaleQueued/statusIcon) — centralizado aquí
     * para que la vista combinada y el fragmento AJAX siempre vean lo mismo.
     *
     * $listTotal es el total YA calculado por el paginador del listado
     * (mismos filtros, ver buildListData()) — se reutiliza como
     * selectedTotal en vez de repetir ese count() aquí.
     *
     * @return array{log: EmailLog, opensSummary: ?array, clicksSummary: ?array, related: Collection<int, EmailLog>, entityPanel: ?string, canManage: bool, staleHours: int, isStaleQueued: bool, statusIcon: string, recipientStats: ?array, transport: array, domainAuth: array, traceElapsedLabel: ?string, emlSizeLabel: ?string, activityLog: Collection<int, Activity>, ticketsModuleEnabled: bool, selectedPosition: int, selectedTotal: int, prevUid: ?string, nextUid: ?string}
     */
    protected function resolveDetailData(EmailLog $emailLog, Request $request, int $listTotal): array
    {
        $emailLog->loadMissing('causer');

        $staleHours = $this->resolveStaleHours();

        return [
            'log' => $emailLog,
            'opensSummary' => $this->opensSummary($emailLog),
            'clicksSummary' => $this->clicksSummary($emailLog),
            'related' => $this->relatedEmails($emailLog),
            // Panel HTML inyectado por el módulo dueño de la entidad (p. ej.
            // HelpdeskTickets pintando el hilo de la conversación) — null si
            // no hay entidad vinculada o ningún módulo satélite se registró
            // para ese entity_type. Ver EntityPanelRegistry.
            'entityPanel' => app(EntityPanelRegistry::class)->renderFor($emailLog),
            'canManage' => $this->resolveCanManage($request),
            'staleHours' => $staleHours,
            'isStaleQueued' => $emailLog->status?->value === 'queued'
                && $emailLog->created_at
                && $emailLog->created_at->lt(now()->subHours($staleHours)),
            'statusIcon' => self::STATUS_ICONS[$emailLog->status?->value] ?? 'fa-circle',
            'recipientStats' => $this->recipientStats($emailLog),
            'transport' => $this->transportInfo(),
            'domainAuth' => $this->domainAuthStatus($emailLog),
            'traceElapsedLabel' => $this->traceElapsedLabel($emailLog),
            'emlSizeLabel' => $this->emlSizeLabel($emailLog),
            'activityLog' => $this->activityLogFor($emailLog),
            'ticketsModuleEnabled' => function_exists('helpdesk_tickets_enabled') && helpdesk_tickets_enabled(),
            // Para que el sidebar diga los días reales de recuperación en la
            // acción "Enviar a la papelera", no un 30 hardcodeado.
            'trashRetentionDays' => $this->resolveTrashRetentionDays(),
        ] + $this->resolveNavigation($emailLog, $request, $listTotal);
    }

    /**
     * Mismas claves que resolveDetailData(), en null/vacío — para cuando el
     * listado no tiene ninguna fila que autoseleccionar (ver renderWorkspace()).
     *
     * @return array{log: null, opensSummary: null, clicksSummary: null, related: Collection<int, EmailLog>, entityPanel: null, canManage: bool, staleHours: int, isStaleQueued: bool, statusIcon: null, recipientStats: null, transport: null, domainAuth: null, traceElapsedLabel: null, emlSizeLabel: null, activityLog: Collection<int, Activity>, ticketsModuleEnabled: bool, selectedPosition: null, selectedTotal: int, prevUid: null, nextUid: null}
     */
    private function emptyDetailData(Request $request): array
    {
        return [
            'log' => null,
            'opensSummary' => null,
            'clicksSummary' => null,
            'related' => collect(),
            'entityPanel' => null,
            'canManage' => $this->resolveCanManage($request),
            'staleHours' => $this->resolveStaleHours(),
            'isStaleQueued' => false,
            'statusIcon' => null,
            'recipientStats' => null,
            'transport' => null,
            'domainAuth' => null,
            'traceElapsedLabel' => null,
            'emlSizeLabel' => null,
            'activityLog' => collect(),
            'ticketsModuleEnabled' => function_exists('helpdesk_tickets_enabled') && helpdesk_tickets_enabled(),
            // Para que el sidebar diga los días reales de recuperación en la
            // acción "Enviar a la papelera", no un 30 hardcodeado.
            'trashRetentionDays' => $this->resolveTrashRetentionDays(),
            'selectedPosition' => null,
            'selectedTotal' => 0,
            'prevUid' => null,
            'nextUid' => null,
        ];
    }

    /**
     * Navegación anterior/siguiente del listado (mockup: contador "X de N" +
     * flechas ▲/▼) — calculada con paginación por keyset (comparar
     * directamente contra los valores de orden de la fila seleccionada, sin
     * OFFSET) para que sea barata incluso con miles de filas: nunca se trae
     * el listado completo a PHP, solo 3 queries ligeras (count + 2 lookups de
     * 1 fila). Reutiliza applyFilters() (mismos filtros activos) — nunca
     * duplica esa lógica.
     *
     * Asume que $emailLog SÍ cumple los filtros activos (siempre cierto en
     * el flujo normal: autoSelectFirst() lo saca del propio listado filtrado,
     * y el clic de fila selecciona algo ya visible en ese listado). Un
     * deep-link a un uid que ya no cumple el filtro activo puede devolver una
     * posición/prev/next no del todo representativos — caso límite fuera de
     * alcance.
     *
     * @return array{selectedPosition: int, selectedTotal: int, prevUid: ?string, nextUid: ?string}
     */
    private function resolveNavigation(EmailLog $emailLog, Request $request, int $listTotal): array
    {
        [$sortCol, $sortDir] = $this->resolveSort($request);
        $selVal = $this->sortColumnValue($emailLog, $sortCol);
        $reverseDir = $this->flipDirection($sortDir);

        // "antes"/"después" respecto al orden (col, id) activo — ver
        // buildListData(), que ordena siempre por [$sortCol, $sortDir] y
        // desempata por id en esa MISMA dirección.
        $beforeOperator = $sortDir === 'asc' ? '<' : '>';
        $afterOperator = $sortDir === 'asc' ? '>' : '<';

        $beforeQuery = fn (): Builder => $this->applyFilters(EmailLog::query(), $request)
            ->where(fn (Builder $q) => $this->applyBeyondCondition($q, $sortCol, $beforeOperator, $selVal, $emailLog->id));

        $afterQuery = fn (): Builder => $this->applyFilters(EmailLog::query(), $request)
            ->where(fn (Builder $q) => $this->applyBeyondCondition($q, $sortCol, $afterOperator, $selVal, $emailLog->id));

        // El más cercano de cada lado: dentro del conjunto "antes", el más
        // próximo es el último en el orden natural (se obtiene invirtiendo el
        // ORDER BY); dentro de "después", el orden natural ya lo trae primero.
        $prev = $beforeQuery()->orderBy($sortCol, $reverseDir)->orderBy('id', $reverseDir)->select('uid')->first();
        $next = $afterQuery()->orderBy($sortCol, $sortDir)->orderBy('id', $sortDir)->select('uid')->first();

        return [
            'selectedPosition' => $beforeQuery()->count() + 1,
            'selectedTotal' => $listTotal,
            'prevUid' => $prev?->uid,
            'nextUid' => $next?->uid,
        ];
    }

    private function flipDirection(string $direction): string
    {
        return $direction === 'asc' ? 'desc' : 'asc';
    }

    /**
     * Valor de $emailLog para la columna de orden activa, listo para comparar
     * en SQL — desenvuelve el enum de 'status' (cast a EmailStatus) a su
     * valor string.
     */
    private function sortColumnValue(EmailLog $emailLog, string $column): mixed
    {
        $value = $emailLog->getAttribute($column);

        return $value instanceof EmailStatus ? $value->value : $value;
    }

    /**
     * (col $operator $selVal) OR (col = $selVal AND id $operator $selId) — la
     * condición de paginación por keyset para localizar las filas que quedan
     * estrictamente a un lado de la seleccionada en el orden (col, id) activo,
     * sin usar OFFSET.
     *
     * NULL se trata como "el valor más bajo" (misma semántica que ORDER BY en
     * MySQL/MariaDB): 'module' es la única columna ordenable nullable del
     * listado (ver SORTABLE) y los operadores > / < de SQL, a diferencia de
     * ORDER BY, no consideran NULL como el mínimo por sí solos.
     */
    private function applyBeyondCondition(Builder $query, string $column, string $operator, mixed $selVal, int $selId): Builder
    {
        return $query->where(function (Builder $q) use ($column, $operator, $selVal, $selId) {
            if ($selVal === null && $operator === '>') {
                // Lo único "mayor" que el valor más bajo es no ser NULL.
                $q->whereNotNull($column);
            } elseif ($selVal === null) {
                // $operator === '<': nada es "menor" que el valor más bajo.
                $q->whereRaw('1 = 0');
            } elseif ($operator === '<') {
                $q->where(fn (Builder $c) => $c->where($column, '<', $selVal)->orWhereNull($column));
            } else {
                $q->where($column, '>', $selVal);
            }

            // Desempate por id (misma columna secundaria que buildListData())
            // — solo aplica a las filas que además comparten columna de orden.
            $q->orWhere(function (Builder $tie) use ($column, $operator, $selVal, $selId) {
                if ($selVal === null) {
                    $tie->whereNull($column);
                } else {
                    $tie->where($column, '=', $selVal);
                }

                $tie->where('id', $operator, $selId);
            });
        });
    }

    /**
     * Agregados del destinatario principal (primer to_addresses) para la
     * tarjeta "Destinatario" del sidebar — null si el envío no tiene ningún
     * destinatario. Cacheado por dirección (TTL corto, mismo patrón que
     * 'stats'/'stale' en buildListData()): sin invalidación proactiva porque
     * es un dato de sidebar de bajo riesgo, tolerante a unos segundos de
     * desfase.
     *
     * 'name'/'company' se exponen siempre en null: no existe ninguna fuente
     * real de esos datos en este módulo — from_name es el REMITENTE, no el
     * destinatario, y HelpdeskContacts no expone un modelo local por email
     * (solo servicios que consultan ERP/PrestaShop bajo demanda) — ver informe.
     *
     * @return array{email: string, name: null, company: null, total_received: int, last_opened_at: ?Carbon, delivery_rate: ?float}|null
     */
    private function recipientStats(EmailLog $emailLog): ?array
    {
        $recipient = $emailLog->to_addresses[0] ?? null;

        if (! $recipient) {
            return null;
        }

        return Cache::remember(
            'helpdeskemaillog:recipient-stats:'.md5($recipient),
            now()->addSeconds(60),
            fn () => $this->computeRecipientStats($recipient),
        );
    }

    /**
     * @return array{email: string, name: null, company: null, total_received: int, last_opened_at: ?Carbon, delivery_rate: ?float}
     */
    private function computeRecipientStats(string $recipient): array
    {
        $agg = EmailLog::query()
            ->whereJsonContains('to_addresses', $recipient)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'sent') as sent")
            ->first();

        $total = (int) ($agg->total ?? 0);
        $sent = (int) ($agg->sent ?? 0);

        $lastOpenedAt = EmailLogOpen::query()
            ->join('email_logs', 'email_logs.id', '=', 'email_log_opens.email_log_id')
            ->whereJsonContains('email_logs.to_addresses', $recipient)
            ->max('email_log_opens.opened_at');

        return [
            'email' => $recipient,
            'name' => null,
            'company' => null,
            'total_received' => $total,
            'last_opened_at' => $lastOpenedAt ? Carbon::parse($lastOpenedAt) : null,
            // Mismo criterio que el KPI global 'delivery_rate' del dashboard
            // (ver computeStatsDelta()/rateFrom()): sent/total, NO
            // delivered_at/total — delivered_at solo lo confirman nativamente
            // Mailgun/Postmark (ver catálogo de eventos por proveedor en
            // EmailDeliveryEventCorrelatorService), así que exigirlo
            // infravaloraría sistemáticamente la tasa para el resto.
            'delivery_rate' => $this->rateFrom($sent, $total),
        ];
    }

    /**
     * Transporte REAL del mailer por defecto de la app (pestaña Traza,
     * tarjeta "Transporte") — no depende del email seleccionado, todo el
     * sistema comparte la misma config/mail.php, pero solo tiene sentido
     * mostrarlo dentro del detalle de un envío concreto.
     *
     * Si el mailer activo no es 'smtp' (p. ej. 'log'/'sendmail' en un
     * entorno sin servidor real), se omite host/puerto y la vista muestra
     * el nombre del mailer en su lugar, en vez de fingir un host que no se
     * está usando.
     *
     * 'queue' es la cola real donde ESTE módulo despacha su propio trabajo
     * de envío (ver ResendEmailLogJob::__construct() y LogEmailSent::$queue,
     * ambas 'emails') — un dato de código, no de configuración, por eso no
     * se lee de config().
     *
     * @return array{mailer: string, isSmtp: bool, host: ?string, port: ?int, queue: string}
     */
    private function transportInfo(): array
    {
        $mailer = (string) config('mail.default');
        $mailerConfig = (array) config("mail.mailers.{$mailer}", []);
        $isSmtp = ($mailerConfig['transport'] ?? null) === 'smtp';

        return [
            'mailer' => $mailer,
            'isSmtp' => $isSmtp,
            'host' => $isSmtp ? ($mailerConfig['host'] ?? null) : null,
            'port' => $isSmtp ? ($mailerConfig['port'] ?? null) : null,
            'queue' => 'emails',
        ];
    }

    /**
     * Estado SPF/DKIM/DMARC del DOMINIO REMITENTE de este envío — no de este
     * mensaje concreto (un mensaje individual no tiene su propio SPF/DKIM;
     * son propiedades que el dominio firmante publica en su DNS). Ver
     * DomainAuthenticationChecker.
     *
     * Nunca dispara aquí una resolución DNS en caliente: solo LEE la caché
     * que ya puebla la pantalla de Reputación
     * (EmailReputationController::index(), misma clave
     * "helpdeskemaillog:domain-auth:{$domain}") — sin esa caché (dominio no
     * vigilado en Settings, o caché caducada) cada campo queda null y la
     * vista ofrece un enlace a Reputación en vez de fingir un resultado.
     *
     * @return array{domain: string, spf: ?array, dmarc: ?array, dkim: ?array}
     */
    private function domainAuthStatus(EmailLog $emailLog): array
    {
        $domain = Str::after((string) $emailLog->from_address, '@');
        $cached = $domain !== '' ? Cache::get("helpdeskemaillog:domain-auth:{$domain}") : null;

        return [
            'domain' => $domain,
            'spf' => $cached['spf'] ?? null,
            'dmarc' => $cached['dmarc'] ?? null,
            'dkim' => $cached['dkim'] ?? null,
        ];
    }

    /**
     * Tiempo total real entre encolado y confirmación de envío (sent_at -
     * created_at) para la tarjeta resumen de la pestaña Traza — null cuando
     * el envío sigue en cola o falló antes de confirmarse (nunca se inventa
     * una duración sin sent_at real).
     */
    private function traceElapsedLabel(EmailLog $emailLog): ?string
    {
        if (! $emailLog->sent_at || ! $emailLog->created_at) {
            return null;
        }

        return $this->formatDuration($emailLog->created_at->diffInSeconds($emailLog->sent_at));
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            $remainingSeconds = $seconds % 60;

            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }

    /**
     * Tamaño REAL del .eml que generaría downloadRaw() (mismo contenido
     * exacto, ver buildEmlContent()) para el subtítulo de "Descargar .eml"
     * del sidebar — null cuando no hay ni cabeceras ni cuerpo capturados
     * (mismo guard que ya usa ese enlace en la vista).
     */
    private function emlSizeLabel(EmailLog $emailLog): ?string
    {
        $hasContent = $emailLog->raw_headers !== null || $emailLog->body_html || $emailLog->body_text;

        return $hasContent ? $this->formatBytes(strlen($this->buildEmlContent($emailLog))) : null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }

    private function resolveCanManage(Request $request): bool
    {
        return (bool) $request->user()?->can('helpdeskemaillog.manage');
    }

    private function resolveStaleHours(): int
    {
        return (int) Setting::get('helpdeskemaillog.stale_queued_hours', config('helpdeskemaillog.stale_queued_hours', 24));
    }

    /**
     * Días de recuperación de la papelera — mismo patrón Setting::get() con
     * fallback a config() que resolveStaleHours()/resolvePerPage(). Leído
     * también por PruneEmailLogsCommand::pruneTrash() (única fuente de
     * verdad de este número, no se duplica el default en ningún otro sitio).
     */
    private function resolveTrashRetentionDays(): int
    {
        return (int) Setting::get('helpdeskemaillog.trash_retention_days', config('helpdeskemaillog.trash_retention_days', 30));
    }

    /**
     * Resumen de aperturas para el detalle — null si este envío nunca tuvo
     * píxel de seguimiento (no confundir con "0 aperturas", que sí es un
     * dato real). Ver EmailLog::hasOpenTracking().
     *
     * @return array{count: int, likely_bot_count: int, first: ?Carbon, last: ?Carbon, recent: Collection<int, EmailLogOpen>}|null
     */
    private function opensSummary(EmailLog $emailLog): ?array
    {
        if (! $emailLog->hasOpenTracking()) {
            return null;
        }

        $agg = EmailLogOpen::query()
            ->where('email_log_id', $emailLog->id)
            ->selectRaw('COUNT(*) as total, MIN(opened_at) as first_opened_at, MAX(opened_at) as last_opened_at')
            ->first();

        $recent = $emailLog->opens()->latest('opened_at')->limit(50)->get();

        return [
            'count' => (int) ($agg->total ?? 0),
            // Sobre las cargadas (hasta 50) por rendimiento — ver
            // annotateLikelyBot(). Casi siempre coincide con el total real:
            // pocos envíos superan 50 aperturas.
            'likely_bot_count' => $this->annotateLikelyBot($recent, 'opened_at', $emailLog->sent_at),
            'first' => $agg->first_opened_at ? Carbon::parse($agg->first_opened_at) : null,
            'last' => $agg->last_opened_at ? Carbon::parse($agg->last_opened_at) : null,
            'recent' => $recent,
        ];
    }

    /**
     * Resumen de clics para el detalle — null si este envío nunca tuvo sus
     * enlaces reescritos para seguimiento (no confundir con "0 clics", que sí
     * es un dato real). Ver EmailLog::hasClickTracking().
     *
     * @return array{count: int, unique_links: int, likely_bot_count: int, first: ?Carbon, last: ?Carbon, recent: Collection<int, EmailLogClick>}|null
     */
    private function clicksSummary(EmailLog $emailLog): ?array
    {
        if (! $emailLog->hasClickTracking()) {
            return null;
        }

        $agg = EmailLogClick::query()
            ->join('email_log_links', 'email_log_links.id', '=', 'email_log_clicks.email_log_link_id')
            ->where('email_log_links.email_log_id', $emailLog->id)
            ->selectRaw('COUNT(*) as total, COUNT(DISTINCT email_log_clicks.email_log_link_id) as unique_links, MIN(clicked_at) as first_clicked_at, MAX(clicked_at) as last_clicked_at')
            ->first();

        // select() explícito sobre la relación hasManyThrough para traer
        // también la URL del enlace (columna de la tabla intermedia,
        // "link_url" en vez de "url" para no chocar con ninguna otra
        // columna propia de email_log_clicks).
        $recent = $emailLog->clicks()
            ->select('email_log_clicks.*', 'email_log_links.url as link_url')
            ->latest('clicked_at')
            ->limit(50)
            ->get();

        return [
            'count' => (int) ($agg->total ?? 0),
            'unique_links' => (int) ($agg->unique_links ?? 0),
            'likely_bot_count' => $this->annotateLikelyBot($recent, 'clicked_at', $emailLog->sent_at),
            'first' => $agg->first_clicked_at ? Carbon::parse($agg->first_clicked_at) : null,
            'last' => $agg->last_clicked_at ? Carbon::parse($agg->last_clicked_at) : null,
            'recent' => $recent,
        ];
    }

    /**
     * Marca (atributo transitorio `likely_bot`, nunca persistido) cada
     * evento que EngagementBotHeuristics considera un probable bot/proxy —
     * nunca se descarta el dato, solo se etiqueta, mismo criterio de
     * honestidad que el resto del módulo (ver notas de Apple MPP/proxy de
     * Gmail ya existentes en la vista).
     *
     * @param  Collection<int, EmailLogOpen|EmailLogClick>  $events
     * @return int cuántos de $events se marcaron como probable bot
     */
    private function annotateLikelyBot(Collection $events, string $dateField, ?Carbon $sentAt): int
    {
        $botCount = 0;

        foreach ($events as $event) {
            $isBot = EngagementBotHeuristics::isLikelyBot($event->user_agent, $sentAt, $event->{$dateField});
            $event->setAttribute('likely_bot', $isBot);

            if ($isBot) {
                $botCount++;
            }
        }

        return $botCount;
    }

    /**
     * Other emails linked to the same entity or sent to the same primary
     * recipient, most recent first (excludes the current record).
     *
     * @return Collection<int, EmailLog>
     */
    private function relatedEmails(EmailLog $emailLog): Collection
    {
        $primary = $emailLog->to_addresses[0] ?? null;
        $hasEntity = $emailLog->entity_type && $emailLog->entity_id;

        if (! $primary && ! $hasEntity) {
            return collect();
        }

        return EmailLog::query()
            ->select(['uid', 'subject', 'status', 'sent_at', 'failed_at', 'created_at', 'to_addresses'])
            ->where('id', '!=', $emailLog->id)
            ->where(function (Builder $q) use ($emailLog, $primary, $hasEntity) {
                if ($hasEntity) {
                    $q->orWhere(fn (Builder $e) => $e
                        ->where('entity_type', $emailLog->entity_type)
                        ->where('entity_id', $emailLog->entity_id));
                }

                if ($primary) {
                    // Coincidencia exacta del destinatario (evita el over-match por
                    // substring de un LIKE '%...%', p.ej. ana@x.com en diana@x.com).
                    $q->orWhereJsonContains('to_addresses', $primary);
                }
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    /**
     * Bitácora de acciones registradas sobre ESTE email (pestaña "Bitácora"
     * del detalle) — todo lo que logActivity() ya escribe en activity('email-log')
     * con performedOn($emailLog), pero que hasta ahora nadie podía ver desde
     * el propio módulo. Solo las últimas 30: es un historial de auditoría de
     * apoyo puntual, no un listado paginado propio (para eso ya existe la
     * auditoría completa de Modules\Activity, enlazada desde la vista).
     *
     * @return Collection<int, Activity>
     */
    private function activityLogFor(EmailLog $emailLog): Collection
    {
        return Activity::query()
            ->where('subject_type', EmailLog::class)
            ->where('subject_id', $emailLog->id)
            ->with('causer')
            ->latest('created_at')
            ->limit(30)
            ->get();
    }

    public function purgeBody(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('delete', $emailLog);

        $metadata = $emailLog->metadata ?? [];
        $metadata['redacted'] = true;
        $metadata['redacted_at'] = now()->toIso8601String();

        $emailLog->update([
            'body_html' => null,
            'body_text' => null,
            'metadata' => $metadata,
        ]);

        $this->logActivity('body_purged', $emailLog);

        return back()->with('success', __('helpdeskemaillog::emaillog.purge.done'));
    }

    /**
     * Vincula un email SIN entidad relacionada a un registro de otro módulo
     * (hoy, un Ticket de HelpdeskTickets — ver LinkEmailLogEntityRequest para
     * la lista de FQCN admitidos). No sobrescribe una entidad ya vinculada:
     * si el email ya tiene entity_type, esto no es "editar el vínculo", es un
     * caso aparte fuera de alcance (evita perder por accidente el vínculo
     * original con el que el resto del panel ya cuenta, p. ej. entityPanel).
     */
    public function linkEntity(LinkEmailLogEntityRequest $request, EmailLog $emailLog): RedirectResponse
    {
        if ($emailLog->entity_type !== null) {
            return back()->with('error', __('helpdeskemaillog::emaillog.link_entity.already_linked'));
        }

        $validated = $request->validated();
        $entityType = $validated['entity_type'];
        $entityId = (int) $validated['entity_id'];

        // Ventana de carrera pequeña pero real entre el buscador (searchTickets())
        // y este submit: el ticket pudo borrarse mientras el modal seguía abierto.
        if (! class_exists($entityType) || ! $entityType::query()->whereKey($entityId)->exists()) {
            return back()->with('error', __('helpdeskemaillog::emaillog.link_entity.not_found'));
        }

        $emailLog->update(['entity_type' => $entityType, 'entity_id' => $entityId]);

        $this->logActivity('entity_linked', $emailLog, ['entity_type' => $entityType, 'entity_id' => $entityId]);

        return back()->with('success', __('helpdeskemaillog::emaillog.link_entity.success'));
    }

    /**
     * Buscador de tickets para el modal "Vincular a un ticket" del sidebar —
     * reutiliza Ticket::scopeSearch() (número/asunto/cliente), el mismo scope
     * que ya usa Modules\Helpdesk\Http\Controllers\Managers\GlobalSearchController
     * para buscar tickets desde OTRO módulo satélite sin acoplarse a su
     * esquema interno: solo se conoce el FQCN del modelo y su scope público,
     * nunca una columna cruda de la tabla de tickets. Mismo guard
     * (helpdesk_tickets_enabled() + class_exists + try/catch) que ese
     * controlador: un fallo aquí nunca debe romper el detalle del email.
     */
    public function searchTickets(Request $request): JsonResponse
    {
        $this->authorize('manage', EmailLog::class);

        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2 || ! function_exists('helpdesk_tickets_enabled') || ! helpdesk_tickets_enabled() || ! class_exists(Ticket::class)) {
            return response()->json(['tickets' => []]);
        }

        try {
            $tickets = Ticket::query()
                ->search($q)
                ->with('customer:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'ticket_number', 'subject', 'customer_id'])
                ->map(fn (Ticket $ticket): array => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'customer_name' => $ticket->customer?->name,
                ]);
        } catch (Throwable) {
            $tickets = collect();
        }

        return response()->json(['tickets' => $tickets]);
    }

    public function resend(ResendEmailLogRequest $request, EmailLog $emailLog): RedirectResponse
    {
        $override = $request->validated()['to'] ?? null;
        $isTest = (bool) ($request->validated()['test'] ?? false);

        if (empty($override) && empty($emailLog->to_addresses)) {
            return back()->with('error', __('helpdeskemaillog::emaillog.resend.no_recipients'));
        }

        // El reenvío reproduce el cuerpo almacenado tal cual: si fue redactado
        // o truncado, se enviaría una copia vacía/incompleta al destinatario.
        if (! $emailLog->isResendable()) {
            return back()->with('error', __('helpdeskemaillog::emaillog.resend.blocked'));
        }

        ResendEmailLogJob::dispatch($emailLog->id, $override, $isTest);

        $this->logActivity('resent', $emailLog, $override ? ['to' => $override] : []);

        return back()->with('success', $override
            ? __('helpdeskemaillog::emaillog.resend.queued_to', ['email' => $override])
            : __('helpdeskemaillog::emaillog.resend.queued'));
    }

    /**
     * Triaje de rebotes en un solo paso (mockup): corrige el destinatario,
     * reenvía a la dirección corregida y, si se marcó, añade la dirección
     * vieja a la lista de supresión — todo en UNA acción de controlador
     * (nunca 2 peticiones encadenadas desde JS) para que nunca quede a medias
     * si el usuario cierra el modal o pierde conexión entre el reenvío y la
     * supresión.
     *
     * ResendEmailLogJob::dispatch() y EmailSuppressionService::suppress() se
     * envuelven en la misma transacción: con la cola 'database' (config por
     * defecto de la app) el job encolado solo se hace visible al worker tras
     * el COMMIT, así que un fallo posterior en la escritura de supresión
     * también revierte el encolado del reenvío — con una cola dirigida a
     * Redis el encolado ya no es transaccional (Redis no participa de la
     * transacción de MySQL), pero la escritura de supresión en sí sigue
     * siendo atómica frente a cualquier otra escritura de esta misma request.
     *
     * El motivo de supresión distingue el rebote real: 'hard_bounce' (mismo
     * motivo que ya usa el auto-supresor de EmailLogObserver para rebotes
     * duros — casi siempre esta dirección YA está suprimida cuando se llega
     * aquí, y suppress() es idempotente) o 'manual' para un rebote blando que
     * el agente decide suprimir de todas formas (EmailLogObserver nunca
     * auto-suprime un soft bounce, así que aquí es una decisión humana
     * explícita, no una reclasificación automática).
     */
    public function resolveBounce(ResolveBounceEmailLogRequest $request, EmailLog $emailLog): RedirectResponse
    {
        if ($emailLog->status !== EmailStatus::Bounced) {
            return back()->with('error', __('helpdeskemaillog::emaillog.bounce_triage.not_bounced'));
        }

        // Mismo guard que resend(): un reenvío reproduce el cuerpo almacenado
        // tal cual.
        if (! $emailLog->isResendable()) {
            return back()->with('error', __('helpdeskemaillog::emaillog.resend.blocked'));
        }

        $validated = $request->validated();
        $newAddress = $validated['to'];
        $suppressOld = (bool) ($validated['suppress_old'] ?? false);
        $oldAddress = $emailLog->to_addresses[0] ?? null;

        DB::transaction(function () use ($emailLog, $newAddress, $suppressOld, $oldAddress): void {
            ResendEmailLogJob::dispatch($emailLog->id, $newAddress);

            if ($suppressOld && $oldAddress) {
                $reason = $emailLog->bounceType() === 'hard' ? SuppressionReason::HardBounce : SuppressionReason::Manual;

                app(EmailSuppressionService::class)->suppress(
                    email: $oldAddress,
                    reason: $reason,
                    module: null,
                    emailLog: $emailLog,
                );
            }
        });

        $this->logActivity('bounce_resolved', $emailLog, [
            'old_to' => $oldAddress,
            'new_to' => $newAddress,
            'suppressed_old' => $suppressOld && $oldAddress !== null,
        ]);

        return back()->with('success', __('helpdeskemaillog::emaillog.bounce_triage.success', ['email' => $newAddress]));
    }

    public function bulkResend(BulkResendEmailLogsRequest $request): RedirectResponse
    {
        // No se cargan las columnas de cuerpo (pesadas) para hasta 200 filas:
        // aquí se filtra por los flags de metadata y el job vuelve a comprobar
        // isResendable() sobre la fila completa (cubre filas antiguas cuyo
        // truncado solo es detectable por el marcador en el cuerpo).
        $logs = EmailLog::query()
            ->select(['id', 'uid', 'to_addresses', 'metadata'])
            ->whereIn('uid', $request->validated('uids'))
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($logs as $log) {
            // Se omiten registros sin destinatarios y aquellos cuyo cuerpo
            // almacenado está redactado o truncado (véase resend()).
            if (empty($log->to_addresses) || ! $log->isResendable()) {
                $skipped++;

                continue;
            }

            ResendEmailLogJob::dispatch($log->id);
            $queued++;
        }

        $this->logActivity('bulk_resent', null, ['count' => $queued, 'skipped' => $skipped]);

        $message = __('helpdeskemaillog::emaillog.resend.bulk_queued', ['count' => $queued]);

        if ($skipped > 0) {
            $message .= ' '.__('helpdeskemaillog::emaillog.resend.bulk_skipped', ['count' => $skipped]);
        }

        return back()->with('success', $message);
    }

    public function download(EmailLog $emailLog): Response
    {
        $this->authorize('view', $emailLog);

        $body = $emailLog->body_html
            ?: ($emailLog->body_text ? '<pre>'.e($emailLog->body_text).'</pre>' : null);

        abort_if($body === null, 404);

        $this->logActivity('downloaded', $emailLog);

        $filename = 'email-'.substr($emailLog->uid, 0, 8).'.html';

        return response($body, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Descarga un .eml reconstruido desde raw_headers + body_html/body_text
     * — no se guarda un raw_source completo aparte (duplicaría el cuerpo,
     * que ya vive en su propia columna sujeta a truncado/redacción). Filas
     * sin raw_headers (creadas antes de esta columna, o con el cuerpo
     * purgado) reciben cabeceras mínimas sintetizadas desde las columnas
     * estructuradas, marcadas explícitamente como reconstrucción.
     */
    public function downloadRaw(EmailLog $emailLog): Response
    {
        $this->authorize('view', $emailLog);

        $hasContent = $emailLog->raw_headers !== null || $emailLog->body_html || $emailLog->body_text;

        abort_if(! $hasContent, 404);

        $this->logActivity('downloaded_raw', $emailLog);

        $filename = 'email-'.substr($emailLog->uid, 0, 8).'.eml';

        return response($this->buildEmlContent($emailLog), 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function buildEmlContent(EmailLog $emailLog): string
    {
        $headers = $emailLog->raw_headers;

        if ($headers === null) {
            $lines = [
                'X-HelpdeskEmailLog-Note: Cabeceras reconstruidas — no es una captura verbatim del envío original.',
                'From: '.($emailLog->from_name ? "{$emailLog->from_name} <{$emailLog->from_address}>" : $emailLog->from_address),
                'To: '.implode(', ', $emailLog->to_addresses ?? []),
            ];

            if ($emailLog->message_id) {
                $lines[] = "Message-ID: <{$emailLog->message_id}>";
            }

            $lines[] = 'Subject: '.$emailLog->subject;
            $lines[] = 'Date: '.($emailLog->created_at?->toRfc2822String() ?? '');
            $headers = implode("\r\n", $lines);
        }

        $body = $emailLog->body_html ?: ($emailLog->body_text ?? '');

        if (! empty($emailLog->attachments)) {
            // Los adjuntos solo guardan metadatos (nombre/tamaño/tipo), nunca
            // el binario — un .eml reconstruido no puede incluirlos de verdad.
            $body .= "\r\n\r\n[Nota: este .eml no incluye los adjuntos originales — solo se conservaron sus metadatos.]";
        }

        return $headers."\r\n\r\n".$body;
    }

    /**
     * "Papelera de registros" (mockup): esto ya NO es un borrado definitivo
     * — EmailLog::class usa SoftDeletes, así que delete() solo rellena
     * deleted_at (fila recuperable vía restore(), ver más abajo) hasta que
     * PruneEmailLogsCommand::pruneTrash() la borre de verdad tras
     * `helpdeskemaillog.trash_retention_days` días.
     */
    public function destroy(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('delete', $emailLog);

        $this->logActivity('deleted', $emailLog);
        $emailLog->delete();

        return redirect()
            ->route('helpdeskemaillog.index')
            ->with('success', __('helpdeskemaillog::emaillog.deleted.one'));
    }

    /**
     * Mismo borrado lógico que destroy() (ver su docblock) — el ->delete()
     * de una query masiva sobre un modelo con SoftDeletes ya hace un UPDATE
     * poniendo deleted_at, nunca un DELETE físico, sin cambio de código aquí.
     */
    public function bulkDestroy(BulkDeleteEmailLogsRequest $request): RedirectResponse
    {
        // Consistencia con destroy() (que sí llama authorize('delete')): la
        // policy deleteAny() ya existía pero no se invocaba desde el controller.
        $this->authorize('deleteAny', EmailLog::class);

        $deleted = EmailLog::query()->whereIn('uid', $request->validated('uids'))->delete();

        if ($deleted > 0) {
            EmailLog::forgetDashboardCaches();
        }

        $this->logActivity('bulk_deleted', null, ['count' => $deleted]);

        return back()->with('success', __('helpdeskemaillog::emaillog.deleted.many', ['count' => $deleted]));
    }

    /**
     * Listado de la papelera (registros con soft-delete, ver destroy()) —
     * mismas columnas ligeras que buildListData() (EmailLog::LIST_COLUMNS)
     * más deleted_at, que es lo único nuevo que esta pantalla necesita
     * mostrar (fecha de borrado + cuenta atrás de retención). Solo
     * 'helpdeskemaillog.manage': quien no puede borrar tampoco necesita ver
     * lo ya borrado.
     */
    public function trash(Request $request): View
    {
        $this->authorize('manage', EmailLog::class);

        abort_if(! helpdesk_emaillog_enabled(), 404);

        $perPage = $this->resolvePerPage($request);

        $logs = EmailLog::onlyTrashed()
            ->select([...EmailLog::LIST_COLUMNS, 'deleted_at'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $q) => $q->where('subject', 'like', $like)
                    ->orWhere('from_address', 'like', $like)
                    ->orWhere('recipients_index', 'like', $like));
            })
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('helpdeskemaillog::emails.trash', [
            'logs' => $logs,
            'retentionDays' => $this->resolveTrashRetentionDays(),
            'perPage' => $perPage,
            'perPageOptions' => config('helpdeskemaillog.per_page_options', [25]),
        ]);
    }

    /**
     * Recupera un registro de la papelera — EmailLog::restore() ya dispara
     * el evento 'updated' del modelo (ver SoftDeletes::restore(), que llama
     * a save()), así que EmailLog::booting() invalida la caché del
     * dashboard sola, sin necesidad de un forgetDashboardCaches() explícito
     * aquí (a diferencia de bulkRestore(), que opera por query masiva).
     */
    public function restore(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('restore', $emailLog);

        // La ruta usa ->withTrashed() (necesario para que el binding
        // implícito encuentre el registro), así que también resuelve un uid
        // que NUNCA pasó por la papelera — sin este guard, restaurar algo
        // que ya está activo sería un no-op silencioso en vez de un 404
        // honesto.
        abort_unless($emailLog->trashed(), 404);

        $emailLog->restore();

        $this->logActivity('restored', $emailLog);

        return back()->with('success', __('helpdeskemaillog::emaillog.trash.restored.one'));
    }

    public function bulkRestore(BulkRestoreEmailLogsRequest $request): RedirectResponse
    {
        $this->authorize('restoreAny', EmailLog::class);

        $restored = EmailLog::onlyTrashed()
            ->whereIn('uid', $request->validated('uids'))
            ->restore();

        if ($restored > 0) {
            EmailLog::forgetDashboardCaches();
        }

        $this->logActivity('bulk_restored', null, ['count' => $restored]);

        return back()->with('success', __('helpdeskemaillog::emaillog.trash.restored.many', ['count' => $restored]));
    }

    /**
     * Borrado definitivo explícito desde la papelera — a diferencia de
     * destroy(), este SÍ es irreversible (mismo forceDelete() que usa el
     * borrado GDPR, ver Modules\HelpdeskCompliance\Services\Handlers\
     * EmailLogComplianceHandler). Solo alcanzable sobre un registro ya en
     * la papelera (ruta con ->withTrashed(), ver routes/web.php): no ofrece
     * un atajo para saltarse el paso por destroy() primero.
     */
    public function forceDestroy(EmailLog $emailLog): RedirectResponse
    {
        $this->authorize('forceDelete', $emailLog);

        // Mismo motivo que restore(): la ruta usa ->withTrashed(), así que
        // sin este guard se podría purgar para siempre un registro que
        // nunca pasó por destroy()/la papelera, saltándose ese primer paso.
        abort_unless($emailLog->trashed(), 404);

        $this->logActivity('force_deleted', $emailLog);
        $emailLog->forceDelete();

        return redirect()
            ->route('helpdeskemaillog.trash.index')
            ->with('success', __('helpdeskemaillog::emaillog.trash.force_deleted.one'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', EmailLog::class);

        $rows = $this->applyFilters(EmailLog::query()->select(self::EXPORT_COLUMNS), $request)
            ->withCount(['opens', 'clicks'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::EXPORT_HARD_LIMIT)
            ->cursor();

        return $this->streamCsv($rows);
    }

    /**
     * Mismo CSV que export(), pero solo para los uids marcados en el listado
     * (checkbox) — misma autorización ('export', igual permiso que ya exige
     * export() hoy: helpdeskemaillog.view) y mismo builder de filas, sin
     * duplicar el formato. Sin límite de paginación propio: la validación del
     * FormRequest ya acota la selección a 1000 uids.
     */
    public function exportSelected(ExportSelectedEmailLogsRequest $request): StreamedResponse
    {
        $this->authorize('export', EmailLog::class);

        $rows = EmailLog::query()
            ->select(self::EXPORT_COLUMNS)
            ->withCount(['opens', 'clicks'])
            ->whereIn('uid', $request->validated('uids'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursor();

        return $this->streamCsv($rows);
    }

    /**
     * @param  iterable<EmailLog>  $rows
     */
    private function streamCsv(iterable $rows): StreamedResponse
    {
        $filename = 'email-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(
                fn (string $key): string => __("helpdeskemaillog::emaillog.csv.{$key}"),
                ['uid', 'date', 'sent_at', 'status', 'subject', 'from', 'to', 'cc', 'module', 'entity', 'mailable', 'opens', 'clicks', 'error']
            ));

            foreach ($rows as $log) {
                fputcsv($out, [
                    $log->uid,
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->sent_at?->format('Y-m-d H:i:s'),
                    $log->status?->value,
                    $log->subject,
                    $log->from_address,
                    implode(', ', $log->to_addresses ?? []),
                    implode(', ', $log->cc_addresses ?? []),
                    $log->module,
                    $log->entity_type ? $log->entity_type.' #'.$log->entity_id : null,
                    $log->mailable_class ? class_basename($log->mailable_class) : null,
                    // "" (sin seguimiento) distinto de "0" (con seguimiento,
                    // cero interacciones) — mismo criterio de honestidad que
                    // la columna "Interacción" del listado.
                    $log->hasOpenTracking() ? (string) $log->opens_count : '',
                    $log->hasClickTracking() ? (string) $log->clicks_count : '',
                    $log->error_message,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{total: int, sent: int, failed: int, queued: int, today: int, bounced: int, complained: int, open_tracked: int, opened: int, click_tracked: int, clicked: int}
     */
    private function computeStats(): array
    {
        $aggregate = EmailLog::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(status = 'sent') AS sent")
            ->selectRaw("SUM(status = 'failed') AS failed")
            ->selectRaw("SUM(status = 'queued') AS queued")
            ->selectRaw("SUM(status = 'bounced') AS bounced")
            ->selectRaw("SUM(status = 'complained') AS complained")
            ->selectRaw('SUM(created_at >= ?) AS today', [today()->toDateTimeString()])
            ->first();

        // Denominador = envíos que SÍ tuvieron seguimiento, no el total —
        // una tasa sobre el total mezclaría "nadie lo abrió" con "nunca se
        // pudo saber" (mismo criterio de honestidad que hasOpenTracking()).
        $openTracked = EmailLog::query()->where('metadata->open_tracking_enabled', true);
        $openTrackedTotal = (clone $openTracked)->count();
        $openedTotal = (clone $openTracked)->whereHas('opens')->count();

        $clickTracked = EmailLog::query()->where('metadata->click_tracking_enabled', true);
        $clickTrackedTotal = (clone $clickTracked)->count();
        $clickedTotal = (clone $clickTracked)->whereHas('clicks')->count();

        return [
            'total' => (int) ($aggregate->total ?? 0),
            'sent' => (int) ($aggregate->sent ?? 0),
            'failed' => (int) ($aggregate->failed ?? 0),
            'queued' => (int) ($aggregate->queued ?? 0),
            'bounced' => (int) ($aggregate->bounced ?? 0),
            'complained' => (int) ($aggregate->complained ?? 0),
            'today' => (int) ($aggregate->today ?? 0),
            'open_tracked' => $openTrackedTotal,
            'opened' => $openedTotal,
            'click_tracked' => $clickTrackedTotal,
            'clicked' => $clickedTotal,
        ];
    }

    /**
     * Delta de cada métrica del dashboard frente al periodo inmediatamente
     * anterior de igual duración:
     * - Con date_from activo (ver applyFilters()), el periodo "actual" es
     *   [date_from, date_to ?? ahora] y el "anterior" la misma duración justo
     *   antes de date_from.
     * - Sin filtro de fecha, se usa la ventana por defecto de 14 días (misma
     *   que el gráfico de tendencia, ver computeTrend()) vs los 14 días previos.
     *
     * 'today' es la única métrica que no seguí este periodo general: su
     * propia definición en computeStats() ya es fija ("desde la medianoche de
     * hoy", sin importar ningún filtro), así que su comparación natural es
     * siempre "hoy vs ayer completo", no la ventana general de arriba.
     *
     * @return array<string, array{current: int|float|null, previous: int|float|null, diff: int|float|null, diff_percent: ?float, direction: string, positive: ?bool}>
     */
    private function computeStatsDelta(Request $request): array
    {
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->resolveDeltaPeriods($request);

        $current = $this->periodStats($currentFrom, $currentTo);
        $previous = $this->periodStats($previousFrom, $previousTo);

        $todayStart = today();
        $yesterdayStart = $todayStart->copy()->subDay();
        $todayCount = EmailLog::query()->where('created_at', '>=', $todayStart)->count();
        $yesterdayCount = EmailLog::query()
            ->whereBetween('created_at', [$yesterdayStart, $todayStart->copy()->subSecond()])
            ->count();

        return [
            // Volumen puro: más o menos envíos no es en sí mismo bueno ni
            // malo, solo informativo — sin polaridad (positive siempre null).
            'total' => $this->buildDelta($current['total'], $previous['total'], higherIsBetter: null),
            'sent' => $this->buildDelta($current['sent'], $previous['sent'], higherIsBetter: true),
            'delivered' => $this->buildDelta($current['delivered'], $previous['delivered'], higherIsBetter: true),
            'failed' => $this->buildDelta($current['failed'], $previous['failed'], higherIsBetter: false),
            'bounced' => $this->buildDelta($current['bounced'], $previous['bounced'], higherIsBetter: false),
            // "Spam": no existe un status propio — la queja del destinatario
            // ES el status 'complained' en este módulo (ver EmailStatus).
            'complained' => $this->buildDelta($current['complained'], $previous['complained'], higherIsBetter: false),
            // En cola es un estado de tránsito, no un resultado — una subida
            // no es necesariamente un problema (podría ser solo más volumen
            // a punto de enviarse), así que tampoco lleva polaridad.
            'queued' => $this->buildDelta($current['queued'], $previous['queued'], higherIsBetter: null),
            'today' => $this->buildDelta($todayCount, $yesterdayCount, higherIsBetter: null),
            'delivery_rate' => $this->buildDelta(
                $this->rateFrom($current['sent'], $current['total']),
                $this->rateFrom($previous['sent'], $previous['total']),
                higherIsBetter: true,
            ),
            'open_rate' => $this->buildDelta(
                $this->rateFrom($current['opened'], $current['open_tracked']),
                $this->rateFrom($previous['opened'], $previous['open_tracked']),
                higherIsBetter: true,
            ),
            'click_rate' => $this->buildDelta(
                $this->rateFrom($current['clicked'], $current['click_tracked']),
                $this->rateFrom($previous['clicked'], $previous['click_tracked']),
                higherIsBetter: true,
            ),
        ];
    }

    /**
     * [currentFrom, currentTo, previousFrom, previousTo] — ver
     * computeStatsDelta() para el criterio de "periodo actual" y "anterior".
     *
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    private function resolveDeltaPeriods(Request $request): array
    {
        $from = $this->parseDateFilter($request->input('date_from'));

        if ($from) {
            $currentFrom = $from->copy()->startOfDay();
            $to = $this->parseDateFilter($request->input('date_to'));
            $currentTo = $to ? $to->copy()->endOfDay() : now();
        } else {
            $currentFrom = today()->subDays(self::DELTA_DEFAULT_WINDOW_DAYS - 1);
            $currentTo = now();
        }

        // Duración en segundos (no en días redondos): "ahora" trae hora del
        // día, así que el periodo anterior debe conservar esa misma duración
        // exacta, no solo el mismo número de días de calendario.
        $durationSeconds = max(1, abs($currentTo->getTimestamp() - $currentFrom->getTimestamp()));
        $previousTo = $currentFrom->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subSeconds($durationSeconds);

        return [$currentFrom, $currentTo, $previousFrom, $previousTo];
    }

    /**
     * Mismas métricas que computeStats(), pero acotadas a un periodo por
     * created_at (igual convención que computeTrend()) — además de
     * 'delivered' (delivered_at no nulo), que computeStats() todavía no
     * expone como campo propio.
     *
     * @return array{total: int, sent: int, delivered: int, failed: int, bounced: int, complained: int, queued: int, open_tracked: int, opened: int, click_tracked: int, clicked: int}
     */
    private function periodStats(Carbon $from, Carbon $to): array
    {
        $aggregate = EmailLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(status = 'sent') AS sent")
            ->selectRaw("SUM(status = 'failed') AS failed")
            ->selectRaw("SUM(status = 'bounced') AS bounced")
            ->selectRaw("SUM(status = 'complained') AS complained")
            ->selectRaw("SUM(status = 'queued') AS queued")
            ->selectRaw('SUM(delivered_at IS NOT NULL) AS delivered')
            ->first();

        $openTracked = EmailLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('metadata->open_tracking_enabled', true);
        $openTrackedTotal = (clone $openTracked)->count();
        $openedTotal = (clone $openTracked)->whereHas('opens')->count();

        $clickTracked = EmailLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('metadata->click_tracking_enabled', true);
        $clickTrackedTotal = (clone $clickTracked)->count();
        $clickedTotal = (clone $clickTracked)->whereHas('clicks')->count();

        return [
            'total' => (int) ($aggregate->total ?? 0),
            'sent' => (int) ($aggregate->sent ?? 0),
            'delivered' => (int) ($aggregate->delivered ?? 0),
            'failed' => (int) ($aggregate->failed ?? 0),
            'bounced' => (int) ($aggregate->bounced ?? 0),
            'complained' => (int) ($aggregate->complained ?? 0),
            'queued' => (int) ($aggregate->queued ?? 0),
            'open_tracked' => $openTrackedTotal,
            'opened' => $openedTotal,
            'click_tracked' => $clickTrackedTotal,
            'clicked' => $clickedTotal,
        ];
    }

    /**
     * Tasa porcentual redondeada a 1 decimal, o null cuando el denominador es
     * 0 — a diferencia de la tasa de entrega que ya pinta la vista (que cae a
     * "0%" porque el total global casi nunca es 0), un periodo concreto SÍ
     * puede no tener ningún envío: "sin datos" es más honesto que un "0%" que
     * insinuaría un intento fallido (mismo criterio que hasOpenTracking()).
     */
    private function rateFrom(int $numerator, int $denominator): ?float
    {
        return $denominator > 0 ? round($numerator / $denominator * 100, 1) : null;
    }

    /**
     * @return array{current: int|float|null, previous: int|float|null, diff: int|float|null, diff_percent: ?float, direction: string, positive: ?bool}
     */
    private function buildDelta(int|float|null $current, int|float|null $previous, ?bool $higherIsBetter): array
    {
        // Sin dato en alguno de los dos periodos (p. ej. una tasa de apertura
        // sin ningún envío con seguimiento): no hay delta real que mostrar,
        // solo se preservan los valores tal cual.
        if ($current === null || $previous === null) {
            return [
                'current' => $current,
                'previous' => $previous,
                'diff' => null,
                'diff_percent' => null,
                'direction' => 'flat',
                'positive' => null,
            ];
        }

        $diff = $current - $previous;
        $diffPercent = $previous > 0 ? round(($diff / $previous) * 100, 1) : null;

        $direction = match (true) {
            $diff > 0 => 'up',
            $diff < 0 => 'down',
            default => 'flat',
        };

        // Sin cambio, o métrica sin polaridad definida (ver comentarios de
        // computeStatsDelta() para 'total'/'queued'/'today'): ni verde ni
        // rojo, la vista debe pintarlo neutro.
        $positive = ($direction === 'flat' || $higherIsBetter === null)
            ? null
            : $higherIsBetter === ($direction === 'up');

        return [
            'current' => $current,
            'previous' => $previous,
            'diff' => $diff,
            'diff_percent' => $diffPercent,
            'direction' => $direction,
            'positive' => $positive,
        ];
    }

    /**
     * Daily counts per status for the last N days (for the trend chart).
     *
     * @return array{labels: list<string>, sent: list<int>, failed: list<int>, queued: list<int>, bounced: list<int>, complained: list<int>}
     */
    private function computeTrend(int $days = self::DELTA_DEFAULT_WINDOW_DAYS): array
    {
        $since = today()->subDays($days - 1);

        $rows = EmailLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d')
            ->selectRaw("SUM(status = 'sent') AS sent")
            ->selectRaw("SUM(status = 'failed') AS failed")
            ->selectRaw("SUM(status = 'queued') AS queued")
            ->selectRaw("SUM(status = 'bounced') AS bounced")
            ->selectRaw("SUM(status = 'complained') AS complained")
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('d');

        $labels = $sent = $failed = $queued = $bounced = $complained = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i);
            $row = $rows->get($date->toDateString());
            $labels[] = $date->format('d/m');
            $sent[] = (int) ($row->sent ?? 0);
            $failed[] = (int) ($row->failed ?? 0);
            $queued[] = (int) ($row->queued ?? 0);
            $bounced[] = (int) ($row->bounced ?? 0);
            $complained[] = (int) ($row->complained ?? 0);
        }

        return compact('labels', 'sent', 'failed', 'queued', 'bounced', 'complained');
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('module')) {
            $query->forModule((string) $request->input('module'));
        }

        if ($request->filled('status')) {
            $query->status((string) $request->input('status'));
        }

        // Agente (quién lo envió) — causer_type siempre App\Models\User en la
        // práctica (ver computeAgentOptions()); se acota igualmente por tipo
        // para no confundir un id de usuario con el de otro causer morph.
        if ($request->filled('causer_id')) {
            $query->where('causer_id', (int) $request->input('causer_id'))->where('causer_type', User::class);
        }

        // Buzón remitente — coincidencia exacta (el select solo ofrece
        // valores que ya existen en la columna, ver 'fromAddresses' en
        // buildListData()).
        if ($request->filled('from_address')) {
            $query->where('from_address', (string) $request->input('from_address'));
        }

        // "Solo con adjuntos" — ver EmailLog::scopeHasAttachments() (mismo
        // criterio que el accessor has_attachments, evaluado en SQL).
        if ($request->boolean('has_attachments')) {
            $query->hasAttachments();
        }

        // Filtro por entidad relacionada (p.ej. un ticket concreto) — el
        // enlace "ver todos los emails de este ticket" lo arma el módulo
        // dueño de la entidad (HelpdeskTickets), nunca al revés: este
        // controlador no conoce ningún FQCN de módulo concreto, solo el par
        // genérico entity_type/entity_id que ya usa EmailLog::scopeForEntity().
        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $query->forEntity((string) $request->input('entity_type'), (string) $request->input('entity_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $like = '%'.addcslashes($search, '%_\\').'%';

            // Nota (revisión de rendimiento, ago-2026): se evaluó quitar el
            // `orWhere('recipients_index', 'like', $like)` por parecer
            // redundante con el MATCH...AGAINST de la misma columna — pero se
            // revirtió tras comprobar en vivo que MATCH en modo lenguaje
            // natural es mucho MENOS preciso que el LIKE (coincide con
            // cualquier fila que comparta un token suelto, p.ej. "example"/
            // "test" de un dominio de prueba, devolviendo decenas de
            // resultados no relacionados) — el LIKE es el que de verdad
            // garantiza la coincidencia exacta de subcadena que el buscador
            // de destinatarios necesita. La query sigue sin poder usar
            // índice en esta rama del OR (LIKE con comodín inicial); una
            // solución real requeriría MATCH en modo booleano con todos los
            // tokens como obligatorios, un cambio de comportamiento mayor
            // fuera del alcance de este arreglo puntual.
            $query->where(function (Builder $q) use ($search, $like) {
                $q->where('subject', 'like', $like)
                    ->orWhere('from_address', 'like', $like)
                    ->orWhere('from_name', 'like', $like)
                    ->orWhereRaw('MATCH(recipients_index) AGAINST (?)', [$search])
                    ->orWhere('recipients_index', 'like', $like);
            });
        }

        if ($from = $this->parseDateFilter($request->input('date_from'))) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $this->parseDateFilter($request->input('date_to'))) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        // "sin abrir"/"sin clic" solo tienen sentido sobre envíos que SÍ
        // tuvieron seguimiento (metadata->*_tracking_enabled) — de lo
        // contrario "sin abrir" incluiría también todo lo que nunca tuvo
        // píxel, mezclando "no lo vio" con "no se pudo saber".
        if ($request->filled('engagement')) {
            match ($request->input('engagement')) {
                'opened' => $query->whereHas('opens'),
                'not_opened' => $query->where('metadata->open_tracking_enabled', true)->doesntHave('opens'),
                'clicked' => $query->whereHas('clicks'),
                'not_clicked' => $query->where('metadata->click_tracking_enabled', true)->doesntHave('clicks'),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Parse a user-supplied date filter defensively: a malformed value (or a
     * non-string, e.g. ?date_from[]=x) must not turn into a 500 — the filter
     * is simply ignored.
     */
    private function parseDateFilter(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{string, string} [column, direction] */
    private function resolveSort(Request $request): array
    {
        $key = (string) $request->input('sort_by', 'date');
        $col = self::SORTABLE[$key] ?? 'created_at';
        $dir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        return [$col, $dir];
    }

    private function resolvePerPage(Request $request): int
    {
        $default = (int) Setting::get('helpdeskemaillog.per_page', config('helpdeskemaillog.per_page', 25));
        $options = array_map('intval', (array) config('helpdeskemaillog.per_page_options', [$default]));
        $requested = (int) $request->input('per_page');

        return in_array($requested, $options, true) ? $requested : $default;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logActivity(string $event, ?EmailLog $emailLog = null, array $properties = []): void
    {
        rescue(function () use ($event, $emailLog, $properties) {
            $logger = activity('email-log')->event($event)->withProperties($properties + ['ip' => request()->ip()]);

            if ($emailLog) {
                $logger->performedOn($emailLog);
            }

            $logger->log('email-log.'.$event);
        }, report: false);
    }
}
