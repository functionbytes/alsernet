<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;

class PublicReviewController extends Controller
{
    public function index(Request $request): View
    {
        $rating = $request->integer('rating', 0);
        $ratingEnum = ($rating >= 1 && $rating <= 5)
            ? ReviewRating::fromInt($rating)
            : null;

        $query = Review::query()
            ->where(fn ($q) => $q
                ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                ->orWhereDoesntHave('moderation')
            )
            ->with('moderation')
            ->orderByDesc('star_rating')
            ->orderByDesc('review_time');

        if ($ratingEnum !== null) {
            $query->where('star_rating', $ratingEnum->value);
        }

        $reviews = $query->paginate(12)->withQueryString();

        [$totalCount, $avgRating] = $this->aggregateStats();

        return view('template::views.testimonios', compact('reviews', 'avgRating', 'totalCount', 'rating'));
    }

    public function widget(Request $request): Response
    {
        $locationId = $request->input('location_id');
        $limit = min((int) $request->input('limit', 5), 20);
        $minRatingInt = max(1, min(5, (int) $request->input('min_rating', 4)));

        $ratingEnums = array_map(
            fn (int $n) => ReviewRating::fromInt($n)->value,
            range($minRatingInt, 5)
        );

        $reviews = Review::query()
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->whereIn('star_rating', $ratingEnums)
            ->whereHas('moderation', fn ($q) => $q->where('is_visible', true))
            ->with('moderation')
            ->latest('review_time')
            ->limit($limit)
            ->get();

        return response()
            ->view('reviews::public.widget', compact('reviews'))
            ->header('X-Frame-Options', 'ALLOWALL')
            ->header('Content-Security-Policy', 'frame-ancestors *');
    }

    public function embedCode(Request $request): JsonResponse
    {
        $locationId = $request->input('location_id');
        $limit = min((int) $request->input('limit', 5), 20);
        $minRating = max(1, min(5, (int) $request->input('min_rating', 4)));

        $params = http_build_query(array_filter([
            'location_id' => $locationId,
            'limit' => $limit,
            'min_rating' => $minRating,
        ]));

        $baseUrl = url('/reviews/widget');
        $src = $params ? "{$baseUrl}?{$params}" : $baseUrl;

        return response()->json([
            'iframe' => "<iframe src=\"{$src}\" width=\"100%\" height=\"400\" frameborder=\"0\"></iframe>",
        ]);
    }

    /**
     * Return [totalCount, avgRating] for all visible reviews using a single query.
     *
     * @return array{int, float}
     */
    private function aggregateStats(): array
    {
        $row = Review::query()
            ->where(fn ($q) => $q
                ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                ->orWhereDoesntHave('moderation')
            )
            ->selectRaw("COUNT(*) as total, AVG(CASE star_rating
                WHEN 'ONE'   THEN 1
                WHEN 'TWO'   THEN 2
                WHEN 'THREE' THEN 3
                WHEN 'FOUR'  THEN 4
                WHEN 'FIVE'  THEN 5
                ELSE NULL END) as avg_rating")
            ->first();

        return [(int) ($row->total ?? 0), (float) ($row->avg_rating ?? 0)];
    }
}
