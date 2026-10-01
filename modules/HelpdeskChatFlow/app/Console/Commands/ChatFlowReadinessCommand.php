<?php

namespace Modules\HelpdeskChatFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowTestCase;
use Modules\HelpdeskChatFlow\Services\ChatFlowTriggerResolver;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Nwidart\Modules\Facades\Module;
use Throwable;

/**
 * Pre-activation checklist for the ChatFlow AI assistant. Read-only: it never
 * enables anything. A blocking KO makes the command exit with code 1; a WARN is
 * advice that does not block the activation.
 */
class ChatFlowReadinessCommand extends Command
{
    private const OK = 'OK';

    private const KO = 'KO';

    private const WARN = 'AVISO';

    protected $signature = 'chatflow:readiness';

    protected $description = 'Check that the ChatFlow AI assistant is ready to be activated (OK/KO per requirement)';

    private int $blocking = 0;

    private int $warnings = 0;

    public function handle(): int
    {
        $this->components->info('ChatFlow: preparacion para activar el asistente IA');

        $this->check('Modulo HelpdeskChatFlow activo', fn () => $this->moduleEnabled());
        $this->check('Interruptor de integracion (kill switch)', fn () => $this->killSwitch());
        $this->check('OPENAI_API_KEY configurada', fn () => $this->openAiKey());
        $this->check('Cola de trabajos en marcha', fn () => $this->queue());
        $this->check('Reverb (tiempo real) configurado', fn () => $this->reverb());
        $this->check('Biblioteca de prompts (casos + bloque base)', fn () => $this->promptLibrary());
        $this->check('Catalogo de acciones activas', fn () => $this->actionCatalog());
        $this->check('Flujos activos con nodo ai_agent y rollout', fn () => $this->aiFlows());
        $this->check('Procedimientos (flujos con disparador procedure)', fn () => $this->procedures());
        $this->check('Bridge de la tienda alcanzable', fn () => $this->bridge());
        $this->check('Tests de regresion de los flujos', fn () => $this->regressionTests());

        $this->newLine();

        if ($this->blocking > 0) {
            $this->components->error("{$this->blocking} requisito(s) bloqueante(s) en KO: NO activar todavia.");

            return self::FAILURE;
        }

        $suffix = $this->warnings > 0 ? " ({$this->warnings} aviso(s) no bloqueantes)" : '';
        $this->components->info('Sin KO bloqueantes: listo para el piloto'.$suffix.'.');

        return self::SUCCESS;
    }

    /**
     * @param  callable(): array{0: string, 1: string, 2?: string}  $check  [status, detail, advice]
     */
    private function check(string $label, callable $check): void
    {
        try {
            [$status, $detail, $advice] = $check() + [2 => ''];
        } catch (Throwable $e) {
            [$status, $detail, $advice] = [self::KO, 'Error al comprobar: '.Str::limit($e->getMessage(), 160), ''];
        }

        match ($status) {
            self::OK => $this->line("  <fg=green>[OK]</>    {$label}: {$detail}"),
            self::WARN => $this->line("  <fg=yellow>[AVISO]</> {$label}: {$detail}"),
            default => $this->line("  <fg=red>[KO]</>    {$label}: {$detail}"),
        };

        if ($status === self::KO) {
            $this->blocking++;
        }

        if ($status === self::WARN) {
            $this->warnings++;
        }

        if ($advice !== '' && $status !== self::OK) {
            $this->line("          -> {$advice}");
        }
    }

    // ==================== Checks ====================

    /** @return array{0: string, 1: string, 2?: string} */
    protected function moduleEnabled(): array
    {
        if (Module::find('HelpdeskChatFlow')?->isEnabled()) {
            return [self::OK, 'habilitado en modules_statuses.json'];
        }

        return [self::KO, 'deshabilitado', 'Habilitar "HelpdeskChatFlow": true en modules_statuses.json (decision del responsable) y limpiar cache.'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function killSwitch(): array
    {
        if (helpdesk_chatflow_enabled()) {
            return [self::OK, 'helpdesk_chatflow_enabled() = true'];
        }

        return [self::KO, 'helpdesk_chatflow_enabled() = false', 'Activar el ajuste chatflow.integration_enabled (y el modulo) en Ajustes de Helpdesk.'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function openAiKey(): array
    {
        $key = (string) config('services.openai.key', '');

        if ($key === '') {
            return [self::KO, 'no configurada', 'Definir OPENAI_API_KEY en .env y ejecutar php artisan config:clear.'];
        }

        return [self::OK, 'configurada ('.strlen($key).' caracteres, oculta)'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function queue(): array
    {
        $driver = (string) config('queue.default', 'sync');

        if ($driver === 'sync') {
            return [self::KO, 'QUEUE_CONNECTION=sync', 'Los nodos con retardo y la entrega del bot necesitan cola real (redis + Horizon).'];
        }

        $horizon = $this->horizonStatus();

        if ($horizon === null) {
            return [self::WARN, "driver {$driver}, Horizon no instalado: no se puede comprobar el worker", 'Verificar a mano que hay un queue:work en ejecucion.'];
        }

        if ($horizon === 'running') {
            return [self::OK, "driver {$driver}, Horizon en ejecucion"];
        }

        $detail = $horizon === 'inactive' ? 'Horizon no esta en ejecucion' : "Horizon en estado {$horizon}";

        return [self::KO, $detail, 'Arrancar php artisan horizon (o reanudarlo con horizon:continue).'];
    }

    /**
     * null = Horizon not installed; otherwise running | paused | inactive.
     */
    protected function horizonStatus(): ?string
    {
        $repository = 'Laravel\\Horizon\\Contracts\\MasterSupervisorRepository';

        if (! interface_exists($repository)) {
            return null;
        }

        $masters = collect(app($repository)->all());

        if ($masters->isEmpty()) {
            return 'inactive';
        }

        return $masters->contains(fn ($master) => ($master->status ?? null) === 'paused') ? 'paused' : 'running';
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function reverb(): array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return [self::KO, 'BROADCAST_CONNECTION='.config('broadcasting.default'), 'Usar BROADCAST_CONNECTION=reverb para que el widget reciba las respuestas del bot.'];
        }

        $missing = collect(['app_id' => 'REVERB_APP_ID', 'key' => 'REVERB_APP_KEY', 'secret' => 'REVERB_APP_SECRET'])
            ->filter(fn (string $env, string $key) => blank(config("broadcasting.connections.reverb.{$key}")))
            ->values();

        if ($missing->isNotEmpty()) {
            return [self::KO, 'faltan '.$missing->implode(', '), 'Definir las variables REVERB_* en .env.'];
        }

        return [self::OK, 'driver reverb con credenciales (no se prueba el socket)'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function promptLibrary(): array
    {
        $cases = AiPromptCase::query()->where('is_active', true)->count();
        $baseBlocks = AiPromptBlock::query()->where('is_active', true)->where('kind', 'base')->count();

        if ($cases > 0 && $baseBlocks > 0) {
            return [self::OK, "{$cases} caso(s) activo(s), {$baseBlocks} bloque(s) base"];
        }

        return [self::KO, "{$cases} caso(s) activo(s), {$baseBlocks} bloque(s) base", 'Ejecutar el seeder AiPromptLibrarySeeder o crear casos y un bloque base en la biblioteca de prompts.'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function actionCatalog(): array
    {
        $active = AiAction::query()->where('is_active', true)->count();

        if ($active > 0) {
            return [self::OK, "{$active} accion(es) activa(s)"];
        }

        return [self::WARN, 'ninguna accion activa: el asistente no podra consultar pedidos ni catalogo', 'Ejecutar AiActionCatalogSeeder y revisar/activar las acciones.'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function aiFlows(): array
    {
        $flows = ChatFlow::query()->active()
            ->where('trigger_type', '!=', ChatFlow::TRIGGER_PROCEDURE)
            ->get()
            ->filter(fn (ChatFlow $flow) => $this->hasAiAgentNode($flow));

        if ($flows->isEmpty()) {
            return [self::WARN, 'no hay flujos activos con nodo ai_agent', 'Crear el flujo desde la plantilla "Asistente de compras (IA)" con rollout 10%.'];
        }

        $summary = $flows->map(fn (ChatFlow $flow) => "#{$flow->id} {$flow->name} ({$this->rolloutLabel($flow)})")->implode('; ');

        $full = $flows->first(fn (ChatFlow $flow) => ChatFlowTriggerResolver::rolloutPercent($flow) >= 100);

        if ($full !== null) {
            return [self::WARN, $summary, 'Hay flujos con rollout 100%: para un primer despliegue usar 10% (trigger_conditions.rollout_percent).'];
        }

        return [self::OK, $summary];
    }

    private function hasAiAgentNode(ChatFlow $flow): bool
    {
        return collect($flow->runtimeNodes())->contains(fn ($node) => ($node['type'] ?? null) === 'ai_agent');
    }

    private function rolloutLabel(ChatFlow $flow): string
    {
        $label = 'rollout '.ChatFlowTriggerResolver::rolloutPercent($flow).'%';
        $inboxes = ChatFlowTriggerResolver::allowedInboxIds($flow);

        return $inboxes === [] ? $label : $label.', buzones '.implode(',', $inboxes);
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function procedures(): array
    {
        $procedures = ChatFlow::query()->active()->where('trigger_type', ChatFlow::TRIGGER_PROCEDURE)->count();

        if ($procedures > 0) {
            return [self::OK, "{$procedures} procedimiento(s) activo(s)"];
        }

        return [self::WARN, 'ningun procedimiento activo', 'Opcional: los casos con procedure_flow_id necesitan su flujo procedure publicado.'];
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function bridge(): array
    {
        if (blank(config('helpdeskprestashop.api_url')) || blank(config('helpdeskprestashop.webhook_secret'))) {
            return [self::KO, 'URL o secreto del bridge sin configurar', 'Definir ALSERNETBRIDGE_API_URL y ALSERNETBRIDGE_WEBHOOK_SECRET.'];
        }

        $started = microtime(true);

        try {
            $result = $this->probeBridge();
        } catch (PsUpstreamException $e) {
            return [self::KO, 'bridge no responde: '.$e->getMessage(), 'Revisar la URL, la firma HMAC y el circuit breaker del bridge.'];
        }

        $ms = (int) ((microtime(true) - $started) * 1000);

        if ($result === null) {
            return [self::KO, "respuesta vacia o error HTTP tras {$ms} ms", 'Revisar los logs de HelpdeskPrestashop.'];
        }

        return [self::OK, "responde a product.search en {$ms} ms"];
    }

    /**
     * Cheap read action that needs no customer (product.search, limit 1),
     * with a short timeout so the check never hangs.
     *
     * @return array<string, mixed>|null
     *
     * @throws PsUpstreamException
     */
    protected function probeBridge(): ?array
    {
        $original = [
            'http_timeout' => config('helpdeskprestashop.http_timeout'),
            'http_connect_timeout' => config('helpdeskprestashop.http_connect_timeout'),
        ];

        config(['helpdeskprestashop.http_timeout' => 5, 'helpdeskprestashop.http_connect_timeout' => 3]);

        try {
            return app(PrestashopContextService::class)->callAllowedAction('product.search', ['query' => 'a', 'limit' => 1]);
        } finally {
            config([
                'helpdeskprestashop.http_timeout' => $original['http_timeout'],
                'helpdeskprestashop.http_connect_timeout' => $original['http_connect_timeout'],
            ]);
        }
    }

    /** @return array{0: string, 1: string, 2?: string} */
    protected function regressionTests(): array
    {
        if (! Schema::connection('helpdesk')->hasTable((new ChatFlowTestCase)->getTable())) {
            return [self::WARN, 'tabla de casos de test inexistente', 'Ejecutar las migraciones del modulo.'];
        }

        $failed = ChatFlowTestCase::query()->where('last_result', 'failed')->count();
        $passed = ChatFlowTestCase::query()->where('last_result', 'passed')->count();

        if ($failed > 0) {
            return [self::KO, "{$failed} caso(s) de test en fallo ({$passed} OK)", 'Ejecutar php artisan chatflow:test-cases y corregir los flujos antes de activar.'];
        }

        if ($passed === 0) {
            return [self::WARN, 'sin casos de test ejecutados', 'Crear casos de test y ejecutarlos (chatflow:test-cases).'];
        }

        return [self::OK, "{$passed} caso(s) de test OK"];
    }
}
