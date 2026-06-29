<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Models\FlashSale;
use Modules\Ecommerce\Services\FlashSaleService;

class FlashSaleObserver
{
    public function saved(FlashSale $flashSale): void
    {
        FlashSaleService::clearCache();
    }

    public function deleted(FlashSale $flashSale): void
    {
        FlashSaleService::clearCache();
    }
}
