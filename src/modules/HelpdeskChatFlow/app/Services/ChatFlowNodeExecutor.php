<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Concerns\EvaluatesBusinessHours;
use Modules\HelpdeskChatFlow\Services\Concerns\FormatsNumberedOptions;

class ChatFlowNodeExecutor
{
    use EvaluatesBusinessHours, FormatsNumberedOptions;

    public function __construct(
        private readonly ChatFlowAiResponder $aiResponder,
        private readonly ChatFlowOrderLookup $orderLookup,
        private readonly ChatFlowHttpRequester $httpRequester,
        private readonly ChatFlowLocalizer $localizer,
        private readonly ChatFlowAgentService $agent,
        private readonly ChatFlowHandoffSummary $handoff,
        private readonly ChatFlowDocumentLink $documentLink,
    ) {}

    /**
     * Routing-only nodes that produce no customer-visible side effect: logging an
     * execution row for them is pure write overhead with no analytics value.
     */
    private const SKIP_LOGGING_TYPES = ['start', 'branchItem', 'delay', 'go_to_step'];

    /**
     * Executes a node and returns the next node ID, or null if execution should pause/end.
     */
    public function execute(array $node, ChatFlowSession $session): ?string
    {
        if (in_array($node['type'], self::SKIP_LOGGING_TYPES, true)) {
            return $this->executeNode($node, $session);
        }

        // Snapshot the pre-execution context so the logged row reflects the input
        // state even though the node may mutate context while running.
        $inputContext = $session->context;
        $startedAt = microtime(true);

        try {
            $nextNodeId = $this->executeNode($node, $session);
        } catch (\Throwable $e) {
            $this->logExecution($node, $session, 'failed', $startedAt, $inputContext, ['error_message' => $e->getMessage()]);

            throw $e;
        }

        // Single write (no pending → success UPDATE) keeps the hot path to one INSERT per node.
        $this->logExecution($node, $session, 'success', $startedAt, $inputContext, ['output' => ['next_node_id' => $nextNodeId]]);

        return $nextNodeId;
    }

    /**
     * @param  array<string,mixed>|null  $inputContext
     * @param  array<string,mixed>  $extra
     */
    private function logExecution(array $node, ChatFlowSession $session, string $status, float $startedAt, ?array $inputContext, array $extra = []): void
    {
        ChatFlowExecution::create(array_merge([
            'session_id' => $session->id,
            'node_id' => $node['id'],
            'node_type' => $node['type'],
            'input' => ['context' => $this->pruneContextSnapshot($inputContext)],
            'status' => $status,
            'executed_at' => now(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ], $extra));
    }

    /**
     * Bound the per-node context snapshot stored in the execution audit row.
     * Context grows with every node (AI answers, HTTP bodies, order payloads),
     * so logging the full blob on each node stores ever-larger copies — quadratic
     * audit-table growth for no analytics value. Long string values are truncated
     * so the debug trail keeps the keys/shape without the bloat.
     *
     * @param  array<string,mixed>|null  $context
     * @return array<string,mixed>|null
     */
    private function pruneContextSnapshot(?array $context): ?array
    {
        if ($context === null) {
            return null;
        }

        $maxLength = 500;

        foreach ($context as $key => $value) {
            if (is_string($value) && mb_strlen($value) > $maxLength) {
                $context[$key] = mb_substr($value, 0, $maxLength).'… [truncated]';
            }
        }

        return $context;
    }

    private const DOC_LABELS = [
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

    private function executeNode(array $node, ChatFlowSession $session): ?string
    {
        $conversation = $session->conversation;

        return match ($node['type']) {
            'start' => $this->getFirstChildId($node, $session),
            'message' => $this->executeMessage($node, $session, $conversation),
            'quick_replies' => $this->executeQuickReplies($node, $session, $conversation),
            'collect_input' => $this->executeCollectInput($node, $session, $conversation),
            'identify_customer' => $this->executeIdentifyCustomer($node, $session, $conversation),
            'request_documents' => $this->executeRequestDocuments($node, $session, $conversation),
            'branches' => null,
            'branchItem' => $this->getFirstChildId($node, $session),
            'action' => $this->executeAction($node, $session, $conversation),
            'delay' => $this->getFirstChildId($node, $session),
            'add_tag' => $this->executeAddTag($node, $session, $conversation),
            'set_attribute' => $this->executeSetAttribute($node, $session, $conversation),
            'go_to_step' => $node['data']['target_node_id'] ?? null,
            'ai_response' => $this->executeAiResponse($node, $session, $conversation),
            'ai_agent' => $this->executeAiAgent($node, $session, $conversation),
            'order_lookup' => $this->executeOrderLookup($node, $session, $conversation),
            'http_request' => $this->executeHttpRequest($node, $session, $conversation),
            'csat' => $this->executeCsat($node, $session, $conversation),
            'business_hours' => $this->executeBusinessHours($node, $session),
            'rich_message' => $this->executeRichMessage($node, $session, $conversation),
            'send_file' => $this->executeSendFile($node, $session, $conversation),
            'document_link' => $this->executeDocumentLink($node, $session, $conversation),
            'transfer' => $this->executeTransfer($node, $session, $conversation),
            'close' => $this->executeClose($node, $session, $conversation),
            'end' => $this->executeEnd($node, $session),
            default => null,
        };
    }

    private function executeRequestDocuments(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $docTypes = $data['doc_types'] ?? [];
        $intro = $this->localizeForCustomer(
            $data['intro_message'] ?? 'Necesitamos los siguientes documentos. Escribe el número del documento y adjunta el archivo:',
            $session,
        );

        $list = implode("\n", array_map(
            fn ($i, $key) => ($i + 1).'. '.(self::DOC_LABELS[$key] ?? $key),
            array_keys($docTypes),
            $docTypes,
        ));

        $conversation->items()->create([
            'type' => 'message',
            'body' => "{$intro}\n{$list}",
            'is_internal' => false,
            'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
        ]);

        return null; // Pause — wait for customer to select and upload documents
    }

    private function executeCollectInput(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $question = $this->interpolateContext($node['data']['question'] ?? '', $session->context ?? []);

        if ($question !== '') {
            $conversation->items()->create([
                'type' => 'message',
                'body' => $question,
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);
        }

        return null; // Pause — wait for the customer's reply
    }

    private function executeIdentifyCustomer(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $question = $this->localizeForCustomer(
            $data['question'] ?? 'Para identificarte, escribe tu email, teléfono o número de documento.',
            $session,
        );

        $conversation->items()->create([
            'type' => 'message',
            'body' => $question,
            'is_internal' => false,
            'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
        ]);

        return null; // Pause — wait for customer reply
    }

    private function executeMessage(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $text = $node['data']['text'] ?? '';

        $text = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($session) {
            return $session->getContextValue($matches[1], $matches[0]);
        }, $text);

        $text = $this->localizeForCustomer($text, $session);

        $metadata = ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']];

        // Optional WhatsApp HSM template: used by the dispatcher when sending a
        // proactive (outbound) message outside the 24h session window.
        if (! empty($node['data']['whatsapp_template'])) {
            $metadata['whatsapp_template'] = (string) $node['data']['whatsapp_template'];
            $metadata['template_vars'] = array_map(
                fn ($v) => $this->interpolateContext((string) $v, $session->context ?? []),
                array_values($node['data']['template_vars'] ?? []),
            );
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => $text,
            'is_internal' => false,
            'metadata' => $metadata,
        ]);

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Translate a fixed bot message into the customer's language, if detected.
     */
    private function localizeForCustomer(string $text, ChatFlowSession $session): string
    {
        return $this->localizer->localize($text, $session->getContextValue('customer_lang'));
    }

    private function executeQuickReplies(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $options = array_values($data['options'] ?? []);

        $header = $this->localizeForCustomer(
            $this->interpolateContext($data['text'] ?? 'Selecciona una opción:', $session->context ?? []),
            $session,
        );

        $conversation->items()->create([
            'type' => 'message',
            'body' => $this->numberedPrompt($header, $options),
            'is_internal' => false,
            'metadata' => [
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'bot_options' => $options, // delivered as native buttons where the channel supports them
                'bot_prompt' => $header,
            ],
        ]);

        return null; // wait for user selection
    }

    private function executeAction(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];

        match ($data['action_type'] ?? '') {
            'assign_agent' => $conversation->update(['assignee_id' => $data['agent_id'] ?? null]),
            'change_status' => $conversation->update(['status_id' => $data['status_id'] ?? null]),
            default => null,
        };

        return $this->getFirstChildId($node, $session);
    }

    private function executeAiResponse(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $questionVar = $data['question_variable'] ?? 'last_input';
        $question = (string) ($session->getContextValue($questionVar) ?? '');
        // Customer language drives the AI's reply language (LLM-native, no translation layer).
        $locale = (string) ($session->getContextValue('customer_lang')
            ?? $conversation->locale
            ?? config('app.locale', 'es'));

        $history = ($data['use_memory'] ?? true)
            ? $this->conversationHistory($conversation)
            : [];

        $result = $this->aiResponder->generate($question, $data, $locale, $history);

        $conversation->items()->create([
            'type' => 'message',
            'body' => $result['answer'],
            'is_internal' => false,
            'metadata' => [
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'ai_generated' => true,
                'ai_used_kb' => $result['used_kb'],
                'ai_sources' => $result['sources'],
            ],
        ]);

        $values = ['ai_used_kb' => $result['used_kb']];
        if (! empty($data['save_to'])) {
            $values[$data['save_to']] = $result['answer'];
        }
        $session->setContextValues($values);

        return $this->getFirstChildId($node, $session);
    }

    private function executeAiAgent(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $question = (string) ($session->getContextValue($data['question_variable'] ?? 'last_input') ?? '');
        $locale = (string) ($session->getContextValue('customer_lang') ?? $conversation->locale ?? config('app.locale', 'es'));

        $result = $this->agent->run($question, $session->context ?? [], $data, $locale);

        $conversation->items()->create([
            'type' => 'message',
            'body' => $result['text'],
            'is_internal' => false,
            'metadata' => [
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'ai_agent' => true,
                'used_tools' => $result['used_tools'],
            ],
        ]);

        if ($result['action'] === 'escalate') {
            $conversation->releaseFromBot();
            $session->update(['status' => 'transferred', 'ended_at' => now()]);

            return null;
        }

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Recent conversation turns for AI memory, oldest first.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function conversationHistory(Conversation $conversation, int $limit = 8): array
    {
        return $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn ($item) => [
                'role' => ($item->metadata['sent_by_chatflow'] ?? false) ? 'assistant' : 'user',
                'content' => (string) ($item->body ?? ''),
            ])
            ->values()
            ->all();
    }

    private function executeOrderLookup(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $orderVar = $data['order_variable'] ?? 'numero_pedido';
        $orderId = $session->getContextValue($orderVar);

        $customer = [
            'erp_id' => $session->getContextValue('customer_erp_id'),
            'ps_id' => $session->getContextValue('customer_ps_id'),
            'email' => $session->getContextValue('customer_email'),
        ];

        $order = $this->orderLookup->lookup($orderId, $customer, $data['source'] ?? 'auto');

        if ($order['found']) {
            // Seed every field a found_message template might interpolate, including
            // order_id/order_date which custom templates and the default message use.
            $session->setContextValues([
                'order_found' => true,
                'order_id' => $order['order_id'] ?? '',
                'order_date' => $order['date'] ?? '',
                'order_status' => $order['status'] ?? '',
                'order_total' => $order['total'] ?? '',
                'order_tracking' => $order['tracking'] ?? '',
            ]);

            $body = ! empty($data['found_message'])
                ? $this->interpolateContext($data['found_message'], $session->context ?? [])
                : $this->defaultOrderMessage($order);
        } else {
            $session->setContextValue('order_found', false);
            $body = $data['not_found_message']
                ?? 'No he encontrado ese pedido asociado a tu cuenta. Verifica el número e inténtalo de nuevo.';
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => $this->localizeForCustomer($body, $session),
            'is_internal' => false,
            'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
        ]);

        return $this->getFirstChildId($node, $session);
    }

    /**
     * @param  array<string,mixed>  $order
     */
    private function defaultOrderMessage(array $order): string
    {
        $lines = ["📦 Pedido #{$order['order_id']}"];

        if ($order['status']) {
            $lines[] = "Estado: {$order['status']}";
        }
        if ($order['date']) {
            $lines[] = "Fecha: {$order['date']}";
        }
        if ($order['total']) {
            $lines[] = "Total: {$order['total']}";
        }
        if ($order['tracking']) {
            $lines[] = "Seguimiento: {$order['tracking']}";
        }

        return implode("\n", $lines);
    }

    private function executeHttpRequest(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $result = $this->httpRequester->send($data, $session->context ?? []);

        $saveTo = $data['save_to'] ?? 'http_response';
        $session->setContextValues([
            $saveTo => $result['value'],
            $saveTo.'_ok' => $result['ok'],
            $saveTo.'_status' => $result['status'],
        ]);

        if (! empty($data['show_message']) && ! empty($data['message_template'])) {
            $conversation->items()->create([
                'type' => 'message',
                'body' => $this->interpolateContext($data['message_template'], $session->context ?? []),
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);
        }

        return $this->getFirstChildId($node, $session);
    }

    /**
     * @return array<int, string>
     */
    private function csatOptions(array $data): array
    {
        if (! empty($data['options']) && is_array($data['options'])) {
            return array_values($data['options']);
        }

        return match ($data['scale'] ?? '1-5') {
            'thumbs' => ['👍 Sí', '👎 No'],
            '1-10' => array_map('strval', range(1, 10)),
            default => ['⭐ Muy malo', '⭐⭐ Malo', '⭐⭐⭐ Normal', '⭐⭐⭐⭐ Bueno', '⭐⭐⭐⭐⭐ Excelente'],
        };
    }

    private function executeCsat(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $question = $this->localizeForCustomer(
            $this->interpolateContext($data['question'] ?? '¿Cómo valorarías nuestra atención?', $session->context ?? []),
            $session,
        );
        $options = $this->csatOptions($data);

        $conversation->items()->create([
            'type' => 'message',
            'body' => $this->numberedPrompt($question, $options),
            'is_internal' => false,
            'metadata' => [
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'csat' => true,
                'bot_options' => $options,
                'bot_prompt' => $question,
            ],
        ]);

        return null; // wait for the rating
    }

    private function executeBusinessHours(array $node, ChatFlowSession $session): ?string
    {
        $data = $node['data'] ?? [];
        $within = $this->isWithinBusinessHours($data);

        $session->setContextValue('within_business_hours', $within);

        return $this->getFirstChildId($node, $session);
    }

    private function executeRichMessage(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $ctx = $session->context ?? [];
        $options = array_values($data['options'] ?? []);

        $cards = $this->normalizeCards($data['cards'] ?? [], $ctx);
        if (count($cards) > 1) {
            return $this->executeCarousel($node, $session, $conversation, $cards, $options);
        }

        $title = $this->interpolateContext($data['title'] ?? '', $ctx);
        $subtitle = $this->interpolateContext($data['subtitle'] ?? '', $ctx);
        $imageUrl = $data['image_url'] ?? null;

        $bodyParts = array_filter([$title, $subtitle]);
        $body = implode("\n", $bodyParts);

        if ($options) {
            $body = $this->numberedPrompt($body, $options);
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => $body !== '' ? $body : ($imageUrl ?? ''),
            'is_internal' => false,
            'metadata' => array_filter([
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'card' => array_filter([
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'image_url' => $imageUrl,
                ]),
                'image_url' => $imageUrl, // text channels send it as an attachment
                'bot_options' => $options ?: null,
                'bot_prompt' => trim($title.' '.$subtitle) ?: null,
            ]),
        ]);

        // With options it waits for the selection; otherwise it continues.
        return $options ? null : $this->getFirstChildId($node, $session);
    }

    /**
     * Carousel of product cards. Delivered with each channel's native format
     * (Messenger generic template, WhatsApp/Instagram image cards); the text
     * body lists them numbered so web/email and fallbacks still work.
     *
     * @param  array<int, array{title: string, subtitle: string, image_url: ?string, url: ?string}>  $cards
     * @param  array<int, string>  $options
     */
    private function executeCarousel(array $node, ChatFlowSession $session, Conversation $conversation, array $cards, array $options): ?string
    {
        $ctx = $session->context ?? [];
        $header = $this->interpolateContext($node['data']['title'] ?? '', $ctx);

        $lines = array_map(
            fn ($c) => trim($c['title'].($c['subtitle'] !== '' ? ' — '.$c['subtitle'] : '')),
            $cards,
        );

        if ($options) {
            $body = $this->numberedPrompt($header, $options);
        } else {
            $list = $this->numberedList($lines);
            $body = $header !== '' ? $header."\n\n".$list : $list;
        }

        $conversation->items()->create([
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
            'metadata' => array_filter([
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'cards' => $cards,
                'bot_options' => $options ?: null,
                'bot_prompt' => $header ?: null,
            ], fn ($v) => $v !== null && $v !== ''),
        ]);

        return $options ? null : $this->getFirstChildId($node, $session);
    }

    /**
     * Normalize and interpolate a list of carousel cards, dropping empty ones.
     *
     * @param  array<int, mixed>  $cards
     * @param  array<string, mixed>  $ctx
     * @return array<int, array{title: string, subtitle: string, image_url: ?string, url: ?string}>
     */
    private function normalizeCards(array $cards, array $ctx): array
    {
        return array_values(array_filter(array_map(function ($card) use ($ctx) {
            if (! is_array($card)) {
                return null;
            }

            $title = $this->interpolateContext((string) ($card['title'] ?? ''), $ctx);
            $subtitle = $this->interpolateContext((string) ($card['subtitle'] ?? ''), $ctx);
            $image = $card['image_url'] ?? null;
            $url = $card['url'] ?? null;

            if ($title === '' && $subtitle === '' && empty($image)) {
                return null;
            }

            return [
                'title' => $title,
                'subtitle' => $subtitle,
                'image_url' => $image ?: null,
                'url' => $url ?: null,
            ];
        }, $cards)));
    }

    /**
     * Send a file (PDF/image/video) to the customer. Delivered as a native
     * attachment on WhatsApp/Messenger/Instagram and as an attachment item on web.
     */
    private function executeSendFile(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $url = $this->interpolateContext((string) ($data['file_url'] ?? ''), $session->context ?? []);

        if ($url === '') {
            return $this->getFirstChildId($node, $session);
        }

        $caption = $this->localizeForCustomer($data['caption'] ?? '', $session);
        $type = in_array($data['file_type'] ?? '', ['image', 'video', 'document'], true) ? $data['file_type'] : 'document';

        $conversation->items()->create([
            'type' => 'message',
            'body' => $caption,
            'is_internal' => false,
            'attachment_urls' => [$url],
            'metadata' => array_filter([
                'sent_by_chatflow' => true,
                'flow_node_id' => $node['id'],
                'attachment' => ['url' => $url, 'type' => $type, 'caption' => $caption ?: null],
            ], fn ($v) => $v !== null && $v !== ''),
        ]);

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Resolves the conversation's document request (HelpdeskDocument) and seeds
     * context variables so later message nodes can send the secure portal link:
     * {{doc_upload_url}} (subir/consultar) and {{doc_missing}} (documentos que faltan).
     */
    private function executeDocumentLink(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $info = $this->documentLink->resolve($conversation);

        $session->setContextValues([
            'doc_upload_url' => $info['upload_url'] ?? '',
            'doc_missing' => $info['missing'] ?? '',
            'doc_found' => $info['found'] ? '1' : '',
        ]);

        return $this->getFirstChildId($node, $session);
    }

    private function executeTransfer(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $message = $this->localizeForCustomer($data['message'] ?? 'Un momento, te transfiero con un agente.', $session);

        if ($message) {
            $conversation->items()->create([
                'type' => 'message',
                'body' => $message,
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);
        }

        if (($session->flowConditions()['handoff_summary'] ?? false) || ($data['summary'] ?? false)) {
            $this->handoff->postFor($conversation);
        }

        // Release first so the assignment broadcast finds the conversation back
        // in the inbox, then assign — which notifies the agent/group in real time.
        $conversation->releaseFromBot();
        $this->assignConversation($conversation, $data);

        $session->update(['status' => 'transferred', 'ended_at' => now()]);

        return null;
    }

    /**
     * Assign the conversation to a specific agent and/or group when the transfer
     * node specifies one.
     *
     * @param  array<string,mixed>  $data
     */
    private function assignConversation(Conversation $conversation, array $data): void
    {
        $agentId = ! empty($data['assignee_id']) ? (int) $data['assignee_id'] : null;
        $groupId = ! empty($data['group_id']) ? (int) $data['group_id'] : null;

        // assignTo()/assignToGroup() broadcast the inbox change and notify the
        // agent (or every group member) in real time — unlike a plain update().
        if ($agentId) {
            $conversation->assignTo($agentId);

            if ($groupId) {
                $conversation->update(['group_id' => $groupId]);
            }

            return;
        }

        if ($groupId) {
            $conversation->assignToGroup($groupId);
        }
    }

    private function executeClose(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $farewell = trim($data['farewell'] ?? '');

        if ($farewell) {
            $conversation->items()->create([
                'type' => 'message',
                'body' => $this->localizeForCustomer($farewell, $session),
                'is_internal' => false,
                'metadata' => ['sent_by_chatflow' => true, 'flow_node_id' => $node['id']],
            ]);
        }

        $session->update(['status' => 'completed', 'ended_at' => now()]);

        return null;
    }

    private function executeAddTag(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $tags = $node['data']['tags'] ?? [];

        if (! empty($tags)) {
            $context = $session->context ?? [];
            $existing = $context['added_tags'] ?? [];
            $session->update([
                'context' => array_merge($context, ['added_tags' => array_values(array_unique(array_merge($existing, $tags)))]),
            ]);
        }

        return $this->getFirstChildId($node, $session);
    }

    private function executeSetAttribute(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $attribute = $data['attribute'] ?? '';
        $value = $this->interpolateContext($data['value'] ?? '', $session->context ?? []);

        match ($attribute) {
            'priority' => $conversation->update(['priority' => $value]),
            'status' => $conversation->update(['status_id' => (int) $value]),
            'assignee' => $conversation->update(['assignee_id' => (int) $value]),
            'custom' => $session->update([
                'context' => array_merge($session->context ?? [], [$data['custom_key'] ?? $attribute => $value]),
            ]),
            default => null,
        };

        return $this->getFirstChildId($node, $session);
    }

    private function interpolateContext(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', fn ($m) => $context[$m[1]] ?? $m[0], $text);
    }

    private function executeEnd(array $node, ChatFlowSession $session): ?string
    {
        $data = $node['data'] ?? [];
        $isTransfer = ($data['action'] ?? '') === 'transfer_to_agent';

        // Transfer-to-agent ending is a handoff → return it to the inbox. A plain
        // close means the bot resolved it: it stays out of the inbox (history only).
        if ($isTransfer) {
            $session->conversation->releaseFromBot();
        }

        $session->update(['status' => $isTransfer ? 'transferred' : 'completed', 'ended_at' => now()]);

        return null;
    }

    private function getFirstChildId(array $node, ChatFlowSession $session): ?string
    {
        return $session->chatFlow->childrenByParent()[$node['id']][0]['id'] ?? null;
    }
}
