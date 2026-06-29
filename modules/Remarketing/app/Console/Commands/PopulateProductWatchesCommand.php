<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PopulateProductWatchesCommand extends Command
{
    protected $signature = 'remarketing:populate-watches';

    protected $description = 'Pre-popula remarketing_product_watches a partir de carts activos (source=cart)';

    public function handle(): int
    {
        if (! Schema::hasTable('remarketing_product_watches')) {
            $this->error('Tabla remarketing_product_watches no existe.');

            return self::FAILURE;
        }

        $itemsTable = Schema::hasTable('remarketing_cart_items')
            ? 'remarketing_cart_items'
            : (Schema::hasTable('remarketing_cart_lines') ? 'remarketing_cart_lines' : null);

        if (! $itemsTable) {
            $this->error('Tabla cart_items no encontrada (probado: remarketing_cart_items, remarketing_cart_lines).');

            return self::FAILURE;
        }

        $stats = ['carts' => 0, 'watches' => 0];

        DB::table('remarketing_carts')
            ->where('status', 'active')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->chunkById(500, function ($carts) use ($itemsTable, &$stats) {
                foreach ($carts as $cart) {
                    $stats['carts']++;
                    $items = DB::table($itemsTable)->where('cart_id', $cart->id)->get();

                    foreach ($items as $item) {
                        if (! ($item->external_product_id ?? null)) {
                            continue;
                        }

                        DB::table('remarketing_product_watches')->updateOrInsert(
                            [
                                'store_id' => $cart->store_id,
                                'customer_id' => $cart->customer_id,
                                'external_product_id' => $item->external_product_id,
                                'source' => 'cart',
                            ],
                            [
                                'reference_price' => $item->unit_price ?? $item->price ?? 0,
                                'expires_at' => now()->addDays(30),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ],
                        );
                        $stats['watches']++;
                    }
                }
            });

        $this->info("Carts procesados: {$stats['carts']}");
        $this->info("Watches creadas/actualizadas: {$stats['watches']}");

        return self::SUCCESS;
    }
}
