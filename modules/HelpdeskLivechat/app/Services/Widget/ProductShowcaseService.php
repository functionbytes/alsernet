<?php

namespace Modules\HelpdeskLivechat\Services\Widget;

use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogManager;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;

/**
 * "Coviewer" en el hilo: publica un carrusel de productos como mensaje de la
 * conversación y lo emite en tiempo real al widget y al panel de agente
 * (reutiliza ConversationMessageCreated, que ya viaja con type + metadata).
 *
 * Dos entradas:
 *  - showcase(): el AGENTE comparte productos que ha buscado en el catálogo.
 *  - autoRecommendForMessage(): el BOT responde a la "pregunta" del visitante
 *    con productos del catálogo (equivalente al módulo product-search de Oct8ne).
 */
class ProductShowcaseService
{
    /**
     * Tope de productos por carrusel — evita payloads gigantes en el broadcast.
     */
    public const MAX_PRODUCTS = 12;

    public function __construct(
        private readonly CatalogManager $catalog
    ) {}

    /**
     * Publica un carrusel de productos en la conversación.
     *
     * @param  array<int, CatalogProduct>  $products
     * @param  int|null  $userId  Agente que comparte (outgoing). null = bot/sistema.
     */
    public function showcase(
        Conversation $conversation,
        array $products,
        ?int $userId = null,
        ?string $note = null,
        bool $fromBot = false,
    ): ?ConversationItem {
        $normalized = array_slice(
            array_map(
                static fn (CatalogProduct $p): array => $p->toArray(),
                array_values($products)
            ),
            0,
            self::MAX_PRODUCTS
        );

        if ($normalized === []) {
            return null;
        }

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'author_id' => null,
            'type' => 'product_carousel',
            'body' => (string) ($note ?? ''),
            'is_internal' => false,
            'metadata' => [
                'products' => $normalized,
                'is_bot' => $fromBot,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Mismo par de eventos que un mensaje normal del widget: MessageReceived
        // (panel/sidebar) + ConversationMessageCreated (widget + panel en vivo).
        event(new MessageReceived($conversation, $item));
        broadcast(new ConversationMessageCreated($item, false));

        return $item;
    }

    /**
     * Si el canal tiene el bot de producto activado, interpreta el texto del
     * visitante como una búsqueda y publica un carrusel con los resultados.
     * Devuelve el item creado, o null si el bot está desactivado o no hubo
     * resultados (degradación silenciosa: no interrumpe la conversación).
     */
    public function autoRecommendForMessage(Conversation $conversation, ?Web $web, string $text): ?ConversationItem
    {
        $text = trim($text);
        if ($text === '' || ! $this->catalog->botEnabledForWeb($web)) {
            return null;
        }

        $products = $this->catalog->forWeb($web)->search($text, 6);
        if ($products === []) {
            return null;
        }

        return $this->showcase($conversation, $products, null, null, true);
    }
}
