<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Services\Helpcenters\HelpcenterWidgetService;

class HelpcenterWidgetController extends Controller
{
    public function __construct(
        private readonly HelpcenterWidgetService $service
    ) {}

    public function apiWidget(): JsonResponse
    {
        return response()->json($this->service->getWidgetData());
    }

    public function apiArticle(int $id): JsonResponse
    {
        $article = $this->service->getArticle($id);

        if (! $article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        return response()->json($article);
    }

    public function apiArticleFeedback(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'helpful' => ['required', 'boolean'],
        ]);

        $ok = $this->service->recordFeedback($id, (bool) $data['helpful']);

        if (! $ok) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        return response()->json(['success' => true]);
    }
}
