<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\GiftMessage\Http\Requests\SearchGiftMessageOrdersRequest;
use Modules\GiftMessage\Services\GiftMessageOrderService;

class GiftMessageOrderController extends Controller
{
    public function __construct(
        private readonly GiftMessageOrderService $orderService
    ) {}

    public function search(SearchGiftMessageOrdersRequest $request): JsonResponse
    {
        $ids = collect(explode(',', $request->validated()['ids']))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '' && ctype_digit($value))
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'rows' => $this->orderService->searchByIds($ids),
        ]);
    }
}
