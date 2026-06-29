<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
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

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'id_product_attribute' => ['nullable', 'integer', 'min:1'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_sku' => ['nullable', 'string', 'max:100'],
            'price_with_tax' => ['nullable', 'numeric', 'min:0'],
            'product_url' => ['nullable', 'string', 'max:500'],
            'product_image' => ['nullable', 'string', 'max:500'],
        ]);

        $rec = PsRecommendation::create(array_merge($validated, [
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
        ]));

        return response()->json(['success' => true, 'id' => $rec->id], 201);
    }
}
