<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Google\Service\Area120Tables\SavedView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Services\SavedViewService;

class SavedViewController extends Controller
{
    public function __construct(
        protected SavedViewService $savedViewService
    ) {}

    /**
     * Get all saved views accessible by the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $viewType = $request->get('type', 'conversation');

        $views = SavedView::accessibleBy($request->user()->id, $request->user()->account_id)
            ->ofType($viewType)
            ->ordered()
            ->with('user:id,name')
            ->get()
            ->map(function ($view) use ($request) {
                return [
                    'id' => $view->id,
                    'name' => $view->name,
                    'display_name' => $view->display_name,
                    'is_shared' => $view->is_shared,
                    'is_default' => $view->is_default,
                    'is_own' => $view->user_id === $request->user()->id,
                    'owner' => $view->user ? $view->user->name : null,
                    'position' => $view->position,
                    'count' => $this->savedViewService->getCount($view),
                ];
            });

        return response()->json($views);
    }

    /**
     * Store a newly created saved view.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'view_type' => 'nullable|string|in:conversation,contact,report',
            'filters' => 'required|array',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'is_shared' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $view = $this->savedViewService->create(
            $validated,
            $request->user()->id,
            $request->user()->account_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Saved view created successfully',
            'view' => $view,
        ], 201);
    }

    /**
     * Display the specified saved view.
     */
    public function show(Request $request, SavedView $savedView): JsonResponse
    {
        // Check access
        if ($savedView->user_id !== $request->user()->id &&
            ! $savedView->is_shared) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        return response()->json([
            'id' => $savedView->id,
            'name' => $savedView->name,
            'view_type' => $savedView->view_type,
            'filters' => $savedView->filters,
            'sort_by' => $savedView->sort_by,
            'sort_direction' => $savedView->sort_direction,
            'is_shared' => $savedView->is_shared,
            'is_default' => $savedView->is_default,
            'is_own' => $savedView->user_id === $request->user()->id,
        ]);
    }

    /**
     * Update the specified saved view.
     */
    public function update(Request $request, SavedView $savedView): JsonResponse
    {
        // Check ownership
        if ($savedView->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Only the owner can update this view',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'filters' => 'nullable|array',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'is_shared' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $view = $this->savedViewService->update($savedView, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Saved view updated successfully',
            'view' => $view,
        ]);
    }

    /**
     * Remove the specified saved view.
     */
    public function destroy(Request $request, SavedView $savedView): JsonResponse
    {
        // Check ownership
        if ($savedView->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Only the owner can delete this view',
            ], 403);
        }

        $savedView->delete();

        return response()->json([
            'success' => true,
            'message' => 'Saved view deleted successfully',
        ]);
    }

    /**
     * Get conversation for a saved view.
     */
    public function conversations(Request $request, SavedView $savedView): JsonResponse
    {
        // Check access
        if ($savedView->user_id !== $request->user()->id &&
            ! $savedView->is_shared) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $conversations = $this->savedViewService->getConversations($savedView, $perPage);

        return response()->json($conversations);
    }

    /**
     * Set a saved view as default.
     */
    public function setDefault(Request $request, SavedView $savedView): JsonResponse
    {
        // Check ownership
        if ($savedView->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Only the owner can set this as default',
            ], 403);
        }

        $this->savedViewService->setAsDefault($savedView);

        return response()->json([
            'success' => true,
            'message' => 'Default view set successfully',
        ]);
    }

    /**
     * Reorder saved views.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view_ids' => 'required|array',
            'view_ids.*' => 'required|integer|exists:saved_views,id',
        ]);

        $this->savedViewService->reorder(
            $validated['view_ids'],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Views reordered successfully',
        ]);
    }

    /**
     * Duplicate a saved view.
     */
    public function duplicate(Request $request, SavedView $savedView): JsonResponse
    {
        // Check access
        if ($savedView->user_id !== $request->user()->id &&
            ! $savedView->is_shared) {
            return response()->json([
                'error' => 'Access denied',
            ], 403);
        }

        $newView = $this->savedViewService->duplicate($savedView, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'View duplicated successfully',
            'view' => $newView,
        ], 201);
    }
}
