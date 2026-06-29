<?php

namespace Modules\Reviews\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Http\Resources\ApiResponse;
use Modules\Reviews\Http\Resources\ReviewResource;
use Modules\Reviews\Http\Resources\ReviewStatsResource;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewAutoSuggestionService;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewAutoSuggestionService $suggestionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $query = Review::query()
            ->with(['location', 'moderation'])
            ->withCount('replies');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }

        if ($request->filled('rating')) {
            $query->where('star_rating', $request->input('rating'));
        }

        if ($request->filled('is_visible')) {
            if ($request->boolean('is_visible')) {
                $query->visible();
            }
        }

        if ($request->filled('is_featured')) {
            if ($request->boolean('is_featured')) {
                $query->featured();
            }
        }

        if ($request->filled('has_reply')) {
            if ($request->boolean('has_reply')) {
                $query->withGoogleReply();
            } else {
                $query->withoutGoogleReply();
            }
        }

        if ($request->filled('has_comment')) {
            if ($request->boolean('has_comment')) {
                $query->withComment();
            } else {
                $query->withoutComment();
            }
        }

        $reviews = $query
            ->latest('review_time')
            ->paginate(min($request->integer('per_page', 20), 100));

        Log::info('API request: reviews.index', [
            'user_id' => $request->user()?->id,
            'filters' => $request->only(['location_id', 'rating', 'is_visible', 'is_featured', 'has_reply', 'has_comment']),
            'total_results' => $reviews->total(),
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return ApiResponse::paginated(
            ReviewResource::collection($reviews),
            'Reviews retrieved successfully'
        );
    }

    public function show(Review $review, Request $request): JsonResponse
    {
        $this->authorize('view', $review);

        $review->load(['location', 'moderation', 'replies.createdBy']);

        Log::info('API request: reviews.show', [
            'user_id' => $request->user()?->id,
            'review_id' => $review->id,
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return ApiResponse::resource(
            ReviewResource::make($review),
            'Review retrieved successfully'
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $cacheKey = 'reviews_stats_'.md5(json_encode([
            'location_id' => $request->input('location_id'),
            'days' => $request->input('days'),
        ]));

        $ttl = now()->addMinutes(config('reviews.general.cache.stats_ttl_minutes', 5));
        $stats = Cache::remember($cacheKey, $ttl, function () use ($request) {
            $locationId = $request->filled('location_id') ? $request->integer('location_id') : null;
            $days = $request->filled('days') ? $request->integer('days', 30) : null;
            $dateFrom = $days ? now()->subDays($days) : null;

            // Single optimized query using aggregates and conditional logic
            $stats = Review::query()
                ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
                ->when($dateFrom, fn ($q) => $q->where('review_time', '>=', $dateFrom))
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN star_rating = "FIVE" THEN 1 END) as five_stars,
                    COUNT(CASE WHEN star_rating = "FOUR" THEN 1 END) as four_stars,
                    COUNT(CASE WHEN star_rating = "THREE" THEN 1 END) as three_stars,
                    COUNT(CASE WHEN star_rating = "TWO" THEN 1 END) as two_stars,
                    COUNT(CASE WHEN star_rating = "ONE" THEN 1 END) as one_stars,
                    COUNT(CASE WHEN comment IS NOT NULL THEN 1 END) as with_comment,
                    COUNT(CASE WHEN google_reply_text IS NOT NULL THEN 1 END) as with_reply,
                    CAST(
                        (
                            COUNT(CASE WHEN star_rating = "FIVE" THEN 1 END) * 5.0 +
                            COUNT(CASE WHEN star_rating = "FOUR" THEN 1 END) * 4.0 +
                            COUNT(CASE WHEN star_rating = "THREE" THEN 1 END) * 3.0 +
                            COUNT(CASE WHEN star_rating = "TWO" THEN 1 END) * 2.0 +
                            COUNT(CASE WHEN star_rating = "ONE" THEN 1 END) * 1.0
                        ) AS REAL
                    ) / NULLIF(COUNT(*), 0) as average_rating
                ')
                ->first();

            // Query for featured and visible counts with moderation join
            $moderation = Review::query()
                ->when($locationId, fn ($q) => $q->where('reviews.location_id', $locationId))
                ->when($dateFrom, fn ($q) => $q->where('reviews.review_time', '>=', $dateFrom))
                ->leftJoin('review_moderations', 'reviews.id', '=', 'review_moderations.review_id')
                ->selectRaw('
                    COUNT(CASE WHEN review_moderations.is_visible = 1 THEN 1 END) as total_visible,
                    COUNT(CASE WHEN review_moderations.is_featured = 1 THEN 1 END) as total_featured
                ')
                ->first();

            $avgRating = isset($stats->average_rating) ? (float) $stats->average_rating : 0;

            return [
                'total' => (int) $stats->total,
                'average_rating' => $avgRating,
                'rating_distribution' => [
                    '5' => (int) $stats->five_stars,
                    '4' => (int) $stats->four_stars,
                    '3' => (int) $stats->three_stars,
                    '2' => (int) $stats->two_stars,
                    '1' => (int) $stats->one_stars,
                ],
                'total_visible' => (int) $moderation->total_visible,
                'total_featured' => (int) $moderation->total_featured,
                'with_comment' => (int) $stats->with_comment,
                'with_reply' => (int) $stats->with_reply,
            ];
        });

        Log::info('API request: reviews.stats', [
            'user_id' => $request->user()?->id,
            'filters' => $request->only(['location_id', 'days']),
            'cache_hit' => Cache::has($cacheKey),
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return ApiResponse::resource(
            ReviewStatsResource::make($stats),
            'Statistics retrieved successfully'
        );
    }

    public function suggestions(Review $review, Request $request): JsonResponse
    {
        $this->authorize('view', $review);

        $suggestions = $this->suggestionService->suggestTemplates($review);

        Log::info('API request: reviews.suggestions', [
            'user_id' => $request->user()?->id,
            'review_id' => $review->id,
            'suggestions_count' => count($suggestions),
            'request_id' => $request->header('X-Request-ID'),
        ]);

        return ApiResponse::success(
            data: [
                'review_id' => $review->id,
                'star_rating' => $review->star_rating_value,
                'has_comment' => ! empty($review->comment),
                'suggestions' => $suggestions,
            ],
            message: 'Reply suggestions generated successfully'
        );
    }
}
