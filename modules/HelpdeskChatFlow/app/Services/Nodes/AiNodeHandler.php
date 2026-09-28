<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowAiResponder;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\ResolvesVisitorContext;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogManager;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\NullCatalogDriver;
use Modules\HelpdeskLivechat\Services\Commerce\WidgetCartGateway;
use Modules\HelpdeskLivechat\Services\Widget\ProductShowcaseService;

/**
 * AI nodes: `ai_response` (RAG answer) and `ai_agent` (tool-calling agent with
 * orders, help center, catalog and cart tools).
 */
class AiNodeHandler implements NodeHandler
{
    use PostsBotMessages, RendersNodeMessages, ResolvesVisitorContext;

    public const TYPES = ['ai_response', 'ai_agent'];

    public function __construct(
        private readonly ChatFlowAiResponder $aiResponder,
        private readonly ChatFlowAgentService $agent,
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        return match ($node['type']) {
            'ai_response' => $this->executeAiResponse($node, $session, $conversation),
            'ai_agent' => $this->executeAiAgent($node, $session, $conversation),
        };
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

        $this->postBotMessage($conversation, $node['id'], $result['answer'], [
            'ai_generated' => true,
            'ai_used_kb' => $result['used_kb'],
            'ai_sources' => $result['sources'],
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

        $catalog = $this->catalogFor($conversation);
        $history = ($data['use_memory'] ?? true) ? $this->conversationHistory($conversation) : [];
        $visitorContext = $this->resolveVisitorContext($conversation);
        $result = $this->agent->run($question, $session->context ?? [], $data, $locale, $catalog, $this->cartFor($conversation), $history, $visitorContext);

        $this->postBotMessage($conversation, $node['id'], $result['text'], [
            'ai_agent' => true,
            'used_tools' => $result['used_tools'],
        ]);

        // Productos que consultó el agente IA → tarjetas con "Añadir al carrito"
        // en el widget (mismo carrusel que envía un agente humano).
        if (! empty($result['products']) && class_exists(ProductShowcaseService::class)) {
            try {
                app(ProductShowcaseService::class)
                    ->showcase($conversation, $result['products'], null, null, true);
            } catch (\Throwable $e) {
                Log::warning('ChatFlow ai_agent: product showcase failed', ['conversation_id' => $conversation->id, 'error' => $e->getMessage()]);
            }
        }

        if ($result['action'] === 'escalate') {
            $conversation->releaseFromBot();
            $session->update(['status' => 'transferred', 'ended_at' => now()]);

            return null;
        }

        return $this->getFirstChildId($node, $session);
    }

    /**
     * Catálogo del canal web de la conversación (HelpdeskLivechat), o null si
     * el módulo no está o el canal no tiene catálogo. Sin él, el agente IA no
     * ofrece las herramientas de producto.
     */
    private function catalogFor(Conversation $conversation): ?object
    {
        if (! class_exists(CatalogManager::class)) {
            return null;
        }

        $channel = $conversation->inbox?->channel;
        if (! $channel instanceof Web) {
            return null;
        }

        $driver = app(CatalogManager::class)->forWeb($channel, $conversation->customer?->language);

        return $driver instanceof NullCatalogDriver ? null : $driver;
    }

    /**
     * Cesta del visitante (HelpdeskLivechat WidgetCartGateway) para las
     * herramientas show_cart/add_to_cart del agente IA, o null sin módulo.
     */
    private function cartFor(Conversation $conversation): ?object
    {
        if (! class_exists(WidgetCartGateway::class)) {
            return null;
        }

        return new class(app(WidgetCartGateway::class), $conversation)
        {
            public function __construct(private readonly object $gateway, private readonly Conversation $conversation) {}

            public function show(): ?array
            {
                return $this->gateway->snapshot($this->conversation);
            }

            public function add(int $productId, int $attributeId, int $quantity): array
            {
                return $this->gateway->addProduct($this->conversation, $productId, $attributeId, $quantity);
            }
        };
    }

    /**
     * Recent conversation turns for AI memory, oldest first.
     *
     * Fetches one extra row and drops the most recent one: that last item is
     * the customer message that triggered this very node run (already sent
     * separately as the current question), so including it here duplicated it
     * in the prompt. Role is 'user' only for genuine customer messages —
     * everything else (bot AND human agent replies) is 'assistant', since a
     * human-agent turn labelled 'user' made the model think the customer had
     * said it.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function conversationHistory(Conversation $conversation, int $limit = 8): array
    {
        return $conversation->items()
            ->where('type', 'message')
            ->where('is_internal', false)
            ->latest('id')
            ->limit($limit + 1)
            ->get()
            ->reverse()
            ->values()
            ->slice(0, -1)
            ->map(fn ($item) => [
                'role' => $item->isFromCustomer() ? 'user' : 'assistant',
                'content' => (string) ($item->body ?? ''),
            ])
            ->values()
            ->all();
    }
}
