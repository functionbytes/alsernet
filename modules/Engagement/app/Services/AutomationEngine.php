<?php

namespace Modules\Engagement\Services;

use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\AutomationFlow;
use Modules\Engagement\Models\AutomationRun;

class AutomationEngine
{
    public function startFlowsForEvent(string $event, string $sessionToken, int $inboxId, ?int $customerId, array $eventPayload = []): void
    {
        $flows = AutomationFlow::query()
            ->forInbox($inboxId)
            ->forEvent($event)
            ->active()
            ->get();

        foreach ($flows as $flow) {
            $existing = AutomationRun::query()
                ->where('flow_id', $flow->id)
                ->where('session_token', $sessionToken)
                ->whereIn('status', [AutomationRun::STATUS_RUNNING])
                ->exists();

            if ($existing) {
                continue;
            }

            $this->start($flow, $sessionToken, $customerId, $eventPayload);
        }
    }

    public function start(AutomationFlow $flow, string $sessionToken, ?int $customerId, array $context = []): ?AutomationRun
    {
        $startNode = $flow->getStartNode();
        if (! $startNode) {
            return null;
        }

        $run = AutomationRun::query()->create([
            'flow_id' => $flow->id,
            'session_token' => $sessionToken,
            'customer_id' => $customerId,
            'current_node_id' => $startNode['id'] ?? null,
            'status' => AutomationRun::STATUS_RUNNING,
            'context' => $context,
            'started_at' => now(),
        ]);

        $this->processNode($run, $flow, $startNode);

        return $run;
    }

    public function advance(AutomationRun $run, ?string $userInput = null): void
    {
        $flow = $run->flow;
        if (! $flow || ! $run->current_node_id) {
            return;
        }

        $branchKey = $userInput ? mb_strtolower(trim($userInput)) : null;
        $nextNodeId = $flow->getNextNodeId($run->current_node_id, $branchKey);

        if (! $nextNodeId) {
            $run->update([
                'status' => AutomationRun::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            return;
        }

        $nextNode = $flow->findNode($nextNodeId);
        if (! $nextNode) {
            $run->update([
                'status' => AutomationRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);

            return;
        }

        $run->update(['current_node_id' => $nextNodeId]);
        $this->processNode($run, $flow, $nextNode);
    }

    private function processNode(AutomationRun $run, AutomationFlow $flow, array $node): void
    {
        $type = $node['type'] ?? 'message';

        try {
            match ($type) {
                'message' => $this->handleMessage($run, $node),
                'question' => $this->handleQuestion($run, $node),
                'delay' => $this->handleDelay($run, $flow, $node),
                'condition' => $this->handleCondition($run, $flow, $node),
                'action' => $this->handleAction($run, $node),
                default => $this->advance($run),
            };
        } catch (\Throwable $e) {
            Log::warning('automation: node processing failed', [
                'run_id' => $run->id, 'node_id' => $node['id'] ?? '?', 'error' => $e->getMessage(),
            ]);
            $run->update(['status' => AutomationRun::STATUS_FAILED, 'finished_at' => now()]);
        }
    }

    private function handleMessage(AutomationRun $run, array $node): void
    {
        // Persist the message that should be shown to the visitor.
        // The widget polls /automation/inbox endpoint to fetch pending messages.
        $context = $run->context ?? [];
        $context['_outbox'] = array_merge($context['_outbox'] ?? [], [[
            'node_id' => $node['id'],
            'type' => 'message',
            'text' => $node['config']['text'] ?? '',
            'at' => now()->toIso8601String(),
        ]]);
        $run->update(['context' => $context]);

        $this->advance($run);
    }

    private function handleQuestion(AutomationRun $run, array $node): void
    {
        // Wait for visitor input — does not auto-advance.
        $context = $run->context ?? [];
        $context['_outbox'] = array_merge($context['_outbox'] ?? [], [[
            'node_id' => $node['id'],
            'type' => 'question',
            'text' => $node['config']['text'] ?? '',
            'choices' => $node['config']['choices'] ?? [],
            'at' => now()->toIso8601String(),
        ]]);
        $run->update(['context' => $context]);
    }

    private function handleDelay(AutomationRun $run, AutomationFlow $flow, array $node): void
    {
        // For V1: synchronous (no real delay). Production version should dispatch a delayed job.
        $this->advance($run);
    }

    private function handleCondition(AutomationRun $run, AutomationFlow $flow, array $node): void
    {
        $key = $node['config']['contextKey'] ?? null;
        $expected = $node['config']['equals'] ?? null;

        $value = $key ? ($run->context[$key] ?? null) : null;
        $branch = ($value == $expected) ? 'true' : 'false';

        $nextNodeId = $flow->getNextNodeId($node['id'], $branch);
        if ($nextNodeId) {
            $next = $flow->findNode($nextNodeId);
            if ($next) {
                $run->update(['current_node_id' => $nextNodeId]);
                $this->processNode($run, $flow, $next);

                return;
            }
        }

        $run->update([
            'status' => AutomationRun::STATUS_COMPLETED,
            'finished_at' => now(),
        ]);
    }

    private function handleAction(AutomationRun $run, array $node): void
    {
        $kind = $node['config']['action'] ?? null;

        if ($kind === 'tag_customer' && $run->customer_id) {
            $context = $run->context ?? [];
            $context['_actions'] = array_merge($context['_actions'] ?? [], [['kind' => 'tag', 'value' => $node['config']['tag'] ?? null]]);
            $run->update(['context' => $context]);
        }

        $this->advance($run);
    }
}
