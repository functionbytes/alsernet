<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskPrestashop\Http\Requests\StorePsRecommendationRequest;
use Modules\HelpdeskPrestashop\Models\PsRecommendation;

class PsRecommendationController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $recommendations = PsRecommendation::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'product_id', 'id_product_attribute', 'product_name', 'product_sku',
                'price_with_tax', 'product_url', 'product_image', 'created_at']);

        return response()->json(['success' => true, 'recommendations' => $recommendations]);
    }

    public function store(StorePsRecommendationRequest $request, Conversation $conversation): JsonResponse
    {
        $rec = PsRecommendation::create(array_merge($request->validated(), [
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
        ]));

        return response()->json(['success' => true, 'id' => $rec->id], 201);
    }
}
