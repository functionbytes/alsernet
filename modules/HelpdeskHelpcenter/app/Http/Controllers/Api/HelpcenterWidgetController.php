<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Http\Requests\ArticleFeedbackRequest;
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

    public function apiArticleFeedback(int $id, ArticleFeedbackRequest $request): JsonResponse
    {
        // Mismo dedup que el voto público: cookie del votante + hash de IP.
        $cookieId = $request->cookie('hd_voter_id') ?? Str::uuid()->toString();
        $salt = config('helpdeskhelpcenter.vote_ip_salt') ?: config('app.key');
        $ipHash = hash('sha256', $request->ip().$salt);

        $ok = $this->service->recordFeedback($id, (bool) $request->validated('helpful'), $cookieId, $ipHash);

        if (! $ok) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        return response()->json(['success' => true])
            ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
    }
}
