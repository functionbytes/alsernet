<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Resources\Api\V1\LegalPageResource;
use Modules\Ecommerce\Models\LegalPage;

class LegalPageController extends BaseApiController
{
    public function show(string $slug): JsonResponse
    {
        $page = LegalPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->ok(new LegalPageResource($page));
    }
}
