<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Services\Ext\LivehintsNotifier;

/**
 * product.back_in_stock → "Vuelve a haber stock de X · Avisar" en cada
 * conversación abierta cuyo cliente tiene X en su lista de deseos o con aviso
 * de reposición apuntado desde el panel. El webhook no trae cliente: se
 * cruza el producto con los clientes que tienen conversación abierta.
 */
class LivehintsBackInStock
{
    public function __construct(
        private readonly LivehintsNotifier $hints
    ) {}

    public function handle(PsBackInStock $event): void
    {
        $this->hints->guard('back_in_stock', function () use ($event): void {
            $productId = $event->productId();
            if (! $productId) {
                return;
            }

            $targets = $this->hints->interestedInProduct($productId);
            if ($targets === []) {
                return;
            }

            $data = $event->productData();
            $product = null;

            foreach ($targets as $conversationId => $target) {
                $item = $target['item'] ?? [];
                $name = $item['name'] ?? $data['name'] ?? null;
                $price = $item['price_with_tax'] ?? $data['price_with_tax'] ?? null;

                if (! $name || $price === null) {
                    $product ??= $this->hints->product($productId) ?? [];
                    $name = $name ?: ($product['name'] ?? null);
                    $price ??= $product['price_with_tax'] ?? null;
                }

                $this->hints->send((int) $conversationId, 'back_in_stock', (string) $productId, [
                    'product_id' => $productId,
                    'name' => $name ? (string) $name : null,
                    'stock' => isset($event->payload['stock_quantity']) ? (int) $event->payload['stock_quantity'] : null,
                    'price' => $price !== null ? (float) $price : null,
                    'source' => $target['source'],
                ]);
            }
        });
    }
}
