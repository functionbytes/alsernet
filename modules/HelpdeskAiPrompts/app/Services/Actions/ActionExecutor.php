<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Throwable;

/**
 * Ejecuta una acción del catálogo. Mismo ejecutor para IA, flujos, agentes y
 * API. Orden: disponibilidad -> canal -> verificación -> confirmación ->
 * argumentos -> cuota -> propiedad -> bridge/http -> respuesta filtrada.
 * Cada ejecución queda registrada; la IA nunca recibe errores internos.
 */
class ActionExecutor
{
    public const UNAVAILABLE = 'La acción no está disponible.';

    private const SOURCES = ['ai', 'flow', 'agent', 'api', 'test'];

    private const QUOTA_TTL_HOURS = 6;

    public function __construct(
        private readonly TemplateResolver $templates,
        private readonly ResponseShaper $shaper,
        private readonly Redactor $redactor,
        private readonly HttpActionClient $http,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @param  array<string, mixed>  $ctx  verified, customer_email, customer_ps_id, customer_erp_id, conversation_id, trace_id, channel, locale
     * @return array{ok: bool, content: string, status: string}
     */
    public function run(string $key, array $args, array $ctx, string $source = 'ai'): array
    {
        $startedAt = hrtime(true);
        $source = in_array($source, self::SOURCES, true) ? $source : 'ai';
        $action = null;
        $argsSummary = [];
        $reason = null;
        $status = ActionRefusal::ERROR;
        $content = self::UNAVAILABLE;

        try {
            $action = AiAction::query()
                ->where('key', $key)
                ->where('is_active', true)
                ->whereIn('type', [AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP])
                ->first();

            if ($action === null) {
                throw ActionRefusal::error('unknown_or_inactive');
            }

            $content = $this->execute($action, $args, $ctx, $argsSummary);
            $status = 'ok';
        } catch (ActionRefusal $refusal) {
            $status = $refusal->status;
            $content = $refusal->publicMessage;
            $reason = $refusal->reason;
        } catch (Throwable $e) {
            $reason = $this->scrub(class_basename($e).': '.$e->getMessage(), $action);
            Log::warning('AiActions: fallo al ejecutar la acción', ['action' => $key, 'error' => $reason]);
        }

        $this->record($key, $source, $ctx, $status, $reason, $startedAt, $argsSummary ?: $this->redactor->summarizeArgs($args));

        return ['ok' => $status === 'ok', 'content' => $content, 'status' => $status];
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $argsSummary  salida: resumen redactado de los argumentos válidos
     *
     * @throws ActionRefusal
     */
    private function execute(AiAction $action, array $args, array $ctx, array &$argsSummary): string
    {
        $verified = ! empty($ctx['verified']);
        $rules = (array) $action->rules;

        if (! $this->allowedForChannel($action->channels, $ctx['channel'] ?? null)) {
            throw ActionRefusal::error('channel_not_allowed');
        }

        if (! empty($rules['requires_verified']) && ! $verified) {
            throw ActionRefusal::denied('Para esto necesito que el cliente esté identificado. Pídele que inicie sesión o verifique su identidad.', 'requires_verified');
        }

        $definition = $this->definition($action);

        if (ActionParameters::needsConfirmation($definition) && ($args[ActionParameters::CONFIRM] ?? false) !== true) {
            throw ActionRefusal::denied('Pregunta antes al cliente si confirma esta acción y vuelve a llamarla solo cuando lo confirme expresamente.', 'not_confirmed');
        }

        $clean = $this->validateArgs(ActionParameters::effective($definition, $verified), $args);
        $argsSummary = $this->redactor->summarizeArgs($clean);

        $this->consumeQuota($action, $ctx);

        $ownership = $this->resolveOwnership($definition, $clean, $ctx);
        $vars = [
            'args' => $clean,
            'customer' => $verified ? $this->verifiedCustomer($ctx) : null,
            'order' => $ownership['order'],
            'conversation' => ['id' => $ctx['conversation_id'] ?? null],
        ];

        $timeout = min(max((int) ($rules['timeout'] ?? config('ai-actions.default_timeout', 8)), 1), (int) config('ai-actions.max_timeout', 10));

        [$ok, $result] = $action->type === AiAction::TYPE_BRIDGE
            ? $this->callBridge($action, $vars, $ownership, $ctx)
            : [true, $this->http->request((array) $action->config, $vars, (array) ($action->secrets ?? []), $timeout)];

        if ($result === null && ! $ok) {
            throw ActionRefusal::error('write_failed', 'La acción no se ha podido completar.');
        }

        $content = $this->shaper->shape($result, (array) $action->response);

        if (! $ok) {
            throw ActionRefusal::error('semantic_failure', $content);
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $vars
     * @param  array{lookup: ?array<string, mixed>, order: ?array<string, mixed>}  $ownership
     * @param  array<string, mixed>  $ctx
     * @return array{0: bool, 1: mixed}
     *
     * @throws ActionRefusal
     */
    private function callBridge(AiAction $action, array $vars, array $ownership, array $ctx): array
    {
        $bridgeAction = (string) ($action->config['action'] ?? '');
        $spec = BridgeAllowlist::find($bridgeAction);

        if ($spec === null) {
            throw ActionRefusal::error('not_allowlisted');
        }

        $payload = (array) $this->templates->resolve((array) ($action->config['payload'] ?? []), $vars);
        unset($payload['lookup']);

        if ($spec['customer']) {
            if ($ownership['lookup'] === null) {
                throw ActionRefusal::denied('Necesito identificar al cliente antes de hacer eso.', 'no_lookup');
            }
            $payload['lookup'] = $ownership['lookup'];
        }

        if ($spec['order'] === true && $ownership['order'] === null) {
            throw ActionRefusal::denied('Indica el pedido sobre el que quieres hacer la consulta.', 'order_required');
        }

        if ($spec['order'] !== false && $ownership['order'] !== null) {
            $payload['order_id'] = (int) $ownership['order']['id'];
        }

        $write = $spec['mode'] === 'write';
        $idempotencyKey = $write ? $this->idempotencyKey($action->key, $payload, $ctx) : null;

        $result = $this->prestashop()->callAllowedAction($bridgeAction, $payload, $idempotencyKey);

        if ($result === null && $write) {
            return [false, null];
        }

        $semanticFailure = is_array($result) && ($result['ok_semantic'] ?? true) === false;

        return [! $semanticFailure, $result];
    }

    /**
     * Comprueba de quién es la consulta. Los datos del cliente salen SIEMPRE del
     * contexto verificado, nunca de los argumentos del modelo (salvo el par
     * pedido+email, que se contrasta con la tienda).
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $args
     * @param  array<string, mixed>  $ctx
     * @return array{lookup: ?array<string, mixed>, order: ?array<string, mixed>}
     *
     * @throws ActionRefusal
     */
    private function resolveOwnership(array $definition, array $args, array $ctx): array
    {
        $ownership = ActionParameters::ownership($definition);

        if ($ownership === 'none') {
            return ['lookup' => null, 'order' => null];
        }

        $verified = ! empty($ctx['verified']);

        if ($ownership === 'verified' && ! $verified) {
            throw ActionRefusal::denied('Para esto necesito que el cliente esté identificado. Pídele que inicie sesión o verifique su identidad.', 'requires_verified');
        }

        $email = $verified ? $this->nullable($ctx['customer_email'] ?? null) : $this->nullable($args[ActionParameters::EMAIL] ?? null);
        $psId = $verified && is_numeric($ctx['customer_ps_id'] ?? null) ? (int) $ctx['customer_ps_id'] : null;

        $lookup = $this->prestashop()->ownershipLookup($email, $psId, 'ai-action');
        if ($lookup === null) {
            throw ActionRefusal::denied('No he podido identificar al cliente.', 'no_identity');
        }

        $order = null;
        if (ActionParameters::needsOrderRef($definition)) {
            $order = $this->resolveOrder((string) ($args[ActionParameters::ORDER_REF] ?? ''), $email, $psId, requireEmailMatch: ! $verified);
        }

        return ['lookup' => $lookup, 'order' => $order];
    }

    /**
     * @return array{id: int, reference: ?string}
     *
     * @throws ActionRefusal
     */
    private function resolveOrder(string $ref, ?string $email, ?int $psId, bool $requireEmailMatch): array
    {
        $deny = fn (string $why): ActionRefusal => ActionRefusal::denied('No he podido verificar ese pedido con los datos facilitados.', $why);

        if ($ref === '') {
            throw $deny('order_ref_missing');
        }

        $ps = $this->prestashop();
        $detail = ctype_digit($ref) ? $ps->getOrderDetail((int) $ref, $email, $psId) : null;
        $detail ??= $ps->getOrderDetailByReference($ref, $email, $psId);

        if (! is_array($detail) || (int) ($detail['id'] ?? 0) <= 0) {
            throw $deny('order_not_found_for_customer');
        }

        if ($requireEmailMatch) {
            $orderEmail = mb_strtolower(trim((string) ($detail['customer_email'] ?? '')));
            if ($orderEmail === '' || $orderEmail !== mb_strtolower((string) $email)) {
                throw $deny('order_email_mismatch');
            }
        }

        return ['id' => (int) $detail['id'], 'reference' => $detail['reference'] ?? null];
    }

    /**
     * @param  array<int, array<string, mixed>>  $params
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     *
     * @throws ActionRefusal
     */
    private function validateArgs(array $params, array $args): array
    {
        $clean = [];

        foreach ($params as $param) {
            $name = $param['name'];
            $value = $args[$name] ?? null;

            if ($value === null || $value === '') {
                if (! empty($param['required'])) {
                    throw ActionRefusal::error("missing_{$name}", "Falta el parámetro «{$name}».");
                }

                continue;
            }

            $clean[$name] = $this->coerce($param, $value);
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $param
     *
     * @throws ActionRefusal
     */
    private function coerce(array $param, mixed $value): mixed
    {
        $name = $param['name'];
        $invalid = fn (): ActionRefusal => ActionRefusal::error("invalid_{$name}", "El parámetro «{$name}» no es válido.");

        if (is_array($value)) {
            throw $invalid();
        }

        switch ($param['type']) {
            case 'boolean':
                return is_bool($value) ? $value : throw $invalid();
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw $invalid();
            case 'number':
                return is_numeric($value) ? $value + 0 : throw $invalid();
            case 'email':
                $email = mb_strtolower(trim((string) $value));

                return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : throw $invalid();
            case 'enum':
                return in_array((string) $value, array_map('strval', (array) ($param['enum'] ?? [])), true) ? (string) $value : throw $invalid();
        }

        $text = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $value) ?? '');

        if (mb_strlen($text) > (int) ($param['max_length'] ?? 300)) {
            throw $invalid();
        }

        if (isset($param['pattern']) && preg_match('~'.str_replace('~', '\~', (string) $param['pattern']).'~u', $text) !== 1) {
            throw $invalid();
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $ctx
     *
     * @throws ActionRefusal
     */
    private function consumeQuota(AiAction $action, array $ctx): void
    {
        $max = max(1, (int) ($action->rules['max_per_conversation'] ?? config('ai-actions.default_max_per_conversation', 5)));
        $scope = isset($ctx['conversation_id']) ? 'c'.$ctx['conversation_id'] : 't'.($ctx['trace_id'] ?? 'global');
        $cacheKey = "ai-actions:quota:{$scope}:{$action->key}";

        Cache::add($cacheKey, 0, now()->addHours(self::QUOTA_TTL_HOURS));

        if ((int) Cache::increment($cacheKey) > $max) {
            throw ActionRefusal::denied('Se ha alcanzado el límite de usos de esta consulta en la conversación. Deriva al cliente a un agente si necesita más.', 'rate_limited');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $ctx
     */
    private function idempotencyKey(string $key, array $payload, array $ctx): string
    {
        $scope = $ctx['conversation_id'] ?? $ctx['trace_id'] ?? 'none';

        return 'ai-'.substr(hash('sha256', $scope.'|'.$key.'|'.json_encode($payload)), 0, 40);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $argsSummary
     */
    private function record(string $key, string $source, array $ctx, string $status, ?string $reason, int $startedAt, array $argsSummary): void
    {
        try {
            AiActionRun::query()->create([
                'action_key' => mb_substr($key, 0, 48),
                'source' => $source,
                'trace_id' => isset($ctx['trace_id']) ? mb_substr((string) $ctx['trace_id'], 0, 64) : null,
                'conversation_id' => $ctx['conversation_id'] ?? null,
                'status' => $status,
                'error' => $reason !== null ? mb_substr($this->redactor->redactString($reason), 0, 255) : null,
                'latency_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'args_summary' => $argsSummary,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('AiActions: no se pudo registrar la ejecución', ['action' => $key, 'error' => class_basename($e)]);
        }
    }

    /**
     * Quita secretos y datos personales de un mensaje de error antes de loguearlo.
     */
    private function scrub(string $message, ?AiAction $action): string
    {
        foreach ((array) ($action?->secrets ?? []) as $secret) {
            if (is_string($secret) && $secret !== '') {
                $message = str_replace($secret, '[secret]', $message);
            }
        }

        $message = preg_replace('/Bearer\s+\S+/i', 'Bearer [secret]', $message) ?? $message;

        return mb_substr($this->redactor->redactString($message), 0, 300);
    }

    /**
     * @param  array<int, string>|null  $channels
     */
    private function allowedForChannel(?array $channels, ?string $channel): bool
    {
        if ($channels === null || $channels === []) {
            return true;
        }

        return $channel !== null && in_array($channel, $channels, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(AiAction $action): array
    {
        return [
            'type' => $action->type,
            'config' => $action->config,
            'rules' => $action->rules,
            'parameters' => $action->parameters,
        ];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array{email: ?string, ps_id: ?int}
     */
    private function verifiedCustomer(array $ctx): array
    {
        return [
            'email' => $this->nullable($ctx['customer_email'] ?? null),
            'ps_id' => is_numeric($ctx['customer_ps_id'] ?? null) ? (int) $ctx['customer_ps_id'] : null,
        ];
    }

    private function nullable(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : mb_strtolower($text);
    }

    private function prestashop(): PrestashopContextService
    {
        if (! class_exists(PrestashopContextService::class)) {
            throw ActionRefusal::error('prestashop_unavailable');
        }

        return app(PrestashopContextService::class);
    }
}
