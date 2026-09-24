<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\HelpdeskPrestashop\Services\Ext\LivehintsNotifier;

/**
 * product.price_dropped → "Bajada de precio de X (antes/ahora) · Avisar" en
 * cada conversación abierta cuyo cliente tiene X en su lista de deseos o con
 * aviso de reposición apuntado desde el panel.
 *
 * El puente manda old_price/new_price SIN IVA (p.price de PrestaShop): se
 * pasan a precio con IVA con el tipo del producto cuando se conoce (lista de
 * deseos o ficha); si no, se envían tal cual con tax_included=false.
 */
class LivehintsPriceDropped
{
    public function __construct(
        private readonly LivehintsNotifier $hints
    ) {}

    public function handle(PsPriceDropped $event): void
    {
        $this->hints->guard('price_dropped', function () use ($event): void {
            $productId = $event->productId();
            $old = $event->oldPrice();
            $new = $event->newPrice();
            if (! $productId || $new <= 0 || $old <= $new) {
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
                $taxRate = $item['tax_rate'] ?? $data['tax_rate'] ?? null;

                if (! $name || $taxRate === null) {
                    $product ??= $this->hints->product($productId) ?? [];
                    $name = $name ?: ($product['name'] ?? null);
                    $taxRate ??= $product['tax_rate'] ?? null;
                }

                $factor = $taxRate !== null ? 1 + ((float) $taxRate / 100) : 1.0;

                $this->hints->send((int) $conversationId, 'price_dropped', $productId.'|'.$new, [
                    'product_id' => $productId,
                    'name' => $name ? (string) $name : null,
                    'old_price' => round($old * $factor, 2),
                    'new_price' => round($new * $factor, 2),
                    'drop_percent' => $event->dropPercent(),
                    'tax_included' => $taxRate !== null,
                    'source' => $target['source'],
                ]);
            }
        });
    }
}
