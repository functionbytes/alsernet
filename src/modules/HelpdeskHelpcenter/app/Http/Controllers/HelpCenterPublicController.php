<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskHelpcenter\Concerns\BuildsFulltextSearch;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;

class HelpCenterPublicController extends Controller
{
    use BuildsFulltextSearch;

    public function index(Request $request): View
    {
        $locale = $this->resolveLocale($request);

        $query = HelpCenterArticle::query()
            ->where('is_published', true)
            ->select('id', 'title', 'slug', 'description', 'views_count', 'published_at');

        if ($request->filled('q')) {
            $term = $request->input('q');
            $booleanTerm = $this->buildBooleanTerm($term);

            if ($booleanTerm !== null) {
                // InnoDB FULLTEXT does not see uncommitted rows; include LIKE so tests inside
                // transactions and edge cases where the FTS cache is cold still return results.
                $query->where(fn ($q) => $q
                    ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%"));
                $query->orderByRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm]);
            } else {
                $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('body', 'like', "%{$term}%"));
            }
        }

        if ($request->filled('category')) {
            $categorySlug = $request->input('category');
            $query->whereHas('categories', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug)
                    ->orWhereHas('parent', fn ($p) => $p->where('slug', $categorySlug));
            });
        }

        if ($locale !== config('app.locale')) {
            $query->inLocale($locale);
        }

        $articles = $query->orderByDesc('views_count')
            ->paginate(config('helpdeskhelpcenter.pagination.public', 12));

        $categories = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('position')
            ->get();

        return view('helpdeskhelpcenter::public.helpcenter.index', compact('articles', 'categories', 'locale'));
    }

    public function show(Request $request, string $slug): View
    {
        $locale = $this->resolveLocale($request);

        // Try to find by translation slug first, then fall back to article slug
        $article = $this->findBySlug($slug, $locale);

        $article->incrementQuietly('views_count');

        $related = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where('id', '!=', $article->id)
            ->select('id', 'title', 'slug', 'description')
            ->orderByDesc('views_count')
            ->take(config('helpdeskhelpcenter.public.related_limit', 5))
            ->get();

        $translation = $article->translation($locale);

        return view('helpdeskhelpcenter::public.helpcenter.show', compact('article', 'related', 'locale', 'translation'));
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $minChars = (int) config('helpdeskhelpcenter.public.search_min_chars', 3);

        if (strlen($q) < $minChars) {
            return response()->json(['articles' => []]);
        }

        $query = HelpCenterArticle::query()
            ->where('is_published', true)
            ->select('id', 'title', 'slug', 'description')
            ->take(5);

        $booleanTerm = $this->buildBooleanTerm($q);

        if ($booleanTerm !== null) {
            $query->where(fn ($qb) => $qb
                ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
                ->orWhere('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%"));
            $query->orderByRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm]);
        } else {
            $query->where(fn ($qb) => $qb->where('title', 'like', "%{$q}%")->orWhere('body', 'like', "%{$q}%"))
                ->orderByDesc('views_count');
        }

        $articles = $query->get();

        return response()->json(['articles' => $articles]);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('helpdeskhelpcenter.supported_locales', []);

        $candidate = $this->extractLocaleCandidate($request);

        if ($candidate && in_array($candidate, $supported, true)) {
            return $candidate;
        }

        return config('app.locale', 'es');
    }

    private function extractLocaleCandidate(Request $request): ?string
    {
        if ($request->filled('locale')) {
            $candidate = $request->input('locale');

            return strlen($candidate) <= 8 ? $candidate : null;
        }

        $acceptLanguage = $request->header('Accept-Language', '');

        if (! $acceptLanguage) {
            return null;
        }

        $primary = explode(',', $acceptLanguage)[0];
        $candidate = trim(explode(';', $primary)[0]);

        return ($candidate && strlen($candidate) <= 8) ? $candidate : null;
    }

    private function findBySlug(string $slug, string $locale): HelpCenterArticle
    {
        // Try translation slug
        $byTranslation = HelpCenterArticle::query()
            ->whereHas('translations', fn ($q) => $q->where('slug', $slug)->where('locale', $locale))
            ->where('is_published', true)
            ->with('translations')
            ->first();

        if ($byTranslation) {
            return $byTranslation;
        }

        return HelpCenterArticle::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('translations')
            ->firstOrFail();
    }
}
