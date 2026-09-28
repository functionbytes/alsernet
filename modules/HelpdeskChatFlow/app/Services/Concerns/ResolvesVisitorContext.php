<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Services\Commerce\WidgetCartGateway;

/**
 * Reads the live "what the visitor is looking at" snapshot (current product,
 * cart, recently viewed) from HelpdeskLivechat's WidgetSession, shared by the
 * flow engine (seeds `{{current_product_title}}` etc. into the session
 * context) and the AI agent node (fences it into the model's prompt).
 *
 * Returns null whenever HelpdeskLivechat isn't installed, the conversation
 * has no widget session yet, or the gateway lookup fails — the caller then
 * simply runs without visitor context (degrades silently, same pattern as
 * ChatFlowNodeExecutor::catalogFor/cartFor).
 */
trait ResolvesVisitorContext
{
    /**
     * @return array{current_product: array<string, mixed>|null, cart: array<string, mixed>|null, viewed_products: array<int, array<string, mixed>>}|null
     */
    protected function resolveVisitorContext(Conversation $conversation): ?array
    {
        if (! class_exists(WidgetCartGateway::class)) {
            return null;
        }

        try {
            $session = app(WidgetCartGateway::class)->sessionFor($conversation);
        } catch (\Throwable) {
            return null;
        }

        if (! $session) {
            return null;
        }

        return [
            'current_product' => is_array($session->current_product) ? $session->current_product : null,
            'cart' => is_array($session->cart_snapshot) ? $session->cart_snapshot : null,
            'viewed_products' => is_array($session->viewed_products) ? $session->viewed_products : [],
        ];
    }
}
