<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Helpdesk\Jobs\SendBulkHsmTemplateJob;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;
use Modules\Helpdesk\Models\Customer;
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
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactsController extends Controller
{
    /**
     * Split list/detail page with a paginated, searchable customer list.
     * Authorization is handled by the route middleware (can:contacts.view).
     */
    public function index(Request $request): View
    {
        abort_if(! helpdesk_contacts_enabled(), 404);

        $perPage = in_array((int) $request->input('per_page'), [15, 25, 50, 100]) ? (int) $request->input('per_page') : 25;

        // Orden fijo (sin sorting por columna en la UI) — mismo patrón que
        // UsersController::index(), que usa latest() sin parámetros de sort.
        $customers = $this->applyFilters(Customer::query()->forAgent($request->user()), $request)
            ->orderByDesc('last_seen_at')
            ->paginate($perPage)
            ->appends($request->query());

        $selected = $request->filled('selected')
            ? Customer::query()->forAgent($request->user())->whereKey($request->integer('selected'))->first()
            : null;

        // Totales globales del alcance del agente (no del resultado filtrado/
        // paginado) — mismo criterio que UsersController::index(), y mismas
        // queries que ya usa reports() para "verificados"/"suspendidos".
        // Cacheado 2 min por agente: forAgent() añade un WHERE EXISTS contra
        // conversations/inboxes que se repetía 4 veces en cada carga de la
        // página más visitada del módulo, sin necesitar frescura al segundo.
        $stats = Cache::remember(
            "helpdeskcontacts:index-stats:{$request->user()->id}",
            120,
            function () use ($request): array {
                $scoped = fn () => Customer::query()->forAgent($request->user());

                return [
                    'total' => $scoped()->count(),
                    'verified' => $scoped()->whereNotNull('email_verified_at')->count(),
                    'banned' => $scoped()->whereNotNull('banned_at')->count(),
                    'new' => $scoped()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ];
            },
        );

        return view('contacts::contacts.index', [
            'customers' => $customers,
            'selected' => $selected,
            'q' => $request->string('q')->trim()->toString(),
            'filters' => $request->only(['q', 'channel', 'last_seen', 'verified', 'banned']),
            'perPage' => $perPage,
            'stats' => $stats,
        ]);
    }

    /**
     * 360 tab-shell page for a single customer.
     * The customer is resolved via implicit binding on the 'helpdesk' connection.
     */
    public function show(Customer $customer): View
    {
        abort_if(! helpdesk_contacts_enabled(), 404);

        $this->assertVisible($customer);

        return view('contacts::contacts.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update editable fields on a customer contact.
     */
    public function update(Customer $customer, UpdateContactRequest $request): JsonResponse
    {
        $this->assertVisible($customer);

        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contacto actualizado correctamente',
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
        abort_if(! helpdesk_contacts_enabled(), 404);

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
        abort_if(! helpdesk_contacts_enabled(), 404);

        $handle = fopen($request->file('file')->getPathname(), 'r');

        // Skip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $nameCol = array_search('name', $headers) !== false ? array_search('name', $headers) : array_search('nombre', $headers);
        $emailCol = array_search('email', $headers) !== false ? array_search('email', $headers) : array_search('correo', $headers);
        $phoneCol = array_search('phone', $headers) !== false ? array_search('phone', $headers) : array_search('telefono', $headers);
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

        $counters = ['created' => 0, 'updated' => 0, 'restored' => 0, 'skipped' => 0, 'invalid_email' => 0, 'invalid_whatsapp' => 0];

        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            $chunk[] = $row;

            if (count($chunk) >= self::IMPORT_CHUNK_SIZE) {
                $this->importChunk($chunk, $nameCol, $emailCol, $phoneCol, $whatsappCol, $agent, $agentInboxIds, $counters);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->importChunk($chunk, $nameCol, $emailCol, $phoneCol, $whatsappCol, $agent, $agentInboxIds, $counters);
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

        return redirect()->route('contacts.index')->with('success', $summary.'.');
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
        array &$counters
    ): void {
        $phoneNormalizer = app(PhoneNormalizerService::class);

        DB::connection('helpdesk')->transaction(function () use ($rows, $nameCol, $emailCol, $phoneCol, $whatsappCol, $agent, $agentInboxIds, $phoneNormalizer, &$counters): void {
            foreach ($rows as $row) {
                $name = ($nameCol !== false && isset($row[$nameCol])) ? trim((string) $row[$nameCol]) : null;
                $email = ($emailCol !== false && isset($row[$emailCol])) ? trim((string) $row[$emailCol]) : null;
                $phone = ($phoneCol !== false && isset($row[$phoneCol])) ? trim((string) $row[$phoneCol]) : null;
                $whatsappRaw = ($whatsappCol !== false && isset($row[$whatsappCol])) ? trim((string) $row[$whatsappCol]) : null;

                if (! $name && ! $email) {
                    $counters['skipped']++;

                    continue;
                }

                if ($email !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $counters['skipped']++;
                    $counters['invalid_email']++;

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
                    $counters['skipped']++;

                    continue;
                }

                // Un contacto eliminado (soft-delete) sigue ocupando su email en
                // el índice único de la BD — sin esto, re-importar la misma
                // dirección tras "Eliminar" chocaba con una violación de
                // constraint en vez de restaurar el registro existente.
                $trashed = $email ? Customer::withTrashed()->onlyTrashed()->where('email', $email)->first() : null;

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
     */
    public function export(Request $request): StreamedResponse
    {
        abort_if(! helpdesk_contacts_enabled(), 404);

        $filename = 'contactos-'.now()->format('Y-m-d').'.csv';

        $query = $this->applyFilters(Customer::query()->forAgent($request->user()), $request)
            ->latest('last_seen_at')
            ->limit(5000);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Nombre', 'Email', 'Teléfono', 'WhatsApp',
                'País', 'Última visita', 'Conversaciones', 'Verificado', 'Suspendido', 'Canales',
            ]);

            $query->chunk(500, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->id,
                        $this->csvSafe($customer->name),
                        $this->csvSafe($customer->email ?? ''),
                        $this->csvSafe($customer->phone ?? ''),
                        $this->csvSafe($customer->whatsapp_phone ?? ''),
                        $this->csvSafe($customer->country ?? ''),
                        $customer->last_seen_at?->toIso8601String() ?? '',
                        $customer->total_conversations ?? 0,
                        $customer->email_verified_at ? 'Sí' : 'No',
                        $customer->banned_at ? 'Sí' : 'No',
                        $this->csvSafe($this->channelList($customer)),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
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
    public function ban(Customer $customer): JsonResponse
    {
        $this->assertVisible($customer);

        $customer->ban();

        return response()->json(['success' => true, 'message' => 'Contacto suspendido']);
    }

    /**
     * Reactivate a previously suspended customer.
     */
    public function unban(Customer $customer): JsonResponse
    {
        $this->assertVisible($customer);

        $customer->unban();

        return response()->json(['success' => true, 'message' => 'Contacto reactivado']);
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
        abort_if(! helpdesk_contacts_enabled(), 404);

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
        abort_if(! helpdesk_contacts_enabled(), 404);

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
        abort_if(! helpdesk_contacts_enabled() || ! helpdesk_integration_enabled(), 404);

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
        abort_if(! helpdesk_contacts_enabled() || ! helpdesk_integration_enabled(), 404);

        $data = $request->validated();

        // Sin plataforma explícita, busca en todas las vinculables a la vez
        // ("Buscar en ERP/PrestaShop" es una búsqueda general, no obliga a
        // elegir una primero) — cada resultado queda etiquetado con su
        // platform de origen para que crear/vincular sepan a cuál pertenece.
        $platforms = filled($data['platform'] ?? null)
            ? [$data['platform']]
            : collect($integrations->linkablePlatforms())->pluck('platform')->all();

        $anyOk = false;
        $failedPlatforms = [];
        $results = [];

        foreach ($platforms as $platform) {
            $result = $integrations->search($platform, $data['query'], $data['type']);

            if (! $result['ok']) {
                $failedPlatforms[] = $platform;

                continue;
            }

            $anyOk = true;

            foreach ($result['results'] as $r) {
                $linked = Customer::findByExternalId($platform, (string) $r['id']);

                $r['platform'] = $platform;
                $r['linked_customer_id'] = $linked?->id;
                $r['matched_customer_id'] = ! $linked && filled($r['email'] ?? null)
                    ? Customer::query()->where('email', $r['email'])->value('id')
                    : null;

                $results[] = $r;
            }
        }

        return response()->json([
            'success' => true,
            'ok' => $anyOk || $platforms === [],
            'failed_platforms' => $failedPlatforms,
            'results' => $results,
        ]);
    }

    /**
     * Create a new contact from an external search result that has no
     * matching Customer yet ("generar la ficha de contacto").
     */
    public function externalCreate(ExternalIntegrationRequest $request, CustomerIntegrationService $integrations): JsonResponse
    {
        abort_if(! helpdesk_contacts_enabled() || ! helpdesk_integration_enabled(), 404);

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
        abort_if(! helpdesk_contacts_enabled() || ! helpdesk_integration_enabled(), 404);

        $data = $request->validated();
        $email = $data['email'] ?? '';

        // ERP soporta fallback por teléfono cuando no hay email (frecuente en
        // resultados encontrados por búsqueda telefónica) — PrestaShop no
        // tiene ese fallback implementado, sigue exigiendo email.
        $context = match ($data['platform']) {
            'erp' => app(ErpContextService::class)->getCustomerContext($email, $data['phone'] ?? null),
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
        abort_if(! helpdesk_contacts_enabled() || ! helpdesk_integration_enabled(), 404);

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
        abort_if(! helpdesk_contacts_enabled() || ! helpdesk_integration_enabled(), 404);

        $this->assertVisible($customer);

        return response()->json(['success' => true, ...$integrations->buildPayload($customer)]);
    }

    /**
     * Apply a bulk action (ban, unban, delete) to a set of customer IDs.
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
        };

        return response()->json([
            'success' => true,
            'message' => 'Acción aplicada a '.count($ids).' contactos',
            'count' => count($ids),
        ]);
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
            ->whereIn('id', $data['customer_ids'])
            ->pluck('id')
            ->all();

        foreach (array_chunk($ids, self::HSM_BULK_CHUNK_SIZE) as $chunk) {
            SendBulkHsmTemplateJob::dispatch(
                $chunk,
                $data['template_name'],
                $data['variables'] ?? [],
                $data['language'] ?? null,
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
        abort_if(! helpdesk_contacts_enabled(), 404);

        // Cacheado 2 min por agente: las 6 queries de este dashboard reevalúan
        // forAgent() (WHERE EXISTS contra conversations/inboxes) cada vez sin
        // necesitar frescura al segundo — mismo criterio que index().
        $payload = Cache::remember(
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

        return view('contacts::contacts.reports', $payload);
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
     * Neutralize CSV formula injection: prefix values starting with a
     * formula trigger (= + - @ tab CR) with a single quote before export.
     */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * Build a comma-separated list of active channel names for a customer.
     */
    private function channelList(Customer $customer): string
    {
        $channels = [];

        if ($customer->email) {
            $channels[] = 'email';
        }
        if ($customer->whatsapp_phone) {
            $channels[] = 'whatsapp';
        }
        if ($customer->facebook_psid) {
            $channels[] = 'facebook';
        }
        if ($customer->instagram_id) {
            $channels[] = 'instagram';
        }

        return implode(', ', $channels);
    }
}
