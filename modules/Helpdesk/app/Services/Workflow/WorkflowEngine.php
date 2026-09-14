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

    /**
     * Estas cuatro leían una clave que ningún nodo guardado usa nunca.
     *
     * El editor de workflows (y los tres workflows reales que hay en la BD:
     * "Bienvenida y etiquetado automatico", "Escalado por incumplimiento de
     * SLA", "Encuesta de satisfaccion tras cierre") guarda cada acción como
     * {"action": "...", "value": "..."} — una única clave `value` para
     * cualquier tipo de acción. Estos cuatro métodos, en cambio, buscaban
     * `text`/`status_id`/`priority`/`user_id`, que no existen en ningún nodo
     * real: `empty($config['status_id'])` siempre era true y el nodo no hacía
     * nada, y `$config['text'] ?? ''` siempre daba cadena vacía — un mensaje
     * "enviado" en blanco. Ejecutar cualquier workflow con estas acciones era
     * un no-op silencioso pase lo que pase (verificado 8-sep-2026 con un
     * WorkflowRun real que terminó `completed` sin producir efecto).
     *
     * Se lee `value` — la única clave que la data real usa — con la
     * específica como fallback por si algún nodo antiguo la trajera.
     */
    protected function actionSendText(?Conversation $conversation, array $config): void
    {
        if (! $conversation) {
            return;
        }

        $text = $config['value'] ?? $config['text'] ?? '';

        if ($text === '') {
            return;
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => $text,
            'direction' => 'outbound',
            'user_id' => null,
        ]);
    }

    protected function actionSetStatus(?Conversation $conversation, array $config): void
    {
        $statusId = $config['value'] ?? $config['status_id'] ?? null;

        if (! $conversation || empty($statusId)) {
            return;
        }

        $conversation->update(['status_id' => (int) $statusId]);
    }

    protected function actionSetPriority(?Conversation $conversation, array $config): void
    {
        $priority = $config['value'] ?? $config['priority'] ?? null;

        if (! $conversation || empty($priority)) {
            return;
        }

        $conversation->update(['priority' => $priority]);
    }

    protected function actionAssignUser(?Conversation $conversation, array $config): void
    {
        $userId = $config['value'] ?? $config['user_id'] ?? null;

        if (! $conversation || empty($userId)) {
            return;
        }

        $conversation->assignTo((int) $userId);
    }

    /**
     * `add_tag` se queda como estaba, deliberadamente: los nodos guardan
     * `value` como un NOMBRE de etiqueta ("nuevo-contacto"), pero esta acción
     * espera un `tag_id` numérico de una fila ya existente en
     * helpdesk_conversation_tags — y esa etiqueta concreta no existe (18
     * etiquetas en la tabla, ninguna es "nuevo-contacto"). Arreglar el mismo
     * alias que las cuatro de arriba no basta aquí: haría falta decidir si
     * se crea la etiqueta sobre la marcha (¿con qué color?, ¿visible en el
     * selector?) o si se exige un tag_id real desde el editor — una decisión
     * de producto, no un alias de clave. Ver reference_erp_lookup_on_inbound_email.
     */
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
