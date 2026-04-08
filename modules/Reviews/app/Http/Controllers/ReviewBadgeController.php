<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\BadgeGeneratorService;

class ReviewBadgeController extends Controller
{
    public function __construct(
        private readonly BadgeGeneratorService $badgeService
    ) {
        $this->middleware('auth');
    }

    /**
     * Badge management page showing all active locations.
     */
    public function index(): View
    {
        $locations = ReviewGoogleLocation::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'average_rating', 'total_reviews']);

        $stats = [
            'total' => $locations->count(),
            'avg_rating' => $locations->count() ? round($locations->avg('average_rating'), 1) : 0,
            'total_reviews' => $locations->sum('total_reviews'),
            'styles' => 4,
        ];

        return view('reviews::badges.index', compact('locations', 'stats'));
    }

    /**
     * Return an SVG badge preview for the given location.
     */
    public function preview(Request $request, ReviewGoogleLocation $location): Response
    {
        $validated = $request->validate([
            'style' => ['sometimes', 'string', 'in:standard,minimal,dark,light'],
        ]);

        $style = $validated['style'] ?? 'standard';
        $svg = $this->badgeService->generateSvg(
            avgRating: (float) $location->average_rating,
            reviewCount: (int) $location->total_reviews,
            businessName: $location->name,
            style: $style
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Download the badge as SVG or PNG.
     */
    public function download(Request $request, ReviewGoogleLocation $location): Response
    {
        $validated = $request->validate([
            'format' => ['sometimes', 'string', 'in:svg,png'],
            'style' => ['sometimes', 'string', 'in:standard,minimal,dark,light'],
        ]);

        $format = $validated['format'] ?? 'svg';
        $style = $validated['style'] ?? 'standard';
        $slug = str($location->name)->slug()->limit(40)->toString();

        $svg = $this->badgeService->generateSvg(
            avgRating: (float) $location->average_rating,
            reviewCount: (int) $location->total_reviews,
            businessName: $location->name,
            style: $style
        );

        if ($format === 'png') {
            $png = $this->badgeService->generatePng($svg);

            if ($png === '') {
                return response('PNG generation not available on this server.', 501, [
                    'Content-Type' => 'text/plain',
                ]);
            }

            return response($png, 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => "attachment; filename=\"badge-{$slug}.png\"",
            ]);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"badge-{$slug}.svg\"",
        ]);
    }

    /**
     * Return HTML embed code and the public badge URL for a location.
     */
    public function embedCode(Request $request, ReviewGoogleLocation $location): JsonResponse
    {
        $validated = $request->validate([
            'style' => ['sometimes', 'string', 'in:standard,minimal,dark,light'],
        ]);

        $style = $validated['style'] ?? 'standard';
        $badgeUrl = route('reviews.badge.public', ['location' => $location->id])
                     .'?style='.$style;
        $embedCode = $this->badgeService->generateEmbedCode($badgeUrl);

        return response()->json([
            'badge_url' => $badgeUrl,
            'embed_code' => $embedCode,
        ]);
    }

    /**
     * Public (no-auth) SVG badge endpoint for embedding on external sites.
     */
    public function publicBadge(Request $request, ReviewGoogleLocation $location): Response
    {
        if (! $location->is_active) {
            abort(404);
        }

        $style = $request->query('style', 'standard');

        if (! in_array($style, ['standard', 'minimal', 'dark', 'light'], true)) {
            $style = 'standard';
        }

        $svg = $this->badgeService->generateSvg(
            avgRating: (float) $location->average_rating,
            reviewCount: (int) $location->total_reviews,
            businessName: $location->name,
            style: $style
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
    }
}
