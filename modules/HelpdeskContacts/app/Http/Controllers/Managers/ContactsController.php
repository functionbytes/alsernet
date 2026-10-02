<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Erp\Http\Controllers\Api\CustomerController as ErpCustomerController;
use Modules\Helpdesk\Jobs\SendBulkHsmTemplateJob;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerTag;
use Modules\Helpdesk\Services\CustomerInsightsService;
use Modules\Helpdesk\Services\HsmConversationService;
use Modules\Helpdesk\Services\PhoneNormalizerService;
use Modules\HelpdeskContacts\Http\Requests\Managers\BulkContactActionRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\BulkSendHsmRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\ExternalIntegrationRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\ExternalPreviewRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\ExternalSearchRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\ImportContactsRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\SendHsmRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\UpdateContactRequest;
use Modules\HelpdeskContacts\Jobs\ExportContactsJob;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskContacts\Services\ContactCsvWriter;
use Modules\HelpdeskContacts\Services\ContactOwnerCatalog;
use Modules\HelpdeskContacts\Support\ContactLayouts;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactsController extends Controller
{
    /**
     * Split list/detail page with a paginated, searchable customer list.
     * Authorization is handled by the route middleware (can:contacts.view).
     */
    public function index(Request $request, CustomerInsightsService $insights, ContactAggregatorService $aggregator, ContactOwnerCatalog $ownerCatalog): View
    {
        $perPage = in_array((int) $request->input('per_page'), [15, 25, 50, 100]) ? (int) $request->input('per_page') : 25;
        $view = in_array($request->input('view'), self::VIEWS, true) ? $request->input('view') : 'all';

        // Orden fijo (sin sorting por columna en la UI) — mismo patrón que
        // UsersController::index(), que usa latest() sin parámetros de sort.
        // withCount() en vez de leer total_conversations: esa columna solo se
        // incrementa (Customer::incrementConversationCount()) y nunca se
        // decrementa al borrar/reasignar conversaciones — encontrada
        // desincronizada en vivo (mostraba 15 con 0 conversaciones reales).
        $query = $this->applyFilters(Customer::query()->forAgent($request->user()), $request);
        $this->applyView($query, $view, $request->user(), $aggregator);

        $customers = $query
            ->withCount('conversations')
            ->with(['company:id,name', 'tags:id,name,color'])
            ->orderByDesc('last_seen_at')
            ->paginate($perPage)
            ->appends($request->query());

        $selected = $request->filled('selected')
            ? Customer::query()->forAgent($request->user())->whereKey($request->integer('selected'))->first()
            : null;

        // Salud y valor de la página actual — la de salud ya es batch
        // (healthScoresFor, 4 consultas agrupadas); el valor de vida
        // (lifetimeOrders) no lo es (busca por email en Remarketing) y se
        // resuelve por fila, tolerable acotado a 100 filas máx por página.
        $healthScores = $insights->healthScoresFor($customers->pluck('id')->all());
        $values = $customers->getCollection()->mapWithKeys(
            fn (Customer $c) => [$c->id => $aggregator->lifetimeOrders($c)]
        );

        // Totales globales del alcance del agente (no del resultado filtrado/
        // paginado) — mismo criterio que UsersController::index(), y mismas
        // queries que ya usa reports() para "verificados"/"suspendidos"/"en riesgo".
        // Cacheado 2 min por agente: forAgent() añade un WHERE EXISTS contra
        // conversations/inboxes que se repetía varias veces en cada carga de la
        // página más visitada del módulo, sin necesitar frescura al segundo.
        $stats = Cache::remember(
            "helpdeskcontacts:index-stats:{$request->user()->id}",
            120,
            function () use ($request, $aggregator): array {
                $scoped = fn () => Customer::query()->forAgent($request->user());

                return [
                    'total' => $scoped()->count(),
                    'verified' => $scoped()->whereNotNull('email_verified_at')->count(),
                    'banned' => $scoped()->whereNotNull('banned_at')->count(),
                    'risk' => $this->applyView($scoped(), 'risk')->count(),
                    'vip' => $scoped()->vip()->count(),
                    'duplicates' => count($aggregator->duplicateCustomerIds($request->user())),
                    'new' => $scoped()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ];
            },
        );

        // "Mismo teléfono/email que X" por fila, solo en la vista de duplicados
        // y en un número fijo de consultas (ver duplicateReasonsFor()).
        $duplicateMatches = $view === 'duplicates'
            ? $aggregator->duplicateMatchesFor($customers->getCollection(), $request->user())
            : [];
        $duplicateReasons = $view === 'duplicates'
            ? array_map(fn (array $m): string => $m['reason'], $duplicateMatches)
            : [];

        return view('contacts::contacts.index', [
            'customers' => $customers,
            'selected' => $selected,
            'q' => $request->string('q')->trim()->toString(),
            'filters' => $request->only(['q', 'channel', 'last_seen', 'verified', 'banned']),
            'perPage' => $perPage,
            'stats' => $stats,
            'view' => $view,
            'healthScores' => $healthScores,
            'values' => $values,
            'duplicateReasons' => $duplicateReasons,
            'duplicateMatches' => $duplicateMatches,
            'allTags' => CustomerTag::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'origins' => self::ORIGINS,
            'owners' => $ownerCatalog->options(),
        ]);
    }

    /**
     * Vistas guardadas soportadas por el listado (mockup "Vistas"): 'vip'
     * filtra por la columna is_vip (Customer::scopeVip()) y 'duplicates' por
     * los IDs que devuelve ContactAggregatorService::duplicateCustomerIds()
     * (coincidencia exacta de email o de los últimos 9 dígitos de teléfono).
     */
    private const VIEWS = ['all', 'risk', 'vip', 'duplicates', 'banned'];

    /**
     * Apply one of the saved list views on top of the already-filtered query.
     * "risk" reuses the exact criterion reports() already scores contacts by.
     * "duplicates" necesita el agente (scope de bandeja) y el agregador, así
     * que solo se puede resolver cuando el llamador los pasa.
     */
    private function applyView(Builder $query, string $view, ?User $user = null, ?ContactAggregatorService $aggregator = null): Builder
    {
        return match ($view) {
            'risk' => $query->where(fn (Builder $q) => $q
                ->where('last_seen_at', '<', now()->subDays(30))
                ->orWhereNull('last_seen_at')),
            'banned' => $query->whereNotNull('banned_at'),
            'vip' => $query->vip(),
            'duplicates' => $user === null
                ? $query
                : $query->whereIn('id', ($aggregator ?? app(ContactAggregatorService::class))->duplicateCustomerIds($user)),
            default => $query,
        };
    }

    /**
     * 360 tab-shell page for a single customer.
     * The customer is resolved via implicit binding on the 'helpdesk' connection.
     */
    public function show(Customer $customer, Request $request): View
    {
        $this->assertVisible($customer);

        // ?action=<x> desde el listado (botones/menú de fila): la ficha abre
        // al cargar el modal que ya existe allí. Whitelist estricta — cualquier
        // otro valor se descarta en silencio, nunca se refleja tal cual.
        $action = $request->query('action');

        return view('contacts::contacts.show', [
            'customer' => $customer,
            'bannedBy' => $customer->banned_at ? $this->bannedBy($customer) : null,
            'autoAction' => is_string($action) && in_array($action, self::AUTO_ACTIONS, true) ? $action : null,
            // ?layout= previsualiza un estilo sin guardarlo (whitelist en ContactLayouts).
            'layout' => ContactLayouts::current(is_string($request->query('layout')) ? $request->query('layout') : null),
        ]);
    }

    /**
     * Lista lateral del estilo "Maestro-detalle": los contactos del alcance
     * del agente, más recientes primero, con búsqueda y filtros rápidos.
     */
    public function rail(Request $request): JsonResponse
    {
        $view = (string) $request->query('view', 'all');
        $term = trim((string) $request->query('q', ''));

        $query = Customer::query()
            ->forAgent($request->user())
            ->withCount(['conversations as open_conversations_count' => fn ($q) => $q->open()]);

        if ($term !== '') {
            $query->search(mb_substr($term, 0, 100));
        }

        match ($view) {
            'open' => $query->whereHas('conversations', fn ($q) => $q->open()),
            'vip' => $query->vip(),
            'risk' => $this->applyView($query, 'risk'),
            default => null,
        };

        $contacts = $query
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit(40)
            ->get(['id', 'name', 'email', 'phone', 'whatsapp_phone', 'is_vip', 'last_seen_at', 'banned_at']);

        // El contacto abierto siempre en la lista (J/K parten de él), aunque
        // no esté entre los 40 más recientes. Solo sin búsqueda ni filtro.
        $currentId = $request->integer('current');
        if ($currentId > 0 && $term === '' && $view === 'all' && ! $contacts->contains('id', $currentId)) {
            $current = Customer::query()
                ->forAgent($request->user())
                ->withCount(['conversations as open_conversations_count' => fn ($q) => $q->open()])
                ->whereKey($currentId)
                ->first(['id', 'name', 'email', 'phone', 'whatsapp_phone', 'is_vip', 'last_seen_at', 'banned_at']);

            if ($current) {
                $contacts->prepend($current);
            }
        }

        return response()->json([
            'data' => $contacts->map(fn (Customer $c): array => [
                'id' => $c->id,
                'name' => $c->name ?: 'Sin nombre',
                'initials' => $c->initials,
                'sub' => $c->email ?: ($c->phone ?: $c->whatsapp_phone),
                'isVip' => (bool) $c->is_vip,
                'isBanned' => $c->banned_at !== null,
                'open' => (int) $c->open_conversations_count,
                'lastSeenAt' => $c->last_seen_at?->toIso8601String(),
                'url' => route('contacts.show', $c),
            ])->all(),
        ]);
    }

    /**
     * Quién bloqueó el contacto: no hay columna propia, sale del registro de
     * actividad (LogsActivity) del último cambio que fijó banned_at.
     */
    private function bannedBy(Customer $customer): ?string
    {
        try {
            $activity = Activity::forSubject($customer)
                ->where('event', 'updated')
                ->latest('id')
                ->limit(20)
                ->get()
                ->first(fn ($a) => ! empty($a->properties['attributes']['banned_at'] ?? null));
        } catch (\Throwable) {
            return null;
        }

        return $activity?->causer?->full_name;
    }

    /**
     * Acciones de la ficha que el listado puede pedir por ?action=.
     */
    private const AUTO_ACTIONS = ['edit', 'ticket', 'merge', 'sync', 'ban', 'unban'];

    /**
     * Update editable fields on a customer contact.
     */
    public function update(Customer $customer, UpdateContactRequest $request): JsonResponse
    {
        $this->assertVisible($customer);

        $data = $request->validated();
        $tagNames = $data['tags'] ?? null;
        unset($data['tags']);

        // Empresa (campo libre del modal Editar): se busca por nombre sin
        // distinguir mayúsculas y, si no existe, se crea. Vacío = sin empresa.
        if (array_key_exists('company', $data)) {
            $companyName = trim((string) $data['company']);
            $data['company_id'] = $companyName === ''
                ? null
                : (Company::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($companyName)])->value('id')
                    ?? Company::create(['name' => $companyName])->id);
            unset($data['company']);
        }

        // is_vip y owner_id son columnas reales de helpdesk_customers ($fillable),
        // así que viajan en $data igual que name/email. owner_id ausente = no se
        // toca; null = quitar el responsable (ya validado contra el catálogo de
        // agentes en UpdateContactRequest).
        $customer->update($data);

        // find-or-create por nombre: el selector de etiquetas del modal Editar
        // permite creación libre (select2 tag mode), así que un nombre nuevo
        // simplemente crea la etiqueta en el mismo request.
        if ($tagNames !== null) {
            $tagIds = collect($tagNames)
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->map(fn (string $name) => CustomerTag::findOrCreateByName($name)->id);

            $changes = $customer->tags()->sync($tagIds);

            // sync() no toca la fila del cliente, y ContactAggregatorService::
            // resumen() cachea por customer.updated_at: sin esto, un cambio que
            // solo afecte a etiquetas dejaba las viejas hasta 60 s tras recargar.
            if ($changes['attached'] || $changes['detached']) {
                $customer->touch();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contacto actualizado correctamente',
        ]);
    }

    /**
     * Etiquetas existentes, para el autocompletado del selector del modal
     * Editar (select2 en modo tag, creación libre + sugerencias de las ya
     * usadas por otros contactos).
     */
    public function tagsIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'tags' => CustomerTag::query()->orderBy('name')->get(['id', 'name', 'slug', 'color']),
        ]);
    }

    /**
     * Agentes que se pueden asignar como responsable de un contacto, para los
     * desplegables de la UI (modal Editar y "Asignar agente responsable" de
     * Acciones masivas). Solo se sirve a quien puede editar contactos (ruta con
     * can:contacts.update): el desplegable no tiene otro uso.
     */
    public function ownersIndex(ContactOwnerCatalog $owners): JsonResponse
    {
        return response()->json([
            'success' => true,
            'agents' => $owners->options(),
        ]);
    }

    /**
     * Rows per transaction when importing a CSV: keeps each write burst short
     * (no long single transaction over a 5 MB file) while remaining atomic per
     * chunk if a row fails midway.
     */
    private const IMPORT_CHUNK_SIZE = 250;

    /**
     * Show the CSV import form.
     */
    public function importForm(): View
    {
        return view('contacts::contacts.import');
    }

    /**
     * Process an uploaded CSV and upsert contacts.
     *
     * Matches on email DENTRO del alcance del agente (forAgent): un email que
     * pertenece a un contacto de otra bandeja no se toca (se cuenta como
     * omitido) — mismo aislamiento por inbox que index/update/bulkAction.
     * Los contactos nuevos se asocian a las bandejas asignadas del agente vía
     * el pivot helpdesk_customer_inboxes para que queden visibles (antes se
     * creaban sin asociación y un agente restringido no podía verlos).
     */
    public function importProcess(ImportContactsRequest $request): RedirectResponse
    {
        $handle = fopen($request->file('file')->getPathname(), 'r');

        // Skip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerRow = fgetcsv($handle);

        if ($headerRow === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'El CSV está vacío o no tiene cabecera.']);
        }

        // Str::ascii() quita tildes ademas de minusculas/trim: una cabecera
        // "Teléfono" (la forma natural en español) nunca casaba con el
        // 'telefono' sin tilde de abajo y la columna se importaba vacia
        // en silencio.
        $headers = array_map(fn (string $header): string => Str::ascii(strtolower(trim($header))), $headerRow);
        $nameCol = array_search('name', $headers) !== false ? array_search('name', $headers) : array_search('nombre', $headers);
        $emailCol = array_search('email', $headers) !== false ? array_search('email', $headers) : array_search('correo', $headers);
        $phoneCol = collect(['phone', 'telefono', 'movil'])
            ->map(fn (string $alias) => array_search($alias, $headers))
            ->first(fn ($index) => $index !== false, false);
        $whatsappCol = array_search('whatsapp_phone', $headers) !== false ? array_search('whatsapp_phone', $headers) : array_search('whatsapp', $headers);

        if ($nameCol === false && $emailCol === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'El CSV debe tener al menos la columna "name" o "email".']);
        }

        $agent = $request->user();
        $agentInboxIds = AgentInboxCapacity::query()
            ->where('user_id', $agent->id)
            ->pluck('inbox_id')
            ->all();

        $counters = ['created' => 0, 'updated' => 0, 'restored' => 0, 'skipped' => 0, 'invalid_email' => 0, 'invalid_whatsapp' => 0, 'rejected' => []];
        // "Actualizar los contactos que ya existan por email" (modal Importar).
        $updateExisting = $request->has('update_existing') ? $request->boolean('update_existing') : true;

        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            $chunk[] = $row;

            if (count($chunk) >= self::IMPORT_CHUNK_SIZE) {
                $this->importChunk($chunk, $nameCol, $emailCol, $phoneCol, $whatsappCol, $agent, $agentInboxIds, $counters, $updateExisting);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->importChunk($chunk, $nameCol, $emailCol, $phoneCol, $whatsappCol, $agent, $agentInboxIds, $counters, $updateExisting);
        }

        fclose($handle);

        $summary = "Importación completada: {$counters['created']} creados, "
            ."{$counters['updated']} actualizados, {$counters['restored']} restaurados, {$counters['skipped']} omitidos";

        if ($counters['invalid_email'] > 0) {
            $summary .= " ({$counters['invalid_email']} con email inválido)";
        }

        if ($counters['invalid_whatsapp'] > 0) {
            $summary .= " ({$counters['invalid_whatsapp']} con WhatsApp inválido, contacto igualmente importado)";
        }

        $redirect = redirect()->route('contacts.index')->with('success', $summary.'.');

        // Filas rechazadas descargables (mockup pieza 08): CSV con la fila
        // original y el motivo, guardado 1 h y solo para el agente que importó.
        if ($counters['rejected'] !== []) {
            $key = (string) Str::uuid();
            Cache::put('contacts:import-rejected:'.$key, [
                'user_id' => $agent->id,
                'header' => $headerRow,
                'rows' => $counters['rejected'],
            ], now()->addHour());
            $redirect->with('import_rejected_url', route('contacts.import.rejected', $key))
                ->with('import_rejected_count', count($counters['rejected']));
        }

        return $redirect;
    }

    /**
     * Descarga el CSV de filas rechazadas de la última importación del agente.
     */
    public function importRejected(Request $request, string $key): StreamedResponse
    {
        $payload = Cache::get('contacts:import-rejected:'.$key);

        abort_unless(is_array($payload) && (int) $payload['user_id'] === (int) $request->user()->id, 404);

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [...$payload['header'], 'motivo']);
            foreach ($payload['rows'] as $row) {
                fputcsv($out, array_map(fn ($v) => $this->csvSafe((string) $v), $row));
            }
            fclose($out);
        }, 'filas-rechazadas.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import a batch of CSV rows inside a single transaction.
     *
     * @param  array<int, array<int, string|null>>  $rows
     * @param  int|false  $nameCol
     * @param  int|false  $emailCol
     * @param  int|false  $phoneCol
     * @param  int|false  $whatsappCol
     * @param  array<int, int>  $agentInboxIds
     * @param  array{created: int, updated: int, skipped: int, invalid_email: int, invalid_whatsapp: int}  $counters
     */
    private function importChunk(
        array $rows,
        $nameCol,
        $emailCol,
        $phoneCol,
        $whatsappCol,
        User $agent,
        array $agentInboxIds,
        array &$counters,
        bool $updateExisting = true
    ): void {
        $phoneNormalizer = app(PhoneNormalizerService::class);

        DB::connection('helpdesk')->transaction(function () use ($rows, $nameCol, $emailCol, $phoneCol, $whatsappCol, $agent, $agentInboxIds, $phoneNormalizer, &$counters, $updateExisting): void {
            $reject = function (array $row, string $reason) use (&$counters): void {
                $counters['skipped']++;
                $counters['rejected'][] = [...$row, $reason];
            };

            foreach ($rows as $row) {
                $name = ($nameCol !== false && isset($row[$nameCol])) ? trim((string) $row[$nameCol]) : null;
                $email = ($emailCol !== false && isset($row[$emailCol])) ? trim((string) $row[$emailCol]) : null;
                $phone = ($phoneCol !== false && isset($row[$phoneCol])) ? trim((string) $row[$phoneCol]) : null;
                $whatsappRaw = ($whatsappCol !== false && isset($row[$whatsappCol])) ? trim((string) $row[$whatsappCol]) : null;

                if (! $name && ! $email) {
                    $reject($row, 'sin nombre ni email');

                    continue;
                }

                if ($email !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $counters['invalid_email']++;
                    $reject($row, 'email con formato inválido');

                    continue;
                }

                // toWhatsappE164() acepta tanto formato internacional como
                // móvil español sin prefijo ("615490503" → "+34615490503");
                // no confirma que exista de verdad en WhatsApp (Meta no lo
                // permite sin coste), solo descarta formatos imposibles.
                $whatsapp = null;
                if ($whatsappRaw !== null && $whatsappRaw !== '') {
                    $whatsapp = $phoneNormalizer->toWhatsappE164($whatsappRaw);

                    if ($whatsapp === null) {
                        $counters['invalid_whatsapp']++;
                    }
                }

                // Match SOLO dentro del alcance del agente (aislamiento por inbox).
                $existing = $email
                    ? Customer::query()->forAgent($agent)->where('email', $email)->first()
                    : null;

                if ($existing && ! $updateExisting) {
                    $reject($row, 'ya existe (no se actualizan existentes)');

                    continue;
                }

                if ($existing) {
                    $existing->update(array_filter(
                        ['name' => $name, 'phone' => $phone, 'whatsapp_phone' => $whatsapp],
                        fn ($v) => $v !== null && $v !== ''
                    ));
                    $counters['updated']++;

                    continue;
                }

                // El email existe pero pertenece a un contacto fuera del alcance
                // del agente: no se modifica ni se duplica — se omite.
                if ($email && Customer::query()->where('email', $email)->exists()) {
                    $reject($row, 'el email pertenece a un contacto de otra bandeja');

                    continue;
                }

                // Un contacto eliminado (soft-delete) sigue ocupando su email en
                // el índice único de la BD — sin esto, re-importar la misma
                // dirección tras "Eliminar" chocaba con una violación de
                // constraint en vez de restaurar el registro existente.
                //
                // El restore SOLO se hace dentro del alcance del agente (mismo
                // aislamiento por inbox que el resto del import): sin forAgent()
                // aquí, un agente podía restaurar y engancharse (syncWithoutDetaching)
                // un contacto borrado de OTRA bandeja con solo conocer su email.
                $trashed = $email
                    ? Customer::withTrashed()->onlyTrashed()->forAgent($agent)->where('email', $email)->first()
                    : null;

                if ($trashed) {
                    $trashed->restore();
                    $trashed->update(array_filter(
                        ['name' => $name, 'phone' => $phone, 'whatsapp_phone' => $whatsapp],
                        fn ($v) => $v !== null && $v !== ''
                    ));

                    if ($agentInboxIds !== []) {
                        $trashed->inboxes()->syncWithoutDetaching($agentInboxIds);
                    }

                    $counters['restored']++;

                    continue;
                }

                // El email pertenece a un contacto eliminado fuera del alcance
                // del agente: no se restaura ni se duplica — se omite.
                if ($email && Customer::withTrashed()->onlyTrashed()->where('email', $email)->exists()) {
                    $reject($row, 'el email pertenece a un contacto eliminado de otra bandeja');

                    continue;
                }

                $customer = Customer::create(array_filter(
                    ['name' => $name, 'email' => $email, 'phone' => $phone, 'whatsapp_phone' => $whatsapp],
                    fn ($v) => $v !== null && $v !== ''
                ));

                // Asocia el contacto nuevo a las bandejas asignadas del agente
                // (mismo pivot que usa el alta vía livechat) para que quede
                // visible bajo el aislamiento por inbox. Un gestor sin bandejas
                // asignadas no necesita asociación: ve todos los contactos.
                if ($agentInboxIds !== []) {
                    $customer->inboxes()->syncWithoutDetaching($agentInboxIds);
                }

                $counters['created']++;
            }
        });
    }

    /**
     * Stream a CSV export of contacts matching the current filters.
     * Maximum 5 000 records.
     *
     * `columns` (array, optional) añade grupos de columnas opcionales al CSV
     * fijo de siempre — 'health' (Salud/Valor de vida, mismos servicios que
     * usa el listado) y 'external' (IDs de ERP/PrestaShop). Sin este
     * parámetro se comporta exactamente igual que antes (solo columnas de
     * contacto) — no rompe al enlace/test que lo llaman sin argumentos.
     */
    /**
     * Filas que se descargan al momento; por encima se envía por email.
     */
    private const EXPORT_DIRECT_LIMIT = 5000;

    public function export(Request $request, ContactAggregatorService $aggregator, ContactCsvWriter $writer): StreamedResponse|RedirectResponse
    {
        $filename = 'contactos-'.now()->format('Y-m-d').'.csv';

        $groups = $request->input('columns');
        $groups = is_array($groups) ? $groups : [];
        $includeHealth = in_array('health', $groups, true);
        $includeExternal = in_array('external', $groups, true);

        $query = $this->applyFilters(Customer::query()->forAgent($request->user()), $request);
        // 'view' opcional: permite exportar una vista guardada del listado
        // (p.ej. "Exportar informe" desde el modal de riesgo, ?view=risk)
        // con el mismo criterio que index()/applyView(), sin requerir que
        // el agente haya navegado antes a esa vista en el listado.
        $view = in_array($request->input('view'), self::VIEWS, true) ? $request->input('view') : 'all';
        // Sin $request->user() aquí, 'duplicates' no filtraba nada (ver
        // applyView(): $user === null devuelve el query intacto) y
        // "Exportar informe" desde la vista de duplicados exportaba TODOS
        // los contactos del agente en vez de solo los duplicados.
        $this->applyView($query, $view, $request->user(), $aggregator);

        // ids[] = "Exportar selección" del listado: se acota a esos contactos
        // (siempre dentro del alcance del agente, forAgent() ya está aplicado).
        $ids = collect(is_array($request->input('ids')) ? $request->input('ids') : [])
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(1000)
            ->values();
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        }

        // Más de 5.000 filas: no se descarga al momento, se genera en la cola
        // "exports" y se envía al agente por email (mockup "Exportar contactos").
        $total = (clone $query)->count();
        if ($total > self::EXPORT_DIRECT_LIMIT) {
            ExportContactsJob::dispatch(
                $request->user()->id,
                (clone $query)->pluck('id')->all(),
                $includeHealth,
                $includeExternal,
            );

            return redirect()->route('contacts.index')->with('success',
                "La exportación tiene {$total} contactos: la preparamos en segundo plano y te llegará por email a {$request->user()->email}.");
        }

        $query->latest('last_seen_at');

        return response()->streamDownload(function () use ($query, $includeHealth, $includeExternal, $writer) {
            $handle = fopen('php://output', 'w');
            $writer->write($handle, $query, $includeHealth, $includeExternal);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Descarga de una exportación enviada por email (enlace firmado de 24 h,
     * solo para el agente que la pidió: la ruta lleva su id en el path).
     */
    public function exportFile(Request $request, string $path): StreamedResponse
    {
        $decoded = base64_decode($path, true);

        abort_unless(
            is_string($decoded)
            && str_starts_with($decoded, 'contacts-exports/'.$request->user()->id.'/')
            && ! str_contains($decoded, '..')
            && Storage::disk('local')->exists($decoded),
            404
        );

        return Storage::disk('local')->download($decoded, basename($decoded), ['Content-Type' => 'text/csv']);
    }

    /**
     * Delete a single contact — same permission gate as the bulk 'delete'
     * action (no dedicated contacts.delete permission exists).
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $this->assertVisible($customer);

        $customer->delete();

        return redirect()->route('contacts.index')->with('success', 'Contacto eliminado');
    }

    /**
     * Suspend a customer account.
     */
    public function ban(Customer $customer, Request $request): JsonResponse
    {
        $this->assertVisible($customer);

        $customer->ban($request->string('reason')->trim()->value() ?: null);

        return response()->json(['success' => true, 'message' => 'Contacto bloqueado']);
    }

    /**
     * Reactivate a previously suspended customer.
     */
    public function unban(Customer $customer): JsonResponse
    {
        $this->assertVisible($customer);

        $customer->unban();

        return response()->json(['success' => true, 'message' => 'Contacto desbloqueado']);
    }

    /**
     * List approved WhatsApp templates for the send-template modal.
     *
     * Duplicated on purpose from ConversationsController::hsmTemplates()
     * instead of reused: that route sits behind `can:helpdesk.view`
     * (Helpdesk's own permission domain), which a contacts-only agent may
     * not have — same reasoning as the cart proxy routes re-gating under
     * contacts.* instead of depending on Helpdesk's gate.
     */
    public function hsmTemplates(): JsonResponse
    {
        $templates = WhatsAppTemplate::query()
            ->where('status', 'approved')
            ->orderBy('display_name')
            ->get()
            ->map(fn (WhatsAppTemplate $t) => [
                'id' => $t->id,
                'name' => $t->display_name,
                'external_id' => $t->external_id,
                'body' => $t->body_template,
                'category' => $t->category,
                'header_type' => $t->header_type,
                'header_value' => $t->header_value,
                'footer_text' => $t->footer_text,
                'language' => $t->language,
                'param_count' => $t->param_count,
            ]);

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    /**
     * Send a WhatsApp HSM template to a single contact — creates or reuses
     * their open WhatsApp conversation (a fresh conversation never has the
     * 24h window open, so a template is required either way).
     */
    public function sendHsm(Customer $customer, SendHsmRequest $request, HsmConversationService $hsmConversations): JsonResponse
    {
        $this->assertVisible($customer);

        abort_if(
            ! $customer->whatsapp_phone,
            422,
            'El contacto no tiene número de WhatsApp.'
        );

        $validated = $request->validated();

        $conversation = $hsmConversations->findOrCreateWhatsAppConversation($customer);
        $hsmConversations->sendToConversation(
            $conversation,
            $validated['template_name'],
            $validated['variables'] ?? [],
            $validated['language'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Plantilla en cola de envío.',
            'conversation_id' => $conversation->id,
        ], 201);
    }

    /**
     * Catalogue of platforms searchable for the external-search modal
     * (ERP/PrestaShop) — separate call from the search itself since it
     * doesn't depend on a query string.
     */
    public function externalPlatforms(CustomerIntegrationService $integrations): JsonResponse
    {
        abort_if(! helpdesk_integration_enabled(), 404);

        return response()->json(['success' => true, 'platforms' => $integrations->linkablePlatforms()]);
    }

    /**
     * Search a customer on an external platform (ERP/PrestaShop). Each
     * result is enriched with whether it's already linked to a Customer, or
     * whether its email matches one — so the modal can offer "ver ficha" /
     * "vincular a existente" / "crear contacto" without a second round-trip.
     */
    public function externalSearch(ExternalSearchRequest $request, CustomerIntegrationService $integrations): JsonResponse
    {
        abort_if(! helpdesk_integration_enabled(), 404);

        $data = $request->validated();

        // Sin plataforma explícita, busca en todas las vinculables a la vez
        // ("Buscar en ERP/PrestaShop" es una búsqueda general, no obliga a
        // elegir una primero) — cada resultado queda etiquetado con su
        // platform de origen para que crear/vincular sepan a cuál pertenece.
        $platforms = filled($data['platform'] ?? null)
            ? [$data['platform']]
            : collect($integrations->linkablePlatforms())->pluck('platform')->all();

        $offset = (int) ($data['offset'] ?? 0);
        $pageSize = ErpContextService::SEARCH_PAGE_SIZE;

        $anyOk = false;
        $failedPlatforms = [];
        $results = [];
        $hasMore = false;

        foreach ($platforms as $platform) {
            $result = $integrations->search($platform, $data['query'], $data['type'], $offset);

            // Una página llena del ERP indica que puede haber más (antes se
            // cortaba en 20 sin forma de ver el resto).
            if ($platform === 'erp' && $result['ok'] && count($result['results']) >= $pageSize) {
                $hasMore = true;
            }

            if (! $result['ok']) {
                $failedPlatforms[] = $platform;

                continue;
            }

            $anyOk = true;

            foreach ($result['results'] as $r) {
                $linked = Customer::findByExternalId($platform, (string) $r['id']);

                $r['platform'] = $platform;
                $r['linked_customer_id'] = $linked?->id;
                // Restringido al forAgent() del solicitante: sin esto, un
                // resultado de búsqueda revelaba el id de un Customer fuera
                // del alcance del agente (aislamiento por inbox roto).
                $r['matched_customer_id'] = ! $linked && filled($r['email'] ?? null)
                    ? Customer::query()->forAgent($request->user())->where('email', $r['email'])->value('id')
                    : null;

                $results[] = $r;
            }
        }

        return response()->json([
            'success' => true,
            'ok' => $anyOk || $platforms === [],
            'failed_platforms' => $failedPlatforms,
            'results' => $results,
            'has_more' => $hasMore,
            'next_offset' => $hasMore ? $offset + $pageSize : null,
        ]);
    }

    /**
     * Create a new contact from an external search result that has no
     * matching Customer yet ("generar la ficha de contacto").
     */
    public function externalCreate(ExternalIntegrationRequest $request, CustomerIntegrationService $integrations): JsonResponse
    {
        abort_if(! helpdesk_integration_enabled(), 404);

        $data = $request->validated();

        try {
            $customer = $integrations->createFromResult($data['platform'], $data['external_id'], $data['phone'] ?? null);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'No se pudo crear el contacto.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'redirect' => route('contacts.show', $customer),
            // La ficha ERP/PrestaShop puede no traer teléfono — el JS lo usa
            // para no ofrecer el paso de enviar WhatsApp si de entrada no hay
            // forma de mandarlo, en vez de dejar rellenar la plantilla entera
            // y recién ahí toparse con el error.
            'has_phone' => filled($customer->phone) || filled($customer->whatsapp_phone),
        ], 201);
    }

    /**
     * Full customer profile (address, orders, invoices/carts) from ERP or
     * PrestaShop for an external search result — keyed by email, same as
     * ContactAggregatorService::erp()/prestashop() use for an already-linked
     * Customer, but callable before any Customer exists.
     */
    public function externalPreview(ExternalPreviewRequest $request): JsonResponse
    {
        abort_if(! helpdesk_integration_enabled(), 404);

        $data = $request->validated();
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? null;

        // Antes aceptaba cualquier email/teléfono arbitrario con solo
        // contacts.view, devolviendo la ficha ERP/PS completa (dirección,
        // pedidos, facturas, NIF) sin relación con el alcance del agente.
        // Ahora exige que el email/teléfono ya pertenezca a un Customer
        // dentro de su forAgent() — mismo aislamiento por inbox que el resto
        // del controlador (assertVisible/bulkAction/index).
        //
        // Excepción: un resultado de la búsqueda externa aún no es contacto
        // (ese es justo el caso de "Ver ficha" antes de "Crear contacto
        // nuevo"), y con solo el chequeo anterior la ficha daba siempre 403.
        // Se acepta si el par id+email coincide con la ficha real de la
        // plataforma: es el mismo dato que la búsqueda ya le mostró al agente.
        $externalId = filled($data['external_id'] ?? null) ? (string) $data['external_id'] : null;
        if (! $this->isExternalResult($data['platform'], $externalId, $email, $phone)) {
            $this->assertKnownToAgent($email, $phone);
        }

        // ERP soporta fallback por teléfono cuando no hay email (frecuente en
        // resultados encontrados por búsqueda telefónica) — PrestaShop no
        // tiene ese fallback implementado, sigue exigiendo email.
        $erpId = $externalId !== null && ctype_digit($externalId) ? (int) $externalId : null;

        // Con el IDCLIENTE ya conocido se usa la ficha del módulo Erp
        // (GET /erp/customer/{id}): clave primaria + teléfonos + direcciones,
        // sin pedidos — PEDIDOCLI_CENTRAL no tiene índice por cliente y los
        // pedidos tardaban >10 s en una ficha que solo sirve para decidir si
        // importar. 'orders' => null le dice a la vista que no pinte esa sección.
        if ($data['platform'] === 'erp' && $erpId !== null) {
            $summary = $this->erpSummaryPreview($erpId);

            if ($summary !== null) {
                return response()->json(['success' => true, 'customer' => $summary, 'orders' => null]);
            }
        }

        $context = match ($data['platform']) {
            'erp' => app(ErpContextService::class)->getCustomerContext($email, $phone, erpId: $erpId),
            'prestashop' => app(PrestashopContextService::class)->getCustomerContext($email),
        };

        return response()->json(['success' => true, ...$context]);
    }

    /**
     * Link an external search result to an already-existing contact
     * ("unirla") — used from the ficha 360, not the index search.
     */
    public function externalLink(Customer $customer, ExternalIntegrationRequest $request, CustomerIntegrationService $integrations): JsonResponse
    {
        abort_if(! helpdesk_integration_enabled(), 404);

        $this->assertVisible($customer);

        $data = $request->validated();

        try {
            $integrations->link($customer, $data['platform'], $data['external_id']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'No se pudo vincular.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Integración vinculada correctamente.',
            'has_phone' => filled($customer->phone) || filled($customer->whatsapp_phone),
        ]);
    }

    /**
     * Platforms already linked to a contact, for the ficha 360 "Integraciones" section.
     */
    public function externalIntegrations(Customer $customer, CustomerIntegrationService $integrations): JsonResponse
    {
        abort_if(! helpdesk_integration_enabled(), 404);

        $this->assertVisible($customer);

        return response()->json(['success' => true, ...$integrations->buildPayload($customer)]);
    }

    /**
     * Apply a bulk action (ban, unban, delete, tag, assign) to a set of customer IDs.
     *
     * - tag: añade la etiqueta `tag` (find-or-create) SIN quitar las que ya tengan.
     * - assign: fija `owner_id` como responsable (null = quitar el responsable).
     */
    public function bulkAction(BulkContactActionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Restringe la acción masiva a los contactos que el agente puede ver
        // (aislamiento por inbox); ignora silenciosamente los IDs fuera de alcance.
        $ids = Customer::query()
            ->forAgent($request->user())
            ->whereIn('id', $data['ids'])
            ->pluck('id')
            ->all();

        match ($data['action']) {
            'ban' => Customer::whereIn('id', $ids)->update(['banned_at' => now()]),
            // Limpia también ban_reason (igual que Customer::unban()); sin esto el
            // motivo del baneo previo quedaba obsoleto tras reactivar en lote.
            'unban' => Customer::whereIn('id', $ids)->update(['banned_at' => null, 'ban_reason' => null]),
            'delete' => Customer::whereIn('id', $ids)->delete(),
            'tag' => $this->attachTagToCustomers($ids, (string) $data['tag']),
            // Builder::update() también fija updated_at, con lo que la caché de
            // resumen() (clave por customer.updated_at) se invalida sola.
            'assign' => Customer::whereIn('id', $ids)->update(['owner_id' => $data['owner_id'] ?? null]),
        };

        $total = count($ids);

        $message = match ($data['action']) {
            'tag' => "Etiqueta «{$data['tag']}» añadida a {$total} contactos",
            'assign' => ($data['owner_id'] ?? null) === null
                ? "Responsable quitado a {$total} contactos"
                : "Responsable asignado a {$total} contactos",
            default => "Acción aplicada a {$total} contactos",
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => $total,
        ]);
    }

    /**
     * Añade una etiqueta (find-or-create por nombre) a varios contactos sin
     * quitarles las que ya tienen: solo se insertan las filas de pivote que
     * faltan, en lotes, en vez de un sync() por contacto. La tabla pivote no
     * toca customers.updated_at, y resumen() cachea por esa columna, así que se
     * hace un touch() de los contactos que cambian.
     *
     * @param  array<int, int>  $ids
     */
    private function attachTagToCustomers(array $ids, string $name): void
    {
        if ($ids === []) {
            return;
        }

        $tag = CustomerTag::findOrCreateByName($name);
        $pivot = DB::connection('helpdesk')->table('helpdesk_customer_tag_pivot');

        $already = (clone $pivot)
            ->where('tag_id', $tag->id)
            ->whereIn('customer_id', $ids)
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $now = now();

        foreach (array_chunk(array_values(array_diff($ids, $already)), 500) as $chunk) {
            $pivot->insert(array_map(fn (int $id): array => [
                'customer_id' => $id,
                'tag_id' => $tag->id,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));

            Customer::whereIn('id', $chunk)->touch();
        }
    }

    /**
     * Rows per chunk job when sending a WhatsApp template to many contacts —
     * same reasoning as SendBroadcastJob::CHUNK_SIZE, kept smaller since this
     * is an ad-hoc send (no persisted campaign entity to resume from).
     */
    private const HSM_BULK_CHUNK_SIZE = 50;

    /**
     * Send a WhatsApp HSM template to a batch of contacts, chunked across
     * SendBulkHsmTemplateJob — each contact gets its own conversation/item,
     * a per-contact failure doesn't abort the rest of the batch.
     */
    public function bulkSendHsm(BulkSendHsmRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Restringe el envío a los contactos que el agente puede ver
        // (aislamiento por inbox), mismo criterio que bulkAction().
        $ids = Customer::query()
            ->forAgent($request->user())
            ->whereNull('banned_at')
            ->whereIn('id', $data['customer_ids'])
            ->pluck('id')
            ->all();

        $batchId = (string) Str::uuid();

        foreach (array_chunk($ids, self::HSM_BULK_CHUNK_SIZE) as $chunk) {
            SendBulkHsmTemplateJob::dispatch(
                $chunk,
                $data['template_name'],
                $data['variables'] ?? [],
                $data['language'] ?? null,
                $batchId,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Envío en cola para '.count($ids).' contactos',
            'count' => count($ids),
        ]);
    }

    /**
     * At-risk dashboard with key contact health stats.
     */
    public function reports(Request $request): View
    {
        return view('contacts::contacts.reports', $this->reportsPayload($request));
    }

    /**
     * JSON summary for the "Informes y clientes en riesgo" modal (mockup
     * pieza #14) — mismo payload cacheado que reports(), recortado a lo que
     * el modal necesita (top-3 en riesgo + 3 stats, "Salud media" añadida).
     */
    public function reportsSummary(Request $request, CustomerInsightsService $insights): JsonResponse
    {
        $payload = $this->reportsPayload($request);

        // "Salud media" no es una columna: healthScoresFor() hace 4 consultas
        // agrupadas (no una por cliente), pero un whereIn() sobre el scope
        // completo del agente (puede ser miles de IDs) sería un IN enorme
        // solo para un promedio. Se aproxima sobre una muestra acotada de los
        // 200 contactos vistos más recientemente — misma cache de 2 min que
        // el resto del payload, así que el coste no se repite en cada apertura.
        $sampleIds = Cache::remember(
            "helpdeskcontacts:reports-health-sample:{$request->user()->id}",
            120,
            fn () => Customer::query()->forAgent($request->user())
                ->whereNull('banned_at')
                ->orderByDesc('last_seen_at')
                ->limit(200)
                ->pluck('id')
                ->all(),
        );
        $scores = $insights->healthScoresFor($sampleIds);
        $avgHealth = $scores === [] ? null : (int) round(array_sum($scores) / count($scores));

        return response()->json([
            'success' => true,
            'stats' => [
                'total' => $payload['stats']['total'],
                // 'inactive' ya es el COUNT() real (sin el límite de 50 de
                // la colección $atRisk, usada solo para pintar filas).
                'atRisk' => $payload['stats']['inactive'],
                'avgHealth' => $avgHealth,
            ],
            // "Enviar plantilla a los N" (mockup: "Enviar campaña"): los en riesgo
            // que pueden recibir WhatsApp — HelpdeskCampaigns está apagado, así
            // que la acción real es el envío masivo de plantilla (bulk-send-hsm).
            'campaignIds' => $payload['atRisk']
                ->filter(fn (Customer $c) => $c->whatsapp_phone && ! $c->banned_at)
                ->pluck('id')->values(),
            'atRisk' => $payload['atRisk']->take(3)->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name ?: 'Sin nombre',
                'score' => $insights->healthScore($c),
                'reason' => $c->last_seen_at
                    ? 'sin actividad desde '.$c->last_seen_at->diffForHumans(null, true)
                    : 'sin actividad registrada',
                'url' => route('contacts.show', $c),
            ])->values(),
        ]);
    }

    /**
     * @return array{atRisk: Collection, topActive: Collection, stats: array}
     */
    private function reportsPayload(Request $request): array
    {
        // Cacheado 2 min por agente: las 6 queries de este dashboard reevalúan
        // forAgent() (WHERE EXISTS contra conversations/inboxes) cada vez sin
        // necesitar frescura al segundo — mismo criterio que index(). Compartido
        // entre reports() (página completa) y reportsSummary() (modal resumen).
        return Cache::remember(
            "helpdeskcontacts:reports:{$request->user()->id}",
            120,
            function () use ($request): array {
                $scoped = fn (): Builder => Customer::query()->forAgent($request->user());

                $atRisk = $scoped()
                    ->where(fn (Builder $q) => $q
                        ->where('last_seen_at', '<', now()->subDays(30))
                        ->orWhereNull('last_seen_at')
                    )
                    ->orderByRaw('last_seen_at IS NULL DESC, last_seen_at ASC')
                    ->limit(50)
                    ->get();

                $topActive = $scoped()
                    ->where('total_conversations', '>', 0)
                    ->orderByDesc('total_conversations')
                    ->limit(10)
                    ->get();

                $stats = [
                    'total' => $scoped()->whereNull('banned_at')->count(),
                    'banned' => $scoped()->whereNotNull('banned_at')->count(),
                    'inactive' => $scoped()
                        ->where(fn (Builder $q) => $q
                            ->where('last_seen_at', '<', now()->subDays(30))
                            ->orWhereNull('last_seen_at')
                        )
                        ->count(),
                    'verified' => $scoped()->whereNotNull('email_verified_at')->count(),
                ];

                return ['atRisk' => $atRisk, 'topActive' => $topActive, 'stats' => $stats];
            },
        );
    }

    /**
     * Orígenes del filtro "Origen" del listado, en ORDEN DE PRIORIDAD. No hay
     * columna de origen en helpdesk_customers: se DERIVA de datos reales y cada
     * contacto cae en exactamente UN origen (el primero de esta lista cuyo
     * criterio cumple), así que los conteos por origen suman el total:
     *
     *   erp        → tiene un external id de la plataforma 'erp'
     *   prestashop → external id 'prestashop' (y ninguno de ERP)
     *   whatsapp   → whatsapp_phone informado
     *   facebook   → facebook_psid informado
     *   instagram  → instagram_id informado
     *   web        → alguna conversación por el canal 'web' (livechat)
     *   email      → solo tiene email (ninguno de los anteriores)
     *   otro       → ninguno de los anteriores (sin email ni canal alguno)
     *
     * @var array<string, string> valor → etiqueta en español para el desplegable
     */
    public const ORIGINS = [
        'erp' => 'ERP',
        'prestashop' => 'PrestaShop',
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'web' => 'Chat web',
        'email' => 'Email',
        'otro' => 'Otro',
    ];

    /**
     * Criterio POSITIVO de cada origen sobre el builder recibido (sin cargar
     * nada en PHP: whereHas/whereNotNull). 'otro' no tiene criterio propio,
     * es el complemento de todos los demás.
     */
    private function originCriterion(Builder $query, string $origin): Builder
    {
        return match ($origin) {
            'erp' => $query->whereHas('externalIds', fn (Builder $q) => $q->where('platform', 'erp')),
            'prestashop' => $query->whereHas('externalIds', fn (Builder $q) => $q->where('platform', 'prestashop')),
            'whatsapp' => $query->whereNotNull('whatsapp_phone'),
            'facebook' => $query->whereNotNull('facebook_psid'),
            'instagram' => $query->whereNotNull('instagram_id'),
            'web' => $query->whereHas('conversations', fn (Builder $q) => $q->where('channel', 'web')),
            'email' => $query->whereNotNull('email'),
            default => $query,
        };
    }

    /**
     * Aplica ?origin=<valor>: el criterio del origen pedido Y la negación de
     * los de mayor prioridad, para que el resultado sea la partición exclusiva
     * descrita en self::ORIGINS.
     */
    private function applyOrigin(Builder $query, string $origin): Builder
    {
        foreach (array_keys(self::ORIGINS) as $candidate) {
            if ($candidate === $origin) {
                break;
            }

            $query->whereNot(fn (Builder $q) => $this->originCriterion($q, $candidate));
        }

        return $origin === 'otro' ? $query : $this->originCriterion($query, $origin);
    }

    /**
     * Apply all supported query-string filters to a Customer query builder.
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        $term = $request->string('q')->trim()->toString();

        if ($term !== '') {
            $query->search($term);
        }

        match ($request->input('channel')) {
            'email' => $query->whereNotNull('email'),
            'whatsapp' => $query->whereNotNull('whatsapp_phone'),
            'facebook' => $query->whereNotNull('facebook_psid'),
            'instagram' => $query->whereNotNull('instagram_id'),
            default => null,
        };

        match ($request->input('last_seen')) {
            'today' => $query->whereDate('last_seen_at', today()),
            'week' => $query->where('last_seen_at', '>=', now()->subWeek()),
            'month' => $query->where('last_seen_at', '>=', now()->subMonth()),
            'inactive' => $query->where(
                fn (Builder $q) => $q->where('last_seen_at', '<', now()->subDays(30))
                    ->orWhereNull('last_seen_at')
            ),
            default => null,
        };

        if ($request->input('verified') === 'yes') {
            $query->whereNotNull('email_verified_at');
        } elseif ($request->input('verified') === 'no') {
            $query->whereNull('email_verified_at');
        }

        if ($request->input('banned') === 'yes') {
            $query->whereNotNull('banned_at');
        } elseif ($request->input('banned') === 'no') {
            $query->whereNull('banned_at');
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $request->string('tag')->toString()));
        }

        // ?owner=<id de agente> filtra por responsable; ?owner=none, los que no
        // tienen ninguno. Un valor que no sea ni entero ni "none" se ignora.
        $owner = $request->input('owner');
        if ($owner === 'none') {
            $query->whereNull('owner_id');
        } elseif (is_string($owner) && ctype_digit($owner)) {
            $query->where('owner_id', (int) $owner);
        }

        $origin = $request->input('origin');
        if (is_string($origin) && array_key_exists($origin, self::ORIGINS)) {
            $this->applyOrigin($query, $origin);
        }

        return $query;
    }

    /**
     * Abort with 403 unless the given customer is within the agent's
     * inbox-isolation scope (same rule as the list queries).
     */
    private function assertVisible(Customer $customer): void
    {
        abort_unless(
            Customer::query()->whereKey($customer->getKey())->forAgent(request()->user())->exists(),
            403,
            'Sin autorización sobre este contacto.'
        );
    }

    /**
     * Ficha de Gestión para la vista previa, desde el endpoint de cliente del
     * módulo Erp (llamado en proceso, sin HTTP a sí mismo). Teléfono: el
     * primer móvil activo, si no el primero activo; dirección: la de tipo 1
     * si existe. Null si el módulo no está o la consulta falla.
     *
     * @return array<string, mixed>|null
     */
    private function erpSummaryPreview(int $erpId): ?array
    {
        if (! class_exists(ErpCustomerController::class)) {
            return null;
        }

        try {
            $response = app(ErpCustomerController::class)->summary($erpId);
        } catch (\Throwable) {
            return null;
        }

        $payload = $response->getData(true);
        $d = $payload['data'] ?? null;

        if ($response->getStatusCode() !== 200 || ! is_array($d)) {
            return null;
        }

        $phones = collect($d['phones'] ?? [])->filter(fn ($p) => ($p['available'] ?? true) && filled($p['number'] ?? null));
        $phone = $phones->first(fn ($p) => preg_match('/^[67]/', (string) $p['number']) === 1) ?? $phones->first();

        $addresses = collect($d['addresses'] ?? [])->filter(fn ($a) => $a['available'] ?? true);
        $address = $addresses->firstWhere('type', '1') ?? $addresses->first();

        return [
            'found' => true,
            'id' => $d['id'] ?? $erpId,
            'name' => trim(($d['label'] ?? '').' '.($d['surnames'] ?? '')),
            'email' => $d['email'] ?? null,
            'nif' => $d['cif'] ?? null,
            'phone' => $phone['number'] ?? null,
            'city' => $address['city'] ?? null,
            'province' => $address['province'] ?? null,
            'address' => $address
                ? (implode(', ', array_filter([trim(($address['street'] ?? '').' '.($address['number'] ?? '')), $address['postal_code'] ?? null])) ?: null)
                : null,
        ];
    }

    /**
     * True when $externalId resolves on the platform to a record carrying
     * the given email (or phone, when no email is sent) — i.e. the request
     * is for a result the external search itself returned.
     */
    private function isExternalResult(string $platform, ?string $externalId, string $email, ?string $phone): bool
    {
        if ($externalId === null) {
            return false;
        }

        $record = app(CustomerIntegrationService::class)->resolveExternal($platform, $externalId);

        if ($record === null) {
            return false;
        }

        if ($email !== '') {
            return strcasecmp((string) ($record['email'] ?? ''), $email) === 0;
        }

        return filled($phone) && app(PhoneNormalizerService::class)->similar($record['phone'] ?? null, $phone);
    }

    /**
     * Abort with 403 unless the given email/phone belongs to a Customer
     * within the requesting agent's forAgent() scope — used by
     * externalPreview() to stop arbitrary email/phone lookups from
     * returning a full external (ERP/PrestaShop) profile.
     */
    private function assertKnownToAgent(string $email, ?string $phone): void
    {
        $exists = Customer::query()
            ->forAgent(request()->user())
            ->where(function (Builder $q) use ($email, $phone) {
                if ($email !== '') {
                    $q->orWhere('email', $email);
                }

                if (filled($phone)) {
                    $q->orWhere('phone', $phone)->orWhere('whatsapp_phone', $phone);
                }
            })
            ->exists();

        abort_unless($exists, 403, 'Sin autorización sobre este contacto externo.');
    }

    /**
     * Neutralize CSV formula injection: prefix values starting with a
     * formula trigger (= + - @ tab CR) with a single quote before export.
     */
    private function csvSafe(mixed $value): string
    {
        return ContactCsvWriter::csvSafe($value);
    }
}
