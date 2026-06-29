<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Mail\ProductBackInStockMail;
use Modules\Ecommerce\Models\ProductRestockAlert;

class SendRestockAlertsCommand extends Command
{
    protected $signature = 'ecommerce:send-restock-alerts';

    protected $description = 'Envía avisos de restock a clientes que solicitaron notificación de productos que ya volvieron a tener stock';

    public function handle(): void
    {
        $sent = 0;

        ProductRestockAlert::query()
            ->pendingNotification()
            ->with('product')
            ->chunk(100, function ($alerts) use (&$sent) {
                foreach ($alerts as $alert) {
                    $product = $alert->product;

                    if (! $product) {
                        continue;
                    }

                    $hasStock = ! $product->with_storehouse_management || $product->quantity > 0;

                    if (! $hasStock) {
                        continue;
                    }

                    Mail::to($alert->email)->queue(new ProductBackInStockMail($product, $alert));

                    $alert->update(['notified_at' => now()]);

                    Log::info('Restock alert sent', [
                        'product_id' => $product->id,
                        'email' => $alert->email,
                    ]);

                    $sent++;
                }
            });

        $this->info("Se enviaron {$sent} avisos de restock.");
    }
}
