<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\ProductTag;

class StoreProductTagService
{
    public function execute(array $names): array
    {
        $tags = [];
        foreach ($names as $name) {
            $tag = ProductTag::query()->firstOrCreate(
                ['name' => trim($name)],
                ['status' => 'published']
            );
            $tags[] = $tag->id;
        }

        return $tags;
    }
}
