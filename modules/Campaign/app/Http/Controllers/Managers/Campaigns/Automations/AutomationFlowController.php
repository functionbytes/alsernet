<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Domain\Automation\Enum\Branch;
use Modules\Campaign\Domain\Automation\Enum\NodeType;
use Modules\Campaign\Domain\Automation\Enum\TriggerKey;
use Modules\Campaign\Domain\Automation\Flow;
use Modules\Campaign\Domain\Automation\FlowException;
use Modules\Campaign\Domain\Automation\FlowMutator;
use Modules\Campaign\Domain\Automation\NodeOptionsValidator;
use Modules\Campaign\Models\Automation\Automation2;

/**
 * Endpoints REST del editor de flujo (grafo normalizado). Portado de acellemail
 * (Refactor\AutomationFlowController). Cubre la edición de ESTRUCTURA del grafo
 * (nodos wait/wait_until/condition/operation/webhook + trigger). La edición de
 * email-por-nodo (Send) se añade en un incremento posterior (depende de
 * AutomationEmail + infraestructura de envío).
 *
 *   GET    flow-automations/{uid}/flow
 *   POST   flow-automations/{uid}/nodes/new/{type}
 *   PATCH  flow-automations/{uid}/nodes/{nodeId}
 *   DELETE flow-automations/{uid}/nodes/{nodeId}?mode=shift|cascade
 *   PATCH  flow-automations/{uid}/trigger
 */
class AutomationFlowController extends Controller
{
    public function show(Request $request, string $uid): JsonResponse
    {
        $automation = $this->resolveAutomation($uid, 'update');
        if ($automation instanceof JsonResponse) {
            return $automation;
        }

        return $this->ok(Flow::fromJson($automation->data));
    }

    public function createNode(Request $request, string $uid, string $type): JsonResponse
    {
        $automation = $this->resolveAutomation($uid, 'update');
        if ($automation instanceof JsonResponse) {
            return $automation;
        }

        $nodeType = NodeType::tryFrom($type);
        if ($nodeType === null || $nodeType === NodeType::Trigger) {
            return $this->fail("Unsupported node type: {$type}", 422);
        }

        $validated = $request->validate([
            'data' => 'sometimes|array',
            'insertAfter' => 'sometimes|nullable|array',
            'insertAfter.nodeId' => 'sometimes|required|string',
            'insertAfter.branch' => 'sometimes|nullable|in:yes,no',
            'insertBefore' => 'sometimes|nullable|string',
            'insertOnEdge' => 'sometimes|nullable|string',
        ]);

        try {
            $data = NodeOptionsValidator::validateForType($nodeType, $validated['data'] ?? []);
        } catch (FlowException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $positions = array_filter([
            'insertAfter' => $validated['insertAfter'] ?? null,
            'insertBefore' => $validated['insertBefore'] ?? null,
            'insertOnEdge' => $validated['insertOnEdge'] ?? null,
        ]);
        if (count($positions) !== 1) {
            return $this->fail('Provide exactly one of insertAfter / insertBefore / insertOnEdge', 422);
        }

        try {
            $flow = Flow::fromJson($automation->data);

            if (isset($positions['insertAfter'])) {
                $branch = isset($positions['insertAfter']['branch'])
                    ? Branch::tryFrom($positions['insertAfter']['branch'])
                    : null;
                [$flow, $node] = FlowMutator::insertAfter($flow, $positions['insertAfter']['nodeId'], $branch, $nodeType, $data);
            } elseif (isset($positions['insertBefore'])) {
                [$flow, $node] = FlowMutator::insertBefore($flow, $positions['insertBefore'], $nodeType, $data);
            } else {
                [$flow, $node] = FlowMutator::insertOnEdge($flow, $positions['insertOnEdge'], $nodeType, $data);
            }
        } catch (FlowException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $this->persist($automation, $flow);

        return $this->ok($flow, $node->toArray());
    }

    public function updateNode(Request $request, string $uid, string $nodeId): JsonResponse
    {
        $automation = $this->resolveAutomation($uid, 'update');
        if ($automation instanceof JsonResponse) {
            return $automation;
        }

        $validated = $request->validate([
            'data' => 'required|array',
            'type' => 'sometimes|string|in:wait,wait_until',
        ]);

        try {
            $flow = Flow::fromJson($automation->data);

            $existing = $flow->node($nodeId);
            if ($existing->type === NodeType::Trigger) {
                return $this->fail('Use PATCH /trigger to update the trigger', 422);
            }

            $targetType = isset($validated['type']) ? NodeType::from($validated['type']) : $existing->type;
            $data = NodeOptionsValidator::validateForType($targetType, $validated['data']);

            [$flow, $node] = $targetType === $existing->type
                ? FlowMutator::replaceNode($flow, $nodeId, $data)
                : FlowMutator::replaceNodeWithType($flow, $nodeId, $targetType, $data);
        } catch (FlowException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $this->persist($automation, $flow);

        return $this->ok($flow, $node->toArray());
    }

    public function deleteNode(Request $request, string $uid, string $nodeId): JsonResponse
    {
        $automation = $this->resolveAutomation($uid, 'update');
        if ($automation instanceof JsonResponse) {
            return $automation;
        }

        $mode = $request->query('mode', 'shift');

        try {
            $flow = Flow::fromJson($automation->data);
            $flow = match ($mode) {
                'shift' => FlowMutator::deleteShift($flow, $nodeId),
                'cascade' => FlowMutator::deleteCascade($flow, $nodeId),
                default => throw new FlowException("Unknown delete mode: {$mode}"),
            };
        } catch (FlowException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $this->persist($automation, $flow);

        return $this->ok($flow);
    }

    public function updateTrigger(Request $request, string $uid): JsonResponse
    {
        $automation = $this->resolveAutomation($uid, 'update');
        if ($automation instanceof JsonResponse) {
            return $automation;
        }

        $validated = $request->validate([
            'triggerKey' => 'required|string',
            'data' => 'sometimes|array',
        ]);

        $key = TriggerKey::tryFrom($validated['triggerKey']);
        if ($key === null) {
            return $this->fail("Unknown trigger key: {$validated['triggerKey']}", 422);
        }

        try {
            $data = NodeOptionsValidator::validateTrigger($key, $validated['data'] ?? []);
        } catch (FlowException $e) {
            return $this->fail($e->getMessage(), 422);
        }
        $data['key'] = $key->value;
        unset($data['init']);

        try {
            $flow = Flow::fromJson($automation->data);
            [$flow, $node] = FlowMutator::replaceTrigger($flow, $data);
        } catch (FlowException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $this->persist($automation, $flow);

        return $this->ok($flow, $node->toArray());
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function resolveAutomation(string $uid, string $ability)
    {
        $automation = Automation2::findByUid($uid);
        if (! $automation) {
            return $this->fail('Automation not found', 404);
        }
        if (Gate::denies($ability, $automation)) {
            return $this->fail('Not authorized', 403);
        }

        return $automation;
    }

    private function persist(Automation2 $automation, Flow $flow): void
    {
        $automation->data = $flow->toJson();
        $automation->save();
    }

    private function ok(Flow $flow, ?array $node = null): JsonResponse
    {
        $body = ['ok' => true, 'flow' => $flow->toArray()];
        if ($node !== null) {
            $body['node'] = $node;
        }

        return response()->json($body);
    }

    private function fail(string $error, int $status): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $error], $status);
    }
}
