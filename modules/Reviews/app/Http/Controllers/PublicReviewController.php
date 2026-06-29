<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;

class PublicReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::query()
            ->where(fn ($q) => $q
                ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                ->orWhereDoesntHave('moderation')
            )
            ->with('moderation')
            ->orderByDesc('star_rating')
            ->orderByDesc('review_time')
            ->limit(100)
            ->get();

        [$totalCount, $avgRating] = $this->getAggregateStats();

        $tagCounts = $reviews
            ->flatMap(fn ($r) => $r->moderation?->tags ?? [])
            ->countBy()
            ->sortByDesc(fn ($c) => $c)
            ->all();

        return view('template::views.testimonios', compact('reviews', 'avgRating', 'totalCount', 'tagCounts'));
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

    /**
     * AJAX endpoint: devuelve cards de reviews renderizadas (HTML) para insertar en containers
     * cargados desde shortcodes tipo `reviews-home`, `reviews-about`, `reviews-service`.
     *
     * Query params:
     *   scope: 'home'|'about'|'service' (default 'home')
     *   limit: int (default 6, max 20)
     *   min_rating: 1-5 (default 5 for home, 4 for service)
     *   tag: opcional, filtrar por tag (scope=service)
     */
    public function cardsJson(Request $request): JsonResponse
    {
        $scope = $request->input('scope', 'home');
        $limit = min(20, max(1, (int) $request->input('limit', 6)));
        $minRating = max(1, min(5, (int) $request->input('min_rating', $scope === 'service' ? 4 : 5)));
        $tag = $request->input('tag');

        $ratingValues = ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE'];
        $allowedRatings = array_slice($ratingValues, $minRating - 1);

        $visible = fn ($q) => $q
            ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
            ->orWhereDoesntHave('moderation');

        $query = Review::query()
            ->where($visible)
            ->whereIn('star_rating', $allowedRatings)
            ->with(['moderation', 'translations']);

        if ($tag) {
            $query->whereHas('moderation', fn ($q) => $q->whereJsonContains('tags', $tag));
        }

        $reviews = $query->inRandomOrder()->limit($limit)->get();

        [$totalCount, $avgRating] = $this->getAggregateStats();

        $partial = $scope === 'about'
            ? 'reviews::shortcodes.partials.swiper-slides'
            : 'reviews::shortcodes.partials.cards';

        $html = view($partial, [
            'reviews' => $reviews,
            'scope' => $scope,
        ])->render();

        return response()->json([
            'html' => $html,
            'stats' => [
                'total' => $totalCount,
                'avg_rating' => round($avgRating, 1),
            ],
        ])->header('Cache-Control', 'public, max-age=300');
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
     * AJAX endpoint: devuelve todos los reviews visibles como JSON estructurado
     * para el shortcode `reviews-page` (testimonios). Soporta filtrado client-side.
     *
     * Query params:
     *   locale: código de idioma (default: app locale)
     */
    public function dataJson(Request $request): JsonResponse
    {
        $locale = $request->input('locale', app()->getLocale());
        $localeCode = strtoupper($locale);

        $activeIds = ReviewGoogleLocation::where('is_active', true)->pluck('id')->all();

        $visible = fn ($q) => $q
            ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
            ->orWhereDoesntHave('moderation');

        $reviews = Review::query()
            ->where($visible)
            ->when($activeIds, fn ($q) => $q->whereIn('location_id', $activeIds))
            ->with(['moderation', 'translations'])
            ->orderByDesc('star_rating')
            ->orderByDesc('review_time')
            ->limit(500)
            ->get();

        [$totalCount, $avgRating] = $this->getAggregateStats();

        $tagCounts = $reviews
            ->flatMap(fn ($r) => $r->moderation?->tags ?? [])
            ->countBy()
            ->sortByDesc(fn ($c) => $c)
            ->all();

        $reviewsData = $reviews->map(fn ($r) => [
            'name' => $r->reviewer_name ?? '',
            'rating' => $r->star_rating->value(),
            'text' => $r->translations->firstWhere('locale_code', $localeCode)?->translated_text ?? $r->comment ?? '',
            'tags' => $r->moderation?->tags ?? [],
            'date' => $r->review_time?->diffForHumans() ?? '',
        ])->values();

        return response()->json([
            'reviews' => $reviewsData,
            'avg' => round($avgRating, 1),
            'total' => $totalCount,
            'tag_counts' => $tagCounts,
        ])->header('Cache-Control', 'public, max-age=120');
    }

    /**
     * Return [totalCount, avgRating] for all visible reviews using a single query.
     *
     * @return array{int, float}
     */
    public function getAggregateStats(): array
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
