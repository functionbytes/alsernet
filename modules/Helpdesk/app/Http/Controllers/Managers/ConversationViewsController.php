<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\ConversationView;

class ConversationViewsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'filters' => ['nullable', 'array'],
        ]);

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
        if ($view->user_id !== auth()->id() || ! $view->canDelete()) {
            abort(403);
        }

        $view->delete();

        return response()->json(['success' => true]);
    }
}
