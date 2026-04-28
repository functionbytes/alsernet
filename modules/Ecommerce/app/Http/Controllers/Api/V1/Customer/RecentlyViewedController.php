<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Resources\Api\V1\ProductResource;

class RecentlyViewedController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $products = auth()->user()
            ->recentlyViewed()
            ->with('brand')
            ->limit((int) request('limit', 12))
            ->get();

        return $this->ok(ProductResource::collection($products)->toArray(request()));
    }
}
