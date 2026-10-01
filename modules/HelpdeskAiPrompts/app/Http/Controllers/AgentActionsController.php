<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskAiPrompts\Http\Requests\RunAgentActionRequest;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionPanel;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionParameters;

/**
 * Acciones del catálogo lanzadas por un agente desde la bandeja (source
 * `agent`). Los datos del cliente salen SIEMPRE de la conversación, nunca de
 * lo que envía el navegador; la identidad cuenta como verificada solo si la
 * conversación ya lo está (metadata.identity_verified) o si el agente lo
 * confirma de forma explícita, y esa confirmación queda en el registro.
 */
class AgentActionsController extends Controller
{
    private const ORDER_REF_KEYS = ['order_ref', 'order_reference', 'reference', 'order_number', 'order_id'];

    public function index(Request $request, Conversation $conversation, ActionPanel $panel): JsonResponse
    {
        abort_unless($request->user()->can('view', $conversation), 403);

        $actions = AiAction::query()
            ->where('is_active', true)
            ->whereIn('type', [AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP])
            ->orderBy('name')
            ->get();

        $email = $this->customerEmail($conversation);

        return response()->json([
            'context' => [
                'identity_verified' => $this->identityVerified($conversation),
                'customer_email' => $email,
                'has_customer' => $email !== null,
                'order_ref' => $this->orderRef($conversation),
            ],
            'actions' => $actions->map(fn (AiAction $action): array => [
                'key' => $action->key,
                'name' => $action->name,
                'description' => $action->description,
                'write' => $panel->isWrite($action),
                'requires_verified' => ! empty($action->rules['requires_verified']),
                'params' => $this->visibleParams($panel, $action),
            ])->values(),
        ]);
    }

    public function run(RunAgentActionRequest $request, Conversation $conversation, ActionExecutor $executor, ActionPanel $panel): JsonResponse
    {
        $action = AiAction::query()
            ->where('key', $request->string('action_key')->toString())
            ->where('is_active', true)
            ->whereIn('type', [AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP])
            ->first();

        if ($action === null) {
            return response()->json(['message' => __('helpdeskaiprompts::agent-actions.not_available')], 404);
        }

        $write = $panel->isWrite($action);

        if ($write && ! $request->boolean('write_confirmed')) {
            return response()->json(['message' => __('helpdeskaiprompts::agent-actions.write_not_confirmed')], 422);
        }

        $conversationVerified = $this->identityVerified($conversation);
        $verified = $conversationVerified || $request->boolean('identity_confirmed');
        $email = $this->customerEmail($conversation);

        $args = $this->coerceArgs($panel->effectiveParams($action, $verified), (array) $request->input('args', []));

        if ($write) {
            $args[ActionParameters::CONFIRM] = true;
        }

        $startedAt = hrtime(true);
        $result = $executor->run($action->key, $args, [
            'verified' => $verified,
            'customer_email' => $verified ? $email : null,
            'customer_ps_id' => $verified ? $this->prestashopId($conversation) : null,
            'customer_erp_id' => $verified ? $conversation->customer?->externalIdFor('erp') : null,
            'conversation_id' => $conversation->id,
            'trace_id' => 'agent-'.$request->user()->id.'-'.uniqid(),
            'channel' => $conversation->channel,
            'locale' => app()->getLocale(),
            'user_id' => $request->user()->id,
            'agent_verified' => $verified && ! $conversationVerified,
        ], 'agent');

        return response()->json([
            'ok' => $result['ok'],
            'status' => $result['status'],
            'content' => $result['content'],
            'latency_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ]);
    }

    /**
     * Parámetros que el agente puede rellenar. La confirmación del cliente es
     * la del propio agente (casilla del modal) y el email solo se pide cuando
     * la identidad no está verificada.
     *
     * @return array<int, array<string, mixed>>
     */
    private function visibleParams(ActionPanel $panel, AiAction $action): array
    {
        return array_values(array_filter(
            $panel->testParams($action),
            fn (array $param): bool => $param['name'] !== ActionParameters::CONFIRM,
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $params
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function coerceArgs(array $params, array $input): array
    {
        $args = [];

        foreach ($params as $param) {
            $value = $input[$param['name']] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $args[$param['name']] = $param['type'] === 'boolean'
                ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                : $value;
        }

        return $args;
    }

    private function identityVerified(Conversation $conversation): bool
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];

        return ! empty($metadata['identity_verified']) && $this->customerEmail($conversation) !== null;
    }

    private function customerEmail(Conversation $conversation): ?string
    {
        $email = trim((string) $conversation->customer?->email);

        return $email === '' ? null : $email;
    }

    private function prestashopId(Conversation $conversation): ?int
    {
        $id = $conversation->customer?->externalIdFor('prestashop');

        return is_numeric($id) ? (int) $id : null;
    }

    private function orderRef(Conversation $conversation): ?string
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $candidates = [];

        foreach (['order', 'context'] as $nested) {
            $candidates[] = (array) ($metadata[$nested] ?? []);
        }

        $candidates[] = $metadata;

        foreach ($candidates as $source) {
            foreach (self::ORDER_REF_KEYS as $key) {
                $value = $source[$key] ?? null;

                if (is_scalar($value) && preg_match('/^[A-Za-z0-9._\-]{1,40}$/', (string) $value) === 1) {
                    return (string) $value;
                }
            }
        }

        return null;
    }
}
