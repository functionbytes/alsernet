<?php

namespace Modules\HelpdeskChatFlow\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskChatFlow\Services\ChatFlowBusinessEventLauncher;
use Modules\Remarketing\Models\Cart;

/**
 * Polling alternative to the PrestaShop webhook: launches the abandoned-cart
 * outbound flow for carts the Remarketing module has marked as abandoned
 * recently. Useful when real-time webhooks aren't configured. No-op when the
 * Remarketing module (and its cart cache) isn't installed.
 */
class PollAbandonedCartsCommand extends Command
{
    protected $signature = 'chatflow:poll-abandoned-carts {--minutes=20 : Only carts abandoned within the last N minutes}';

    protected $description = 'Launch the abandoned-cart chat flow for recently abandoned carts (Remarketing cache)';

    public function handle(ChatFlowBusinessEventLauncher $launcher): int
    {
        $cartModel = Cart::class;

        if (! class_exists($cartModel)) {
            $this->warn('El módulo Remarketing no está disponible; no hay carritos que consultar.');

            return self::SUCCESS;
        }

        $since = now()->subMinutes(max(1, (int) $this->option('minutes')));
        $launched = 0;

        $cartModel::query()
            ->where('status', 'abandoned')
            ->where('abandoned_at', '>=', $since)
            ->whereNotNull('email')
            ->cursor()
            ->each(function ($cart) use ($launcher, &$launched): void {
                $items = is_array($cart->items) ? $cart->items : [];

                $session = $launcher->launch('abandoned_cart',
                    ['email' => $cart->email],
                    ['cart_total' => (float) $cart->total, 'items_count' => count($items), 'ps_cart_id' => $cart->external_id],
                );

                if ($session) {
                    $launched++;
                }
            });

        $this->info("Lanzados {$launched} flow(s) de carrito abandonado (ventana: {$this->option('minutes')} min).");

        return self::SUCCESS;
    }
}
