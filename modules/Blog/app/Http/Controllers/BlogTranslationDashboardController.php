<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Services\BlogPostService;
use Modules\Blog\Services\BlogTranslationService;

class BlogTranslationDashboardController extends Controller
{
    /**
     * Translation coverage dashboard.
     */
    public function index(): View
    {
        $posts = BlogPost::query()
            ->with('translations')
            ->latest()
            ->get();

        $locales = BlogPostService::getSupportedLocales();
        $totalPosts = $posts->count();

        $coverage = [];
        foreach ($locales as $locale) {
            $translated = 0;
            $complete = 0;
            $stale = 0;
            $autoTranslated = 0;
            $reviewed = 0;

            foreach ($posts as $post) {
                $trans = $post->translations->firstWhere('locale', $locale);
                if (! $trans) {
                    continue;
                }

                $translated++;
                if ($trans->isComplete()) {
                    $complete++;
                }
                if ($trans->status?->value === 'stale') {
                    $stale++;
                }
                if ($trans->status?->value === 'auto_translated') {
                    $autoTranslated++;
                }
                if ($trans->status?->value === 'reviewed') {
                    $reviewed++;
                }
            }

            $coverage[$locale] = [
                'translated' => $translated,
                'complete' => $complete,
                'stale' => $stale,
                'auto_translated' => $autoTranslated,
                'reviewed' => $reviewed,
                'missing' => $totalPosts - $translated,
                'percentage' => $totalPosts > 0 ? round(($translated / $totalPosts) * 100) : 0,
            ];
        }

        $metrics = BlogTranslationService::getUsageMetrics(
            now()->subDays(30)->toDateString(),
            now()->toDateString()
        );

        return view('blog::translations.dashboard', compact(
            'coverage',
            'locales',
            'totalPosts',
            'metrics',
        ));
    }

    /**
     * Get usage metrics as JSON for AJAX.
     */
    public function metrics(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());

        $metrics = BlogTranslationService::getUsageMetrics($from, $to);

        return response()->json($metrics);
    }
}
