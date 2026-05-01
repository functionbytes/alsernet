<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Banner;
use Modules\Helpdesk\Models\Customer;

class BannersApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $banners = Banner::query()->active()->get();

        $customerId = $request->query('customer_id');

        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $banners = $banners->filter(fn ($banner) => $this->matchesSegments($banner, $customer));
            }
        }

        return response()->json([
            'banners' => $banners->values()->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'body' => $b->body,
                'type' => $b->type,
                'cta_text' => $b->cta_text,
                'cta_url' => $b->cta_url,
                'dismissible' => $b->dismissible,
            ]),
        ]);
    }

    private function matchesSegments(Banner $banner, Customer $customer): bool
    {
        $segments = $banner->target_segments;

        if (empty($segments)) {
            return true;
        }

        // Filter by country
        if (! empty($segments['country']) && $customer->country !== $segments['country']) {
            return false;
        }

        // Filter by language
        if (! empty($segments['language']) && $customer->language !== $segments['language']) {
            return false;
        }

        return true;
    }
}
