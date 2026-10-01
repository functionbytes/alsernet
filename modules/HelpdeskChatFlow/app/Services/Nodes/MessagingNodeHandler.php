<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Concerns\FormatsNumberedOptions;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;

/**
 * Nodes that talk to the customer: plain messages and the prompts of the
 * waiting nodes (questions, options, identification, documents, CSAT). The
 * reply to a waiting node is processed by the engine / Input handlers.
 */
class MessagingNodeHandler implements NodeHandler
{
    use FormatsNumberedOptions, PostsBotMessages, RendersNodeMessages;

    public const TYPES = ['message', 'quick_replies', 'collect_input', 'identify_customer', 'request_documents', 'csat'];

    public function __construct(
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        return match ($node['type']) {
            'message' => $this->executeMessage($node, $session, $conversation),
            'quick_replies' => $this->executeQuickReplies($node, $session, $conversation),
            'collect_input' => $this->executeCollectInput($node, $session, $conversation),
            'identify_customer' => $this->executeIdentifyCustomer($node, $session, $conversation),
            'request_documents' => $this->executeRequestDocuments($node, $session, $conversation),
            'csat' => $this->executeCsat($node, $session, $conversation),
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
            fn ($i, $key) => ($i + 1).'. '.(config('helpdeskchatflow.document_labels', [])[$key] ?? $key),
            array_keys($docTypes),
            $docTypes,
        ));

        $this->postBotMessage($conversation, $node['id'], "{$intro}\n{$list}");

        return null; // Pause — wait for customer to select and upload documents
    }

    private function executeCollectInput(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $question = $this->interpolateContext($node['data']['question'] ?? '', $session->context ?? []);

        if ($question !== '') {
            $this->postBotMessage($conversation, $node['id'], $question);
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

        $this->postBotMessage($conversation, $node['id'], $question);

        return null; // Pause — wait for customer reply
    }

    private function executeMessage(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $text = $node['data']['text'] ?? '';

        $text = $this->interpolateContext($text, $session->context ?? []);

        $text = $this->localizeForCustomer($text, $session);

        // Optional WhatsApp HSM template: used by the dispatcher when sending a
        // proactive (outbound) message outside the 24h session window.
        $metadata = [];
        if (! empty($node['data']['whatsapp_template'])) {
            $metadata['whatsapp_template'] = (string) $node['data']['whatsapp_template'];
            $metadata['template_vars'] = array_map(
                fn ($v) => $this->interpolateContext((string) $v, $session->context ?? []),
                array_values($node['data']['template_vars'] ?? []),
            );
        }

        $this->postBotMessage($conversation, $node['id'], $text, $metadata);

        return $this->getFirstChildId($node, $session);
    }

    private function executeQuickReplies(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $options = array_values($data['options'] ?? []);

        $header = $this->localizeForCustomer(
            $this->interpolateContext($data['text'] ?? 'Selecciona una opción:', $session->context ?? []),
            $session,
        );

        $labels = $this->localizeOptions($options, $session);

        $this->postBotMessage($conversation, $node['id'], $this->numberedPrompt($header, $labels, $this->optionsHint($session)), [
            'bot_options' => $labels, // delivered as native buttons where the channel supports them
            'bot_prompt' => $header,
        ]);

        return null; // wait for user selection
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
        $labels = $this->localizeOptions($this->csatOptions($data), $session);

        $this->postBotMessage($conversation, $node['id'], $this->numberedPrompt($question, $labels, $this->optionsHint($session)), [
            'csat' => true,
            'bot_options' => $labels,
            'bot_prompt' => $question,
        ]);

        return null; // wait for the rating
    }
}
