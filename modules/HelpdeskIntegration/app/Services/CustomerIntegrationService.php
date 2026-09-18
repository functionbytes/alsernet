<?php

namespace Modules\HelpdeskIntegration\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\Helpdesk\Services\PhoneNormalizerService;
use Modules\HelpdeskIntegration\Contracts\IntegrationDriverContract;
use Modules\HelpdeskIntegration\Jobs\ResyncCustomerIntegrationsJob;
use Modules\HelpdeskIntegration\Models\IntegrationAuditLog;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;

/**
 * Orquesta el catalogo de proveedores (IntegrationProvider) con el vinculo
 * de datos ya existente en el core (Customer::externalIds / linkExternalId).
 */
class CustomerIntegrationService
{
    /**
     * Memoiza IntegrationProvider por platform durante la vida de esta
     * instancia — evita 1 query por plataforma en sync()/auditHistory()
     * cuando el catalogo ya se resolvio antes en el mismo request.
     *
     * @var array<string, IntegrationProvider|null>
     */
    private array $providerByPlatform = [];

    public function __construct(
        private readonly IntegrationDriverRegistry $registry,
    ) {}

    /**
     * @return array{integrations: array, linkable_platforms: array, last_activity: ?string}
     */
    public function buildPayload(Customer $customer): array
    {
        if (! helpdesk_integration_enabled()) {
            return ['integrations' => [], 'linkable_platforms' => [], 'last_activity' => null];
        }

        $customer->loadMissing('externalIds');

        $providers = IntegrationProvider::allCached();
        $linked = $customer->externalIds->keyBy('platform');

        $integrations = $providers
            ->map(fn (IntegrationProvider $provider) => $this->presentIntegration($provider, $linked->get($provider->platform)))
            ->values()
            ->all();

        // Vinculos legacy cuyo proveedor ya no esta en el catalogo: se mantienen visibles.
        $cataloguedPlatforms = $providers->pluck('platform')->all();
        foreach ($linked as $platform => $link) {
            if (in_array($platform, $cataloguedPlatforms, true)) {
                continue;
            }

            $integrations[] = [
                'platform' => $platform,
                'label' => ucfirst($platform),
                'description' => null,
                'icon' => 'fas fa-plug',
                'color' => null,
                'connected' => true,
                'external_id' => (string) $link->external_id,
                'is_critical' => false,
                'sync_status' => $link->sync_status,
                'last_synced_at' => $link->last_synced_at?->toIso8601String(),
            ];
        }

        return [
            'integrations' => $integrations,
            'linkable_platforms' => $this->linkablePlatforms($providers),
            'last_activity' => $this->lastActivity($customer),
        ];
    }

    /**
     * Catalogo de plataformas vinculables (label/icono/color/tipos de busqueda).
     * A diferencia de buildPayload(), no depende de $customer — es informacion
     * de catalogo, no del vinculo de un cliente concreto — por eso es seguro
     * exponerlo antes de verificar identidad (lo usa el buscador de
     * PrestaShop/ERP del modal de identidad, que solo consulta y no vincula).
     */
    public function linkablePlatforms(?Collection $providers = null): array
    {
        if (! helpdesk_integration_enabled()) {
            return [];
        }

        $providers ??= IntegrationProvider::allCached();

        return $providers
            ->filter(fn (IntegrationProvider $provider) => $provider->is_active && $provider->is_linkable)
            ->map(fn (IntegrationProvider $provider) => $this->presentLinkable($provider))
            ->values()
            ->all();
    }

    /**
     * Reverifica (no descubre) todas las plataformas ya conectadas del cliente.
     *
     * Tolerante a plataformas lentas: el trabajo síncrono tiene un presupuesto
     * total (config helpdeskintegration.sync.budget_seconds, 8s por defecto).
     * Los vínculos que no dan tiempo dentro del presupuesto se marcan
     * sync_status='pending' (visible en el payload que consume el modal) y se
     * reverifican en background vía ResyncCustomerIntegrationsJob, que los
     * deja en su estado real (ok/not_found/error). El timeout por plataforma
     * lo gobierna el propio driver (timeout HTTP de cada integración).
     *
     * @return array{linked: bool, payload: array}
     */
    public function sync(Customer $customer): array
    {
        $customer->loadMissing('externalIds');

        $anySynced = $customer->externalIds->isNotEmpty();
        $budgetSeconds = (float) config('helpdeskintegration.sync.budget_seconds', 8);
        $startedAt = microtime(true);
        $pendingIds = [];

        // Resincroniza cada vinculo ya cargado sin reconstruir el payload
        // completo (proveedores + externalIds) en cada iteracion — eso se
        // hace una unica vez al final, evitando ~1 query set por plataforma.
        foreach ($customer->externalIds as $link) {
            if ((microtime(true) - $startedAt) >= $budgetSeconds) {
                $pendingIds[] = $link->id;

                continue;
            }

            $this->resyncLink($customer, $link);
        }

        if ($pendingIds !== []) {
            CustomerExternalId::query()
                ->whereIn('id', $pendingIds)
                ->update(['sync_status' => 'pending']);

            ResyncCustomerIntegrationsJob::dispatch($customer->id, $pendingIds);
        }

        return [
            'linked' => $anySynced,
            'payload' => $this->buildPayload($customer->fresh(['externalIds'])),
        ];
    }

    /**
     * Reverifica una unica plataforma ya conectada. Devuelve null si el
     * cliente no tiene vinculo con esa plataforma.
     *
     * @return array{integrations: array, linkable_platforms: array, last_activity: ?string}|null
     */
    public function syncPlatform(Customer $customer, string $platform): ?array
    {
        $link = $customer->externalIds()->where('platform', $platform)->first();

        if (! $link) {
            return null;
        }

        $this->resyncLink($customer, $link);

        return $this->buildPayload($customer->fresh(['externalIds']));
    }

    public function resyncLink(Customer $customer, CustomerExternalId $link): void
    {
        $driver = $this->driverFor($link->platform);
        $result = $driver?->resync($link->external_id);

        // 'not_found': la plataforma respondió pero el ID ya no resuelve a un
        // cliente (vínculo roto) — distinto de 'error' (plataforma caída o
        // proveedor custom sin driver, no verificable).
        $status = match (true) {
            $result === null, ! $result->ok => 'error',
            ! $result->found($link->external_id) => 'not_found',
            default => 'ok',
        };

        $link->update([
            'last_synced_at' => now(),
            'sync_status' => $status,
        ]);

        // Esta llamada YA es una consulta fresca a la plataforma — no dejar
        // que la caché corta de detail() (60s) devuelva un resultado más
        // viejo que el que este resync acaba de confirmar.
        Cache::forget($this->detailCacheKey($link->platform, (string) $link->external_id));

        $this->logAudit($customer, $link->platform, 'synced', $link->external_id);
    }

    /**
     * Busca en la plataforma remota. `ok=false` significa que la plataforma
     * falló o no respondió — distinto de "respondió sin coincidencias".
     *
     * @return array{ok: bool, results: list<array{id: string, name: string, email: string, meta: string}>}
     */
    public function search(string $platform, string $query, string $type): array
    {
        $driver = $this->driverFor($platform);

        if (strlen($query) < 2 || ! $driver) {
            return ['ok' => true, 'results' => []];
        }

        $result = $driver->search($query, $type);

        return ['ok' => $result->ok, 'results' => $result->results];
    }

    /**
     * Ficha completa (nombre/email/NIF/teléfono/ciudad/...) de una plataforma
     * YA vinculada al cliente — reutiliza resync() (mismo driver, mismo shape
     * de datos que search()) en vez de duplicar la llamada remota. A
     * diferencia de link()/unlink(), esto solo consulta y no persiste nada,
     * así que sigue el mismo criterio que search(): no exige identidad
     * verificada (ver LinkCustomerIntegrationRequest).
     *
     * @return array{platform: string, label: string, icon: string, color: ?string, external_id: string, is_critical: bool, available: bool, record: ?array}
     */
    public function detail(Customer $customer, string $platform): array
    {
        $customer->loadMissing('externalIds');
        $link = $customer->externalIds->firstWhere('platform', $platform);

        if (! $link) {
            throw ValidationException::withMessages([
                'platform' => __('helpdeskintegration::messages.link.not_found'),
            ]);
        }

        $provider = $this->providerFor($platform);
        $driver = $this->driverFor($platform);
        $record = $this->fetchDetailRecord($platform, (string) $link->external_id, $driver);

        return [
            'platform' => $platform,
            'label' => $provider?->label ?: ($driver?->defaultLabel() ?? ucfirst($platform)),
            'icon' => $provider?->icon ?? $driver?->defaultIcon() ?? 'fas fa-plug',
            'color' => $provider?->color ?? $driver?->defaultColor(),
            'external_id' => (string) $link->external_id,
            'is_critical' => $provider?->is_critical ?? false,
            'available' => (bool) $record,
            'record' => $record,
        ];
    }

    /**
     * Caché corta (60s) del registro remoto para la ficha de detalle — mismo
     * patrón que ErpContextService::getOrderDetail() para el mismo motivo:
     * un agente que abre/cierra el modal repetidamente sobre el mismo
     * cliente (patrón habitual mientras atiende una conversación) pagaba la
     * latencia completa de la plataforma remota (ERP: hasta 15s de timeout;
     * PrestaShop: hasta 10s) en CADA clic, para un dato que apenas cambia en
     * ese margen. Cache::remember no persiste null aquí a propósito (no se
     * envuelve en un try/gestión especial): una consulta fallida se
     * reintenta en la próxima apertura en vez de quedar "atascada" el resto
     * del minuto.
     */
    private function fetchDetailRecord(string $platform, string $externalId, ?IntegrationDriverContract $driver): ?array
    {
        if (! $driver) {
            return null;
        }

        $cacheKey = $this->detailCacheKey($platform, $externalId);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === self::NO_RECORD_SENTINEL ? null : $cached;
        }

        $result = $driver->resync($externalId);
        $record = ($result && $result->ok)
            ? collect($result->results)->firstWhere('id', $externalId)
            : null;

        // No cachear un fallo de plataforma (result no ok) — solo un "sigue
        // sin registro" confirmado (respuesta ok, pero sin match) merece el
        // mismo TTL que un hit real, para no reintentar en cada clic durante
        // una caída puntual pero tampoco fingir 60s de disponibilidad falsa.
        if ($result && $result->ok) {
            Cache::put($cacheKey, $record ?? self::NO_RECORD_SENTINEL, now()->addSeconds(60));
        }

        return $record;
    }

    /** Distingue "sin registro confirmado" (cacheable) de "sin cachear todavía" (Cache::get devuelve null en ambos casos). */
    private const NO_RECORD_SENTINEL = '__helpdeskintegration_no_record__';

    private function detailCacheKey(string $platform, string $externalId): string
    {
        return "helpdeskintegration:detail:{$platform}:{$externalId}";
    }

    public function link(Customer $customer, string $platform, string $externalId): array
    {
        // Antes se persistía el external_id tal cual lo enviaba el JS del
        // modal; ahora, si la plataforma tiene driver, se comprueba que el ID
        // realmente resuelva a un cliente en el sistema remoto. Los proveedores
        // custom (sin driver) no tienen forma de verificarse y se aceptan.
        $driver = $this->driverFor($platform);

        if ($driver) {
            $result = $driver->resync($externalId);

            if (! $result->ok) {
                throw ValidationException::withMessages([
                    'external_id' => __('helpdeskintegration::messages.link.platform_error'),
                ]);
            }

            if (! $result->found($externalId)) {
                throw ValidationException::withMessages([
                    'external_id' => __('helpdeskintegration::messages.link.not_found'),
                ]);
            }
        }

        // linkExternalId() hace updateOrCreate scopeado a ESTE customer_id,
        // pero la restricción única real es (platform, external_id) SIN
        // customer_id (migración helpdesk_customer_external_ids) — si el id
        // remoto ya está vinculado a OTRO cliente, el lookup interno no lo ve
        // (busca con customer_id=$customer->id) y el INSERT choca con esa
        // restricción. Antes esto era un QueryException 23000 sin capturar →
        // 500 genérico en vez de un mensaje claro, a diferencia de
        // createFromResult() (línea ~347), que sí lo captura.
        try {
            $customer->linkExternalId($platform, $externalId, ['linked_via' => 'manual']);
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            throw ValidationException::withMessages([
                'external_id' => __('helpdeskintegration::messages.link.already_linked_elsewhere'),
            ]);
        }

        $customer->load('externalIds');

        // Por si se re-vincula el mismo external_id que tuvo un vínculo
        // previo (desvinculado y vuelto a vincular) — no dejar que la caché
        // corta de detail() sirva un resultado de la sesión de vínculo
        // anterior.
        Cache::forget($this->detailCacheKey($platform, $externalId));

        $this->logAudit($customer, $platform, 'linked', $externalId);

        return $this->buildPayload($customer);
    }

    /**
     * Crea un Customer nuevo a partir de un resultado de búsqueda externa y
     * lo vincula de una — usado por HelpdeskContacts cuando el agente busca
     * en ERP/PrestaShop desde el listado de contactos (sin un Customer
     * previo abierto) y no hay ninguno existente para vincular. Mismo
     * patrón de verificación contra la plataforma remota que link() (línea
     * 212), para no crear una ficha a partir de un id que ya no resuelve.
     */
    public function createFromResult(string $platform, string $externalId, ?string $phone = null): Customer
    {
        $driver = $this->driverFor($platform);
        $result = $driver?->resync($externalId);

        if (! $driver || ! $result->ok) {
            throw ValidationException::withMessages([
                'external_id' => __('helpdeskintegration::messages.link.platform_error'),
            ]);
        }

        if (! $result->found($externalId)) {
            throw ValidationException::withMessages([
                'external_id' => __('helpdeskintegration::messages.link.not_found'),
            ]);
        }

        $match = collect($result->results)->firstWhere('id', $externalId);

        // El ERP/PrestaShop suele devolver el móvil en formato local sin
        // prefijo de país ("615490503"); se deriva a E.164 para poder
        // habilitar el envío de plantillas WhatsApp sin esperar a que el
        // cliente escriba primero por ese canal.
        $whatsapp = filled($phone) ? app(PhoneNormalizerService::class)->toWhatsappE164($phone) : null;

        $email = filled($match['email'] ?? null) ? $match['email'] : null;

        // Un contacto eliminado (soft-delete) sigue ocupando su email en el
        // índice único de la BD — sin esto, recrearlo con la misma dirección
        // tras "Eliminar" chocaba con una violación de constraint en vez de
        // restaurar el registro existente.
        $customer = $email ? Customer::withTrashed()->where('email', $email)->first() : null;

        if ($customer) {
            $customer->restore();
            $customer->update([
                'name' => $match['name'] ?? $customer->name,
                'phone' => filled($phone) ? $phone : $customer->phone,
                'whatsapp_phone' => $whatsapp ?: $customer->whatsapp_phone,
            ]);
        } else {
            try {
                $customer = Customer::create([
                    'name' => $match['name'] ?? null,
                    'email' => $email,
                    'phone' => filled($phone) ? $phone : null,
                    'whatsapp_phone' => $whatsapp,
                ]);
            } catch (QueryException $e) {
                // Carrera entre el SELECT de arriba y este INSERT (p. ej. un
                // webhook entrante crea el mismo email justo en medio): en
                // vez de un 500, se reutiliza el contacto que ganó la carrera.
                if ($e->getCode() !== '23000' || ! $email) {
                    throw $e;
                }

                $customer = Customer::withTrashed()->where('email', $email)->firstOrFail();
                $customer->restore();
                $customer->update([
                    'name' => $match['name'] ?? $customer->name,
                    'phone' => filled($phone) ? $phone : $customer->phone,
                    'whatsapp_phone' => $whatsapp ?: $customer->whatsapp_phone,
                ]);
            }
        }

        $customer->linkExternalId($platform, $externalId, ['linked_via' => 'created_from_search']);
        $customer->load('externalIds');

        $this->logAudit($customer, $platform, 'linked', $externalId);

        return $customer;
    }

    public function unlink(Customer $customer, string $platform): array
    {
        $link = $customer->externalIds->firstWhere('platform', $platform)
            ?? $customer->externalIds()->where('platform', $platform)->first();

        $customer->externalIds()->where('platform', $platform)->delete();
        $customer->load('externalIds');

        if ($link) {
            Cache::forget($this->detailCacheKey($platform, (string) $link->external_id));
        }

        $this->logAudit($customer, $platform, 'unlinked');

        return $this->buildPayload($customer);
    }

    private function logAudit(Customer $customer, string $platform, string $action, ?string $externalId = null): void
    {
        IntegrationAuditLog::query()->create([
            'customer_id' => $customer->id,
            'platform' => $platform,
            'action' => $action,
            'external_id' => $externalId,
            'user_id' => auth()->id(),
        ]);
    }

    private function lastActivity(Customer $customer): ?string
    {
        $entry = IntegrationAuditLog::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->with('user')
            ->first();

        if (! $entry) {
            return null;
        }

        $described = $this->describeAuditEntry($entry);

        return "{$described['summary']} · {$described['agent']} · {$entry->created_at->diffForHumans()}";
    }

    /**
     * Historial completo de auditoria del cliente (vincular/desvincular/
     * sincronizar/verificar identidad), mas alla de la ultima actividad —
     * para el visor de auditoria del modal de integraciones.
     *
     * @return list<array{summary: string, agent: string, icon: string, created_at: string}>
     */
    public function auditHistory(Customer $customer, int $limit = 50): array
    {
        return IntegrationAuditLog::query()
            ->where('customer_id', $customer->id)
            ->with('user')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (IntegrationAuditLog $entry): array {
                $described = $this->describeAuditEntry($entry);

                return [
                    'summary' => $described['summary'],
                    'agent' => $described['agent'],
                    'icon' => $described['icon'],
                    'created_at' => $entry->created_at->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * @return array{summary: string, agent: string, icon: string}
     */
    private function describeAuditEntry(IntegrationAuditLog $entry): array
    {
        $agent = $entry->user?->full_name ?? __('helpdeskintegration::messages.audit.system');

        if ($entry->platform === 'identity') {
            $summary = match ($entry->action) {
                'identity_verified' => __('helpdeskintegration::messages.audit.identity_verified', [
                    'channel' => $entry->external_id ?: 'email',
                ]),
                'identity_locked' => __('helpdeskintegration::messages.audit.identity_locked'),
                default => $entry->action,
            };

            return [
                'summary' => $summary,
                'agent' => $agent,
                'icon' => $entry->action === 'identity_locked' ? 'fas fa-lock' : 'fas fa-shield-halved',
            ];
        }

        $provider = $this->providerFor($entry->platform);
        $label = $provider?->label ?? ucfirst($entry->platform);

        $summary = in_array($entry->action, ['linked', 'unlinked', 'synced'], true)
            ? __("helpdeskintegration::messages.audit.{$entry->action}", ['label' => $label])
            : $entry->action;

        return [
            'summary' => $summary,
            'agent' => $agent,
            'icon' => 'fas fa-plug',
        ];
    }

    private function driverFor(string $platform): ?IntegrationDriverContract
    {
        $provider = $this->providerFor($platform);

        if (! $provider || $provider->driver === null || ! $this->registry->has($provider->driver)) {
            return null;
        }

        return $this->registry->get($provider->driver);
    }

    private function providerFor(string $platform): ?IntegrationProvider
    {
        return $this->providerByPlatform[$platform] ??= IntegrationProvider::query()
            ->where('platform', $platform)
            ->first();
    }

    private function presentIntegration(IntegrationProvider $provider, mixed $link): array
    {
        $driver = $this->resolveDriver($provider);
        $externalId = $link?->external_id;

        return [
            'platform' => $provider->platform,
            'label' => $provider->label ?: ($driver?->defaultLabel() ?? ucfirst($provider->platform)),
            'description' => $provider->description,
            'icon' => $provider->icon ?? $driver?->defaultIcon() ?? 'fas fa-plug',
            'color' => $provider->color ?? $driver?->defaultColor(),
            'connected' => $externalId !== null,
            'external_id' => $externalId,
            'is_critical' => $provider->is_critical,
            'sync_status' => $link?->sync_status,
            'last_synced_at' => $link?->last_synced_at?->toIso8601String(),
        ];
    }

    private function presentLinkable(IntegrationProvider $provider): array
    {
        $driver = $this->resolveDriver($provider);

        return [
            'platform' => $provider->platform,
            'label' => $provider->label ?: ($driver?->defaultLabel() ?? ucfirst($provider->platform)),
            'icon' => $provider->icon ?? $driver?->defaultIcon() ?? 'fas fa-plug',
            'color' => $provider->color ?? $driver?->defaultColor(),
            'search_types' => $provider->search_types ?: ($driver?->defaultSearchTypes() ?? []),
        ];
    }

    private function resolveDriver(IntegrationProvider $provider): ?IntegrationDriverContract
    {
        if ($provider->driver === null || ! $this->registry->has($provider->driver)) {
            return null;
        }

        return $this->registry->get($provider->driver);
    }
}
