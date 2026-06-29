<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\Jobs\ProcessProductImageJob;
use Modules\Ecommerce\Models\Product;

class ProcessExistingProductImagesCommand extends Command
{
    protected $signature = 'ecommerce:process-product-images {--queue : Procesar via queue en lugar de inline}';

    protected $description = 'Procesa imágenes de productos existentes generando thumbs WebP';

    public function handle(): int
    {
        $count = 0;

        Product::query()
            ->whereNotNull('featured_image')
            ->chunk(50, function ($products) use (&$count) {
                foreach ($products as $product) {
                    if ($this->option('queue')) {
                        ProcessProductImageJob::dispatch($product->featured_image);
                    } else {
                        ProcessProductImageJob::dispatchSync($product->featured_image);
                    }

                    $count++;
                }
            });

        $this->info("Se procesaron {$count} imágenes.");

        return self::SUCCESS;
    }
}
