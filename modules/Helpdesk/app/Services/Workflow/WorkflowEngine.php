<?php

namespace Modules\Helpdesk\Services\Workflow;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\RunWorkflowJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Workflow;
use Modules\Helpdesk\Models\WorkflowRun;
use Modules\Helpdesk\Support\OutboundUrlGuard;

class WorkflowEngine
{
    /**
     * Find active workflows matching the trigger and dispatch one job per match.
     */
    public function executeForTrigger(string $trigger, array $context): void
    {
        $workflows = Workflow::active()->forTrigger($trigger)->get();

        foreach ($workflows as $workflow) {
            if (! $this->matchesTriggerConfig($workflow, $context)) {
                continue;
            }

            $firstNode = collect($workflow->nodes ?? [])->first();

            $run = WorkflowRun::create([
                'workflow_id' => $workflow->id,
                'conversation_id' => $context['conversation_id'] ?? null,
                'customer_id' => $context['customer_id'] ?? null,
                'status' => WorkflowRun::STATUS_RUNNING,
                'current_node_id' => $firstNode['id'] ?? null,
                'context' => $context,
                'started_at' => now(),
            ]);

            RunWorkflowJob::dispatch($run);
        }
    }

    /**
     * Iterate workflow nodes, executing each in order.
     */
    public function runWorkflow(WorkflowRun $run): void
    {
        $workflow = $run->workflow;

        if (! $workflow) {
            $run->markFailed('Workflow not found');

            return;
        }

        try {
            $nodeId = $run->current_node_id;
            $context = $run->context ?? [];

            while ($nodeId) {
                $node = $workflow->getNodeById($nodeId);

                if (! $node) {
                    break;
                }

                $run->update(['current_node_id' => $nodeId]);

                $result = $this->executeNode($node, $run, $context);

                if ($result === null || ($node['type'] ?? '') === 'end') {
                    break;
                }

                // For wait nodes the job re-dispatches itself with delay
                if (($node['type'] ?? '') === 'wait') {
                    return;
                }

                $nodeId = $result;
            }

            $run->markCompleted();
            $workflow->incrementRun();
        } catch (\Throwable $e) {
            Log::error('WorkflowEngine: run failed', [
                'run_id' => $run->id,
                'workflow_id' => $run->workflow_id,
                'error' => $e->getMessage(),
            ]);

            $run->markFailed($e->getMessage());
        }
    }

    /**
     * Execute a single node and return the next node id (or null to stop).
     */
    protected function executeNode(array $node, WorkflowRun $run, array &$context): ?string
    {
        $type = $node['type'] ?? 'end';
        $config = $node['config'] ?? [];

        return match ($type) {
            'condition' => $this->executeCondition($node, $run, $context),
            'action' => $this->executeAction($node, $run, $context),
            'wait' => $this->executeWait($node, $run),
            'branch' => $node['config']['branches'][0]['next'] ?? null,
            'end' => null,
            default => $node['next'] ?? null,
        };
    }

    protected function executeCondition(array $node, WorkflowRun $run, array $context): ?string
    {
        $config = $node['config'] ?? [];
        $field = $config['field'] ?? null;
        $operator = $config['operator'] ?? 'equals';
        $value = $config['value'] ?? null;

        $actual = $context[$field] ?? null;

        $matches = match ($operator) {
            'equals' => $actual == $value,
            'not_equals' => $actual != $value,
            'contains' => str_contains((string) $actual, (string) $value),
            'is_empty' => empty($actual),
            'is_not_empty' => ! empty($actual),
            default => false,
        };

        return $matches
            ? ($node['config']['true_next'] ?? $node['next'] ?? null)
            : ($node['config']['false_next'] ?? null);
    }

    protected function executeAction(array $node, WorkflowRun $run, array $context): ?string
    {
        $config = $node['config'] ?? [];
        $actionType = $config['action'] ?? null;

        $conversation = $run->conversation_id
            ? Conversation::find($run->conversation_id)
            : null;

        match ($actionType) {
            'send_text' => $this->actionSendText($conversation, $config),
            'set_status' => $this->actionSetStatus($conversation, $config),
            'set_priority' => $this->actionSetPriority($conversation, $config),
            'assign_user' => $this->actionAssignUser($conversation, $config),
            'add_tag' => $this->actionAddTag($conversation, $config),
            'http_request' => $this->actionHttpRequest($config, $context),
            default => null,
        };

        return $node['next'] ?? null;
    }

    protected function executeWait(array $node, WorkflowRun $run): ?string
    {
        $minutes = (int) ($node['config']['minutes'] ?? 60);

        RunWorkflowJob::dispatch($run)->delay(now()->addMinutes($minutes));

        return null; // Signal to stop current execution; job re-runs later
    }

    // ── Action implementations ────────────────────────────────────────

    protected function actionSendText(?Conversation $conversation, array $config): void
    {
        if (! $conversation) {
            return;
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => $config['text'] ?? '',
            'direction' => 'outbound',
            'user_id' => null,
        ]);
    }

    protected function actionSetStatus(?Conversation $conversation, array $config): void
    {
        if (! $conversation || empty($config['status_id'])) {
            return;
        }

        $conversation->update(['status_id' => (int) $config['status_id']]);
    }

    protected function actionSetPriority(?Conversation $conversation, array $config): void
    {
        if (! $conversation || empty($config['priority'])) {
            return;
        }

        $conversation->update(['priority' => $config['priority']]);
    }

    protected function actionAssignUser(?Conversation $conversation, array $config): void
    {
        if (! $conversation || empty($config['user_id'])) {
            return;
        }

        $conversation->assignTo((int) $config['user_id']);
    }

    protected function actionAddTag(?Conversation $conversation, array $config): void
    {
        if (! $conversation || empty($config['tag_id'])) {
            return;
        }

        $conversation->conversationTags()->syncWithoutDetaching([(int) $config['tag_id']]);
    }

    protected function actionHttpRequest(array $config, array $context): void
    {
        $url = $config['url'] ?? null;

        if (! $url) {
            return;
        }

        if (! OutboundUrlGuard::isSafe((string) $url)) {
            Log::warning('WorkflowEngine: blocked unsafe http_request URL (SSRF guard)', ['url' => $url]);

            return;
        }

        $method = strtolower($config['method'] ?? 'post');
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        if (! in_array($method, $allowedMethods, true)) {
            $method = 'post';
        }

        $headers = $config['headers'] ?? [];

        // timeout/connectTimeout: la URL la define el usuario y esto corre en un
        // listener encolado; sin límite, un endpoint del cliente colgado bloquea
        // el worker. Mismo criterio que DispatchWebhookJob/DispatchDirectWebhookJob.
        Http::withHeaders($headers)->timeout(10)->connectTimeout(5)->{$method}($url, $context);
    }

    protected function matchesTriggerConfig(Workflow $workflow, array $context): bool
    {
        $cfg = $workflow->trigger_config ?? [];

        // Channel filter
        if (! empty($cfg['channel']) && ($context['channel'] ?? null) !== $cfg['channel']) {
            return false;
        }

        // Tag filter
        if (! empty($cfg['tag_id']) && ($context['tag_id'] ?? null) != $cfg['tag_id']) {
            return false;
        }

        return true;
    }
}
