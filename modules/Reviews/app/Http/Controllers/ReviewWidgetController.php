<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewWidgetToken;

class ReviewWidgetController extends Controller
{
    /**
     * List widget tokens for the authenticated user's locations.
     */
    public function index(): View
    {
        $tokens = ReviewWidgetToken::query()
            ->with('location:id,name')
            ->latest()
            ->paginate(20);

        $locations = ReviewGoogleLocation::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reviews::widget.index', compact('tokens', 'locations'));
    }

    /**
     * Create a new widget token.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:review_google_locations,id'],
            'max_reviews' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'min_rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'allowed_origins' => ['sometimes', 'nullable', 'array'],
            'allowed_origins.*' => ['string', 'max:255'],
        ]);

        $token = ReviewWidgetToken::create($validated);
        $token->load('location:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Token de widget creado correctamente.',
            'token' => $token,
            'embed_code' => $this->buildEmbedCode($token),
        ], 201);
    }

    /**
     * Revoke (delete) a widget token.
     */
    public function destroy(ReviewWidgetToken $widgetToken): JsonResponse
    {
        $widgetToken->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token de widget eliminado correctamente.',
        ]);
    }

    /**
     * Serve the embeddable JavaScript widget.
     * No auth required — uses public token.
     */
    public function widget(string $token): Response
    {
        $widgetToken = ReviewWidgetToken::query()
            ->active()
            ->where('token', $token)
            ->firstOrFail();

        $apiUrl = route('reviews.widget.reviews', ['token' => $token]);

        $origin = request()->header('Origin');
        $allowedOrigins = $widgetToken->allowed_origins ?? [];

        $corsOrigin = empty($allowedOrigins) || in_array($origin, $allowedOrigins, true)
            ? ($origin ?: '*')
            : null;

        $content = view('reviews::widget.embed', compact('apiUrl'))->render();

        $headers = [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=900',
            'Access-Control-Allow-Origin' => $corsOrigin ?? '*',
            'Access-Control-Allow-Methods' => 'GET',
        ];

        return response($content, 200, $headers);
    }

    /**
     * Return public review data for the widget token.
     * No auth required — cached 15 minutes.
     */
    public function reviews(string $token): JsonResponse
    {
        $widgetToken = ReviewWidgetToken::query()
            ->active()
            ->where('token', $token)
            ->with('location:id,name,average_rating,total_reviews')
            ->firstOrFail();

        $cacheKey = "widget_reviews_{$token}";

        $data = Cache::remember($cacheKey, 900, function () use ($widgetToken): array {
            $minRatingEnums = array_map(
                fn (int $n) => ReviewRating::fromInt($n)->value,
                range($widgetToken->min_rating, 5)
            );

            $reviews = Review::query()
                ->where('location_id', $widgetToken->location_id)
                ->whereIn('star_rating', $minRatingEnums)
                ->where(fn ($q) => $q
                    ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                    ->orWhereDoesntHave('moderation')
                )
                ->orderByDesc('review_time')
                ->limit(min($widgetToken->max_reviews, 10))
                ->get(['id', 'reviewer_name', 'star_rating', 'comment', 'review_time']);

            return [
                'location_name' => $widgetToken->location->name,
                'avg_rating' => (float) $widgetToken->location->average_rating,
                'review_count' => (int) $widgetToken->location->total_reviews,
                'reviews' => $reviews->map(fn (Review $review) => [
                    'id' => $review->id,
                    'reviewer_name' => $review->reviewer_name,
                    'star_rating' => $review->star_rating->value(),
                    'comment' => $review->comment,
                    'review_time' => $review->review_time?->toIso8601String(),
                ])->values()->all(),
            ];
        });

        $origin = request()->header('Origin');
        $allowedOrigins = $widgetToken->allowed_origins ?? [];
        $corsOrigin = empty($allowedOrigins) || in_array($origin, $allowedOrigins, true)
            ? ($origin ?: '*')
            : null;

        return response()->json($data)
            ->header('Access-Control-Allow-Origin', $corsOrigin ?? '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Cache-Control', 'public, max-age=900');
    }

    /**
     * Preview page for admin to see how widget looks.
     */
    public function widgetPreview(ReviewWidgetToken $widgetToken): View
    {
        $widgetToken->load('location:id,name');
        $jsUrl = route('reviews.widget.js', ['token' => $widgetToken->token]);

        return view('reviews::widget.preview', compact('widgetToken', 'jsUrl'));
    }

    private function buildEmbedCode(ReviewWidgetToken $token): string
    {
        $jsUrl = route('reviews.widget.js', ['token' => $token->token]);

        return '<div data-reviews-widget></div>'."\n".
               "<script src=\"{$jsUrl}\"></script>";
    }
}
