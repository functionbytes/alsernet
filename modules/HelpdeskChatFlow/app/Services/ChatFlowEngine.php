<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Events\ChatFlowCsatRecorded;
use Modules\HelpdeskChatFlow\Events\ChatFlowStarted;
use Modules\HelpdeskChatFlow\Jobs\HandleNodeTimeoutJob;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Concerns\FormatsNumberedOptions;
use Modules\HelpdeskChatFlow\Services\Concerns\ValidatesUserInput;

class ChatFlowEngine
{
    use FormatsNumberedOptions, ValidatesUserInput;

    private const WAIT_FOR_INPUT_TYPES = ['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat', 'rich_message'];

    // Nodes that ALWAYS pause execution. rich_message is intentionally excluded:
    // it pauses only when it has options (returns null), otherwise it continues.
    private const PAUSE_TYPES = ['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat'];

    public function __construct(
        private readonly ChatFlowNodeExecutor $executor,
        private readonly ChatFlowTriggerResolver $resolver,
        private readonly CustomerIdentityResolver $identityResolver,
        private readonly ChatFlowAiResponder $aiResponder,
        private readonly ChatFlowSentiment $sentiment,
        private readonly ChatFlowLocalizer $localizer,
        private readonly ChatFlowHandoffSummary $handoff,
    ) {}

    /**
     * Starts a new flow for a conversation.
     */
    /**
     * @param  array<string, mixed>  $context  Seed values (e.g. customer/order data for proactive outbound flows)
     */
    public function start(ChatFlow $flow, Conversation $conversation, string $triggerType, array $context = []): ?ChatFlowSession
    {
        // Serialize concurrent triggers for the same conversation (e.g. a customer
        // double-tapping on WhatsApp) so we never create two active sessions. A DB
        // unique constraint isn't viable here: MariaDB has no partial unique index
        // and a conversation legitimately has many non-active historical sessions.
        $lock = Cache::lock('chatflow-start:'.$conversation->id, 10);

        try {
            return $lock->block(3, fn () => $this->startLocked($flow, $conversation, $triggerType, $context));
        } catch (LockTimeoutException) {
            // Another request is already starting a flow for this conversation.
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function startLocked(ChatFlow $flow, Conversation $conversation, string $triggerType, array $context): ?ChatFlowSession
    {
        if ($this->hasActiveSession($conversation)) {
            return null;
        }

        // Correlation id stitched through observer → jobs → engine → dispatcher logs.
        $context['_trace_id'] = (string) Str::uuid();

        $session = ChatFlowSession::create([
            'chat_flow_id' => $flow->id,
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'trigger_type' => $triggerType,
            'context' => $context,
            'started_at' => now(),
            'last_customer_reply_at' => now(),
        ]);

        $this->applyTraceContext($session);

        // The bot is now in control: keep the conversation out of the agent inbox
        // (it stays in history) until the flow hands off to a human.
        $conversation->markHandledByBot();

        ChatFlowStarted::dispatch($session);

        $startNode = $flow->getStartNode();

        if (! $startNode) {
            $session->update(['status' => 'failed', 'ended_at' => now()]);
            // The bot couldn't run — let an agent pick it up.
            $conversation->releaseFromBot();

            return $session;
        }

        $this->runFrom($session, $startNode);

        return $session;
    }

    /**
     * Processes an incoming user message (text and/or file attachments) in an active session.
     *
     * @param  array<string>  $attachmentUrls  URLs of attached files (populated by WP/FB/email channels)
     */
    public function processMessage(ChatFlowSession $session, string $message, array $attachmentUrls = []): void
    {
        if (! $session->isActive()) {
            return;
        }

        $this->applyTraceContext($session);

        $flow = $session->chatFlow;
        $currentNode = $flow->getNodeById($session->current_node_id ?? '');

        if (! $currentNode) {
            $session->update(['status' => 'abandoned', 'ended_at' => now()]);

            return;
        }

        if (! in_array($currentNode['type'], self::WAIT_FOR_INPUT_TYPES)) {
            return;
        }

        // The customer replied to a waiting node — record activity so inactivity
        // expiry is driven by the customer going silent, not by context writes.
        $session->update(['last_customer_reply_at' => now()]);

        // Buffer every context write this message produces (lang/sentiment/input
        // capture + the downstream node run) into a single UPDATE.
        $session->withBufferedContext(fn () => $this->handleInput($session, $flow, $currentNode, $message, $attachmentUrls));
    }

    /**
     * Handle a customer reply on a waiting node: language/sentiment detection,
     * escape handling, input capture and advancing the flow.
     *
     * @param  array<string, mixed>  $currentNode
     * @param  array<string>  $attachmentUrls
     */
    private function handleInput(ChatFlowSession $session, ChatFlow $flow, array $currentNode, string $message, array $attachmentUrls): void
    {
        $conditions = $flow->trigger_conditions ?? [];

        // Detect the customer's language on first interaction (multilingual flows).
        if (($conditions['multilingual'] ?? false) && ! $session->getContextValue('customer_lang')) {
            $lang = $this->localizer->detect($message);
            if ($lang) {
                $session->setContextValue('customer_lang', $lang);
            }
        }

        // Escalate frustrated customers to a human (sentiment-aware flows), unless
        // the message is a valid option selection for the current node — a numbered
        // choice answered in a frustrated tone should still advance the flow.
        if ($conditions['sentiment_escalation'] ?? false) {
            $picksValidOption = $this->resolveNumberedChoice($message, $currentNode['data']['options'] ?? []) !== null;

            $mood = $this->sentiment->analyze($message);
            $session->setContextValue('customer_sentiment', $mood['label']);

            if (! $picksValidOption && $this->sentiment->isFrustrated($mood)) {
                $this->escalateToHuman($session, $currentNode, $conditions['sentiment_message'] ?? null);

                return;
            }
        }

        // Global escape: customer asks for a human (unless the message is a valid
        // numbered option of the current node, which takes precedence).
        if ($this->wantsHuman($flow, $currentNode, $message)) {
            $this->escalateToHuman($session, $currentNode);

            return;
        }

        if ($currentNode['type'] === 'identify_customer') {
            $this->processIdentification($session, $currentNode, $message);

            return;
        }

        if ($currentNode['type'] === 'request_documents') {
            $this->processDocumentUpload($session, $currentNode, $message, $attachmentUrls);

            return;
        }

        // Standardize: resolve a numbered reply ("1"/"2") to the option text for
        // nodes that branch by option label (quick_replies, rich_message). CSAT keeps
        // the raw number as the score.
        if (in_array($currentNode['type'], ['quick_replies', 'rich_message'], true)) {
            $options = $currentNode['data']['options'] ?? [];
            $resolved = $this->resolveNumberedChoice($message, $options);

            // NLU: no exact match + the node understands natural language → let the
            // AI classify the free-text reply to the closest option.
            if ($resolved === null && ! empty($options) && ($currentNode['data']['use_nlu'] ?? false)) {
                $index = $this->aiResponder->classifyIntent($message, $options);
                if ($index !== null) {
                    $resolved = array_values($options)[$index - 1] ?? null;
                }
            }

            // Still no match → re-prompt instead of silently falling through.
            if ($resolved === null && ! empty($options)) {
                $this->repromptInvalidOption($session, $currentNode);

                return;
            }

            if ($resolved !== null) {
                $message = $resolved;
            }
        }

        // collect_input validation (email/phone/number) → re-ask on failure.
        if ($currentNode['type'] === 'collect_input') {
            $rule = $currentNode['data']['validation'] ?? 'none';
            if ($rule !== 'none' && ! $this->passesValidation($rule, $message)) {
                $this->repromptInvalidInput($session, $currentNode, $rule);

                return;
            }
        }

        if ($currentNode['type'] === 'csat') {
            if ($this->captureCsat($session, $currentNode, $message)) {
                return; // a low score handed the conversation to a human
            }
        } else {
            $this->captureInput($session, $currentNode, $message);
        }

        $nextNodeId = $this->getNextNodeAfterInput($session, $currentNode, $message);

        if (! $nextNodeId) {
            return;
        }

        $nextNode = $flow->getNodeById($nextNodeId);

        if ($nextNode) {
            $this->runFrom($session, $nextNode);
        }
    }

    /**
     * Whether the message is a human-agent request that should escalate now.
     */
    private function wantsHuman(ChatFlow $flow, array $node, string $message): bool
    {
        $conditions = $flow->trigger_conditions ?? [];

        if (($conditions['escape_enabled'] ?? true) === false) {
            return false;
        }

        // Free-text capture nodes expect arbitrary input — names, addresses or
        // company names may legitimately contain words like "operador"/"agente".
        // Don't treat those answers as an escape request (the customer can still
        // ask for a human at any menu/option node, and timeouts still hand off).
        if (in_array($node['type'], ['collect_input', 'identify_customer'], true)) {
            return false;
        }

        // A valid option selection always wins over the escape keywords.
        if ($this->resolveNumberedChoice($message, $node['data']['options'] ?? []) !== null) {
            return false;
        }

        return $this->isHumanEscapeRequest($message, $conditions['escape_keywords'] ?? null);
    }

    private function escalateToHuman(ChatFlowSession $session, array $node, ?string $customMessage = null): void
    {
        $message = $customMessage
            ?? $session->chatFlow->trigger_conditions['escape_message']
            ?? 'Te paso con un agente. Un momento, por favor. 🙋';

        $session->conversation->items()->create([
            'type' => 'message',
            'body' => $this->localize($session, $message),
            'is_internal' => false,
            'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
        ]);

        if ($session->flowConditions()['handoff_summary'] ?? false) {
            $this->handoff->postFor($session->conversation);
        }

        // Handoff to a human: the conversation re-enters the agent inbox.
        $session->conversation?->releaseFromBot();

        $session->update(['status' => 'transferred', 'ended_at' => now()]);
        ChatFlowCompleted::dispatch($session);
    }

    private function repromptInvalidOption(ChatFlowSession $session, array $node): void
    {
        if ($this->exceededRetries($session, $node)) {
            $this->escalateToHuman($session, $node);

            return;
        }

        $options = array_values($node['data']['options'] ?? []);
        $body = "No reconocí esa opción. 🤔\n\n".$this->numberedList($options)."\n\nResponde con el número de la opción.";

        $this->sendBotMessage($session, $node, $this->localize($session, $body));
    }

    private function repromptInvalidInput(ChatFlowSession $session, array $node, string $rule): void
    {
        if ($this->exceededRetries($session, $node)) {
            $this->escalateToHuman($session, $node);

            return;
        }

        $this->sendBotMessage($session, $node, $this->localize($session, $this->validationError($rule)));
    }

    private function exceededRetries(ChatFlowSession $session, array $node): bool
    {
        $key = '_retries_'.$node['id'];
        $retries = (int) ($session->getContextValue($key) ?? 0) + 1;
        $session->setContextValue($key, $retries);

        return $retries >= (int) ($node['data']['max_retries'] ?? 3);
    }

    private function sendBotMessage(ChatFlowSession $session, array $node, string $body): void
    {
        $session->conversation->items()->create([
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
            'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
        ]);
    }

    private function processIdentification(ChatFlowSession $session, array $node, string $message): void
    {
        $data = $node['data'] ?? [];
        $sources = $data['sources'] ?? ['erp', 'ps'];
        $maxAttempts = (int) ($data['max_attempts'] ?? 3);

        $customer = $this->identityResolver->resolve($message, $sources);

        if ($customer !== null) {
            $values = ['customer_identified' => true];
            foreach ($customer as $key => $value) {
                $values['customer_'.$key] = $value;
            }
            $values['customer_name'] = $customer['name'] ?? '';
            $values['customer_email'] = $customer['email'] ?? '';
            $session->setContextValues($values);

            if (! empty($data['found_message'])) {
                $text = preg_replace_callback('/\{\{(\w+)\}\}/', fn ($m) => $session->getContextValue($m[1], $m[0]), $data['found_message']);
                $session->conversation->items()->create([
                    'type' => 'message',
                    'body' => $text,
                    'is_internal' => false,
                    'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
                ]);
            }

            // Continue linearly — designer places branches node next if found/not_found split needed
            $nextNodeId = $this->getFirstChildId($session, $node['id']);

            if ($nextNodeId) {
                $nextNode = $session->chatFlow->getNodeById($nextNodeId);
                if ($nextNode) {
                    $this->runFrom($session, $nextNode);
                }
            }

            return;
        }

        $attempts = (int) ($session->getContextValue('_identify_attempts_'.$node['id']) ?? 0) + 1;
        $session->setContextValue('_identify_attempts_'.$node['id'], $attempts);

        if ($attempts >= $maxAttempts) {
            $this->handleIdentificationFailure($session, $node, $data);

            return;
        }

        $notFoundMsg = $data['not_found_message'] ?? 'No encontramos ningún cliente con ese dato. Intenta con tu email, teléfono o número de documento de identidad.';
        $session->conversation->items()->create([
            'type' => 'message',
            'body' => $notFoundMsg,
            'is_internal' => false,
            'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
        ]);
    }

    /**
     * Handles document uploads in a request_documents node.
     *
     * Channels that support file attachments (WP, FB, email) pass $attachmentUrls.
     * Text-only interaction: customer types the number of the doc they want to send,
     * and then sends a subsequent message with the file (handled in the next call).
     *
     * Logic:
     * - If $attachmentUrls not empty → assign first URL to the next pending doc type
     * - If $message is a number matching a pending doc → ask customer to send the file
     * - When all docs are collected → advance to next node
     */
    private function processDocumentUpload(ChatFlowSession $session, array $node, string $message, array $attachmentUrls): void
    {
        $data = $node['data'] ?? [];
        $required = $data['doc_types'] ?? [];
        $uploadKey = '_doc_uploads_'.$node['id'];
        $uploaded = $session->getContextValue($uploadKey) ?? [];
        $pending = array_values(array_diff($required, array_keys($uploaded)));

        $docLabels = [
            'dni_frontal' => 'DNI/NIE frontal',
            'dni_trasera' => 'DNI/NIE trasera',
            'pasaporte' => 'Pasaporte',
            'contrato' => 'Contrato firmado',
            'factura' => 'Factura de compra',
            'foto_producto' => 'Foto del producto',
            'proforma' => 'Factura proforma',
            'iban' => 'Certificado bancario / IBAN',
            'selfie' => 'Selfie con documento',
            'recibo' => 'Recibo',
        ];

        if (! empty($attachmentUrls)) {
            // File arrived — assign to pending doc that was announced (or first pending)
            $announcedKey = $session->getContextValue('_announced_doc_'.$node['id']);
            $docKey = ($announcedKey && in_array($announcedKey, $pending))
                ? $announcedKey
                : ($pending[0] ?? null);

            if ($docKey) {
                $uploaded[$docKey] = $attachmentUrls[0];
                $session->setContextValue($uploadKey, $uploaded);
                $session->setContextValue('_announced_doc_'.$node['id'], null);

                $label = $docLabels[$docKey] ?? $docKey;
                $session->conversation->items()->create([
                    'type' => 'message',
                    'body' => "✅ {$label} recibido. Gracias.",
                    'is_internal' => false,
                    'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
                ]);

                $pending = array_values(array_diff($required, array_keys($uploaded)));
            }
        } elseif (is_numeric(trim($message))) {
            // Customer typed a number to select which doc to send next
            $idx = (int) trim($message) - 1;
            $docKey = $pending[$idx] ?? null;

            if ($docKey) {
                $session->setContextValue('_announced_doc_'.$node['id'], $docKey);
                $label = $docLabels[$docKey] ?? $docKey;
                $session->conversation->items()->create([
                    'type' => 'message',
                    'body' => $this->localize($session, "Entendido. Por favor envía el archivo para **{$label}**."),
                    'is_internal' => false,
                    'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
                ]);

                return; // Stay on this node waiting for the file
            }

            // Out-of-range number → tell the customer instead of going silent.
            $session->conversation->items()->create([
                'type' => 'message',
                'body' => $this->localize($session, 'Ese número no corresponde a ningún documento pendiente. Escribe el número de uno de la lista.'),
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);

            return;
        }

        if (empty($pending)) {
            // All documents received — save list and continue
            $varName = $data['variable_name'] ?? 'uploaded_docs';
            $session->setContextValue($varName, array_keys($uploaded));

            $doneMsg = $data['done_message'] ?? '📂 ¡Todos los documentos recibidos! Continuamos.';
            $session->conversation->items()->create([
                'type' => 'message',
                'body' => $doneMsg,
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);

            $nextNodeId = $this->getFirstChildId($session, $node['id']);
            $nextNode = $nextNodeId ? $session->chatFlow->getNodeById($nextNodeId) : null;

            if ($nextNode) {
                $this->runFrom($session, $nextNode);
            }
        } else {
            // Still pending — re-send the list of remaining docs
            $list = implode("\n", array_map(
                fn ($k, $t) => ($k + 1).'. '.($docLabels[$t] ?? $t),
                array_keys($pending),
                $pending
            ));
            $session->conversation->items()->create([
                'type' => 'message',
                'body' => "Aún faltan los siguientes documentos:\n{$list}\n\nEscribe el número del documento y envía el archivo.",
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);
        }
    }

    private function handleIdentificationFailure(ChatFlowSession $session, array $node, array $data): void
    {
        $session->setContextValue('customer_identified', false);

        // Look for an else branchItem sibling if the next node is a branches container
        $nextNodeId = $this->getFirstChildId($session, $node['id']);
        $nextNode = $nextNodeId ? $session->chatFlow->getNodeById($nextNodeId) : null;

        if ($nextNode && $nextNode['type'] === 'branches') {
            $elseBranchItem = collect($session->chatFlow->getBranchItems($nextNode['id']))
                ->first(fn ($b) => $b['data']['isElse'] ?? false);

            if ($elseBranchItem) {
                $afterElse = $this->getFirstChildId($session, $elseBranchItem['id']);
                $afterNode = $afterElse ? $session->chatFlow->getNodeById($afterElse) : null;
                if ($afterNode) {
                    $this->runFrom($session, $afterNode);

                    return;
                }
            }
        }

        if ($data['transfer_on_failure'] ?? true) {
            $session->conversation?->releaseFromBot();
            $session->update(['status' => 'transferred', 'ended_at' => now()]);
            ChatFlowCompleted::dispatch($session);
        }
    }

    /**
     * Schedule a timeout job for a waiting node so the bot reacts if the customer
     * goes quiet. No-op unless the node is a wait node with timeout_minutes set.
     */
    private function scheduleTimeout(ChatFlowSession $session, array $node): void
    {
        $minutes = (int) ($node['data']['timeout_minutes'] ?? 0);

        if ($minutes < 1 || ! in_array($node['type'], self::PAUSE_TYPES, true)) {
            return;
        }

        $lastItemId = (int) ($session->conversation?->items()->max('id') ?? 0);

        HandleNodeTimeoutJob::dispatch($session->id, $node['id'], $lastItemId)
            ->delay(now()->addMinutes($minutes));
    }

    /**
     * Run a waiting node's timeout branch: optional message, then re-ask (bounded
     * by timeout_retries), transfer to a human, or close the session.
     */
    public function handleNodeTimeout(ChatFlowSession $session, array $node): void
    {
        $this->applyTraceContext($session);

        $data = $node['data'] ?? [];

        if (! empty($data['timeout_message'])) {
            $session->conversation?->items()->create([
                'type' => 'message',
                'body' => $data['timeout_message'],
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id'], 'timeout' => true],
            ]);
        }

        $action = $data['timeout_action'] ?? 'close';

        if ($action === 'transfer') {
            $session->conversation?->releaseFromBot();
            $session->update(['status' => 'transferred', 'ended_at' => now()]);
            ChatFlowCompleted::dispatch($session);

            return;
        }

        if ($action === 'retry') {
            $maxRetries = max(1, (int) ($data['timeout_retries'] ?? 1));
            $key = '_timeout_retries_'.$node['id'];
            $used = (int) $session->getContextValue($key, 0);

            if ($used < $maxRetries) {
                $session->setContextValue($key, $used + 1);
                $this->runFrom($session, $node); // re-ask + reschedule timeout

                return;
            }
        }

        // Default / retries exhausted → end the session and return the conversation
        // to the inbox so a human can follow up (otherwise it stays handled_by_bot
        // and invisible to agents forever).
        $session->conversation?->releaseFromBot();
        $session->update(['status' => 'abandoned', 'ended_at' => now()]);
        ChatFlowCompleted::dispatch($session);
    }

    /**
     * Supervisor intervention: stop the active bot session and hand the
     * conversation to the given agent (it re-enters the inbox immediately).
     */
    public function takeOver(Conversation $conversation, int $agentId): bool
    {
        $session = $this->getActiveSession($conversation);

        if ($session) {
            $session->update(['status' => 'transferred', 'ended_at' => now()]);
        }

        $conversation->releaseFromBot();
        $conversation->assignTo($agentId);

        return true;
    }

    /**
     * Finds and starts the right flow for a conversation based on trigger and context.
     */
    public function triggerFor(Conversation $conversation, string $triggerType, array $context = []): ?ChatFlowSession
    {
        $flow = $this->resolver->resolve($conversation, $triggerType, $context);

        if (! $flow) {
            return null;
        }

        return $this->start($this->pickAbVariant($flow), $conversation, $triggerType);
    }

    /**
     * A/B testing: if the flow has a configured variant and an even split, send
     * half of new conversations to the variant so their analytics can be compared.
     */
    public function pickAbVariant(ChatFlow $flow): ChatFlow
    {
        $variantId = $flow->trigger_conditions['ab_variant_id'] ?? null;

        if (! $variantId || random_int(1, 100) > (int) ($flow->trigger_conditions['ab_split'] ?? 50)) {
            return $flow;
        }

        return ChatFlow::query()->find($variantId) ?? $flow;
    }

    public function hasActiveSession(Conversation $conversation): bool
    {
        return ChatFlowSession::query()
            ->where('conversation_id', $conversation->id)
            ->where('status', 'active')
            ->exists();
    }

    public function getActiveSession(Conversation $conversation): ?ChatFlowSession
    {
        return ChatFlowSession::query()
            ->where('conversation_id', $conversation->id)
            ->where('status', 'active')
            ->with('chatFlow')
            ->latest()
            ->first();
    }

    private function runFrom(ChatFlowSession $session, array $node): void
    {
        // Buffer context writes for the whole run: a single message can touch
        // ~11 context keys across nodes; without buffering each is its own
        // UPDATE. Re-entrant, so nesting under processMessage's buffer is safe.
        $session->withBufferedContext(fn () => $this->runNodes($session, $node));
    }

    private function runNodes(ChatFlowSession $session, array $node): void
    {
        $maxDepth = 50;
        $depth = 0;

        // Index the node tree once: the loop resolves the next node O(1) instead of
        // re-scanning the JSON array (and re-querying via refresh()) on every hop.
        $nodesById = collect($session->chatFlow->runtimeNodes())->keyBy('id');

        while ($node && $depth < $maxDepth) {
            $session->update(['current_node_id' => $node['id']]);

            try {
                $nextNodeId = $this->executeNode($session, $node);
            } catch (\Throwable $e) {
                Log::error('ChatFlow node execution failed', [
                    'session_id' => $session->id,
                    'node_id' => $node['id'],
                    'error' => $e->getMessage(),
                ]);
                $this->failSession($session);

                return;
            }

            if (in_array($node['type'], self::PAUSE_TYPES) || $nextNodeId === null) {
                $this->finalizeIfNeeded($session, $node, $nextNodeId);
                $this->scheduleTimeout($session, $node);

                return;
            }

            $node = $nodesById[$nextNodeId] ?? null;

            // The resolved next id points to a node that no longer exists (deleted
            // target, typo in go_to_step). Don't strand the session as an active
            // zombie that ignores every reply — end it and release to the inbox.
            if ($node === null) {
                Log::warning('ChatFlow next node not found', [
                    'session_id' => $session->id,
                    'next_node_id' => $nextNodeId,
                ]);
                $this->failSession($session);

                return;
            }

            $depth++;
        }

        if ($depth >= $maxDepth) {
            Log::warning('ChatFlow max depth reached', ['session_id' => $session->id]);
            $this->failSession($session);
        }
    }

    /**
     * Marks a session failed and returns the conversation to the agent inbox, so a
     * bot breakdown never leaves a customer stranded with no human follow-up.
     */
    private function failSession(ChatFlowSession $session): void
    {
        $session->update(['status' => 'failed', 'ended_at' => now()]);
        $session->conversation?->releaseFromBot();
        ChatFlowCompleted::dispatch($session);
    }

    /**
     * Executes a node. For `branches`, evaluates conditions and routes to the winning branchItem's children.
     */
    private function executeNode(ChatFlowSession $session, array $node): ?string
    {
        if ($node['type'] === 'branches') {
            return $this->executeBranches($session, $node);
        }

        return $this->executor->execute($node, $session);
    }

    private function executeBranches(ChatFlowSession $session, array $node): ?string
    {
        $branchItems = $session->chatFlow->getBranchItems($node['id']);
        $elseBranchItem = null;

        foreach ($branchItems as $branchItem) {
            if ($branchItem['data']['isElse'] ?? false) {
                $elseBranchItem = $branchItem;

                continue;
            }

            if ($this->evaluateBranchItem($branchItem, $session)) {
                return $this->getFirstChildId($session, $branchItem['id']);
            }
        }

        // Fall through to else branch if present
        if ($elseBranchItem) {
            return $this->getFirstChildId($session, $elseBranchItem['id']);
        }

        return null;
    }

    private function evaluateBranchItem(array $branchItem, ChatFlowSession $session): bool
    {
        foreach ($branchItem['data']['conditions'] ?? [] as $cond) {
            $actual = $session->getContextValue($cond['variable'] ?? '');
            $value = $cond['value'] ?? '';

            $matches = match ($cond['operator'] ?? '=') {
                '=' => (string) $actual === (string) $value,
                '!=' => (string) $actual !== (string) $value,
                '>' => (float) $actual > (float) $value,
                '<' => (float) $actual < (float) $value,
                'contains' => str_contains((string) $actual, (string) $value),
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    private function finalizeIfNeeded(ChatFlowSession $session, array $node, ?string $nextNodeId): void
    {
        if ($session->status !== 'active') {
            ChatFlowCompleted::dispatch($session);

            return;
        }

        // Wait-for-input nodes pause execution — session stays active
        if (in_array($node['type'], self::WAIT_FOR_INPUT_TYPES)) {
            return;
        }

        if ($node['type'] !== 'end' && $nextNodeId === null) {
            $session->update(['status' => 'completed', 'ended_at' => now()]);
            ChatFlowCompleted::dispatch($session);
        }
    }

    private function captureInput(ChatFlowSession $session, array $node, string $message): void
    {
        $variableName = $node['data']['variable_name'] ?? 'last_input';
        $session->setContextValue($variableName, $message);
    }

    /**
     * Capture a CSAT answer. Returns true when a low score escalated the
     * conversation to a human (service recovery), so the caller stops the flow.
     */
    private function captureCsat(ChatFlowSession $session, array $node, string $message): bool
    {
        $data = $node['data'] ?? [];
        $variableName = $data['variable_name'] ?? 'csat_score';
        $score = is_numeric(trim($message)) ? (int) trim($message) : $message;

        $session->setContextValues([$variableName => $score, 'csat_score' => $score]);

        // Persist the score on the conversation and announce it so agents, CRM and
        // reporting can react — not just the analytics widget.
        if (is_numeric($score)) {
            $this->recordCsat($session, (int) $score);
        }

        // Service recovery: a low score can hand the conversation to a human.
        if (is_numeric($score) && $this->isLowCsat($data, (int) $score)) {
            $this->escalateToHuman($session, $node, $data['csat_low_message'] ?? null);

            return true;
        }

        if (! empty($data['thanks_message'])) {
            $text = $this->localize($session, $this->interpolate($data['thanks_message'], $session));
            $session->conversation->items()->create([
                'type' => 'message',
                'body' => $text,
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);
        }

        return false;
    }

    /**
     * Persist the CSAT score on the conversation metadata and broadcast the event.
     */
    private function recordCsat(ChatFlowSession $session, int $score): void
    {
        $conversation = $session->conversation;

        if ($conversation) {
            $conversation->update([
                'metadata' => array_merge($conversation->metadata ?? [], ['csat_score' => $score]),
            ]);
        }

        ChatFlowCsatRecorded::dispatch($session, $score);
    }

    /**
     * @param  array<string, mixed>  $data  CSAT node data
     */
    private function isLowCsat(array $data, int $score): bool
    {
        if (($data['csat_low_action'] ?? 'none') !== 'escalate') {
            return false;
        }

        $threshold = (int) ($data['csat_low_threshold'] ?? 0);

        return $threshold > 0 && $score <= $threshold;
    }

    private function interpolate(string $text, ChatFlowSession $session): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', fn ($m) => $session->getContextValue($m[1], $m[0]), $text);
    }

    /**
     * Translate a fixed customer-facing string into the customer's detected
     * language (multilingual flows); a no-op when no language was detected.
     */
    private function localize(ChatFlowSession $session, string $text): string
    {
        return $this->localizer->localize($text, $session->getContextValue('customer_lang'));
    }

    /**
     * Stamp the session's trace id + id onto the logging context so every log line
     * for this session (across the observer, jobs, engine and dispatcher) can be
     * correlated. Purely additive — no behavioural effect.
     */
    private function applyTraceContext(ChatFlowSession $session): void
    {
        Log::withContext([
            'chatflow_trace' => $session->getContextValue('_trace_id'),
            'chatflow_session' => $session->id,
        ]);
    }

    private function getNextNodeAfterInput(ChatFlowSession $session, array $node, string $message): ?string
    {
        if (in_array($node['type'], ['quick_replies', 'rich_message'], true)) {
            // Match option label to a child node's label; fall back to first child
            foreach ($session->chatFlow->childrenByParent()[$node['id']] ?? [] as $child) {
                if (($child['label'] ?? '') === $message) {
                    return $child['id'];
                }
            }
        }

        return $this->getFirstChildId($session, $node['id']);
    }

    private function getFirstChildId(ChatFlowSession $session, string $parentId): ?string
    {
        return $session->chatFlow->childrenByParent()[$parentId][0]['id'] ?? null;
    }
}
