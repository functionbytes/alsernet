<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;

class HelpcenterWidgetController extends Controller
{
    public function __construct(
        private readonly HelpcenterWidgetService $service
    ) {}

    public function apiWidget(): JsonResponse
    {
        return response()->json($this->service->getWidgetData());
    }

    public function apiSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        $rawLocale = trim($request->input('locale', ''));
        $supported = config('helpdeskhelpcenter.supported_locales', []);
        $locale = in_array($rawLocale, $supported, true) ? $rawLocale : '';

        if (strlen($q) < 2) {
            return response()->json(['articles' => []]);
        }

        $articles = $this->service->searchArticles($q, $locale);

        return response()->json(['articles' => $articles]);
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
