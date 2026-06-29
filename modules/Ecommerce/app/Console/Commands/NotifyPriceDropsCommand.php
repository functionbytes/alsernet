<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;

class NotifyPriceDropsCommand extends Command
{
    protected $signature = 'ecommerce:notify-price-drops';

    protected $description = 'Notifica a clientes cuando productos en su wishlist bajan de precio';

    public function handle(): int
    {
        $sent = 0;

        Customer::query()->chunk(100, function ($customers) use (&$sent) {
            foreach ($customers as $customer) {
                $productIds = $customer->wishlists()->pluck('product_id')->toArray();
                if (empty($productIds)) {
                    continue;
                }

                $onSale = Product::query()
                    ->whereIn('id', $productIds)
                    ->where('status', 'published')
                    ->whereNotNull('sale_price')
                    ->whereColumn('sale_price', '<', 'price')
                    ->limit(5)
                    ->get();

                if ($onSale->isEmpty()) {
                    continue;
                }

                $html = view('ecommerce::emails.price-drops', [
                    'customer' => $customer,
                    'products' => $onSale,
                ])->render();

                Mail::html($html, function ($message) use ($customer) {
                    $message->to($customer->email)->subject('Productos de tu wishlist en oferta');
                });

                $sent++;
            }
        });

        $this->info("Notificaciones enviadas: {$sent}");

        return self::SUCCESS;
    }
}
