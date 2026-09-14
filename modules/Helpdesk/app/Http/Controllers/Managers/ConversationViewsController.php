<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\StoreInboxViewRequest;
use Modules\Helpdesk\Models\ConversationView;

class ConversationViewsController extends Controller
{
    public function store(StoreInboxViewRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $view = ConversationView::create([
            'name' => $validated['name'],
            'filters' => $validated['filters'] ?? [],
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'view' => [
                'id' => $view->id,
                'name' => $view->name,
                'filters' => $view->filters,
            ],
        ], 201);
    }

    public function destroy(ConversationView $view): JsonResponse
    {
        $this->authorize('delete', $view);

        $view->delete();

        return response()->json(['success' => true]);
    }
}
