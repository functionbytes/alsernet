<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Supports\BrandHelper;

class BrandObserver
{
    public function saved(Brand $brand): void
    {
        BrandHelper::clearCache();
    }

    public function deleted(Brand $brand): void
    {
        BrandHelper::clearCache();
    }
}
