<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Http\Resources\DashboardStatsResource;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Services\ReviewDashboardService;

class ReviewDashboardController extends Controller
{
    public function __construct(
        private readonly ReviewDashboardService $dashboardService
    ) {
        $this->middleware('can:reviews.reviews.view');
    }

    public function index(Request $request): View
    {
        return view('reviews::dashboard.index', [
            'range' => $request->get('range', 'last_30_days'),
        ]);
    }

    public function data(): JsonResponse
    {
        $data = [
            'kpis' => $this->dashboardService->getKpiMetrics(),
            'rating_trends' => $this->dashboardService->getRatingTrends(12),
            'location_stats' => $this->dashboardService->getLocationStats(),
            'reviews_by_day' => $this->dashboardService->getReviewsByDay(30),
            'rating_distribution' => $this->dashboardService->getRatingDistribution(),
            'recent_reviews' => $this->dashboardService->getRecentReviews(10),
            'attention_needed' => $this->dashboardService->getReviewsNeedingAttention(10),
            'avg_response_time' => $this->dashboardService->getAverageResponseTime(),
            'top_reviewers' => $this->dashboardService->getTopReviewers(5),
            'sentiment_trend' => $this->dashboardService->getSentimentTrend(30),
        ];

        return response()->json(new DashboardStatsResource($data));
    }

    public function kpisData(): JsonResponse
    {
        $kpis = $this->dashboardService->getKpiMetrics();

        return response()->json([
            'total_reviews' => $kpis['total'],
            'avg_rating' => number_format((float) $kpis['avg_rating'], 1),
            'pending_replies' => $kpis['unanswered'],
            'response_rate' => number_format((float) $kpis['response_rate'], 1).'%',
            'last_updated' => now()->format('H:i:s'),
        ]);
    }

    public function templatePreview(Request $request, ReviewReplyTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $renderedText = $template->body;

        if ($reviewId = $request->integer('review_id')) {
            $review = Review::with('location')->find($reviewId);

            if ($review) {
                $renderedText = $template->renderForReview($review);
            }
        }

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'category' => ucfirst($template->category),
            'template_text' => $template->body,
            'rendered_text' => $renderedText,
        ]);
    }
}
