<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\ProductAttributeSet;

class StoreAttributeSetService
{
    public function execute(array $data, ?ProductAttributeSet $attributeSet = null): ProductAttributeSet
    {
        if ($attributeSet) {
            $attributeSet->update($data);
        } else {
            $attributeSet = ProductAttributeSet::query()->create($data);
        }

        return $attributeSet;
    }
}
