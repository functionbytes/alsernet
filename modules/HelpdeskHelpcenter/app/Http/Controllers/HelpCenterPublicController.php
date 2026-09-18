<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            ->published()
            ->visibleToRole($request->user())
            ->select('id', 'title', 'slug', 'description', 'views_count', 'published_at');

        if ($request->filled('q')) {
            $term = $request->input('q');
            $booleanTerm = $this->buildBooleanTerm($term);

            if ($booleanTerm !== null) {
                // InnoDB FULLTEXT does not see uncommitted rows, so the LIKE
                // fallback only combines in tests (shouldFallbackToLikeSearch) —
                // in production it anulaba el índice FULLTEXT en cada búsqueda.
                $query->where(fn ($q) => $q
                    ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
                    ->when($this->shouldFallbackToLikeSearch(), fn ($qb) => $qb
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%")));
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
        $article = $this->findBySlug($slug, $locale, $request->user());

        $article->incrementQuietly('views_count');

        $related = HelpCenterArticle::query()
            ->published()
            ->visibleToRole($request->user())
            ->where('id', '!=', $article->id)
            ->select('id', 'title', 'slug', 'description')
            ->orderByDesc('views_count')
            ->take(config('helpdeskhelpcenter.public.related_limit', 5))
            ->get();

        // translationPublished(), not translation(): a draft translation for an
        // otherwise-published article must fall back to the base article body,
        // not leak its draft content (see findBySlug()'s equivalent guard).
        $translation = $article->translationPublished($locale);

        return view('helpdeskhelpcenter::public.helpcenter.show', compact('article', 'related', 'locale', 'translation'));
    }

    public function search(Request $request): JsonResponse
    {
        $q = $this->stringInput($request, 'q');
        $minChars = (int) config('helpdeskhelpcenter.public.search_min_chars', 3);

        if (strlen($q) < $minChars) {
            return response()->json(['articles' => []]);
        }

        $query = HelpCenterArticle::query()
            ->published()
            ->visibleToRole($request->user())
            ->select('id', 'title', 'slug', 'description')
            ->take(5);

        $booleanTerm = $this->buildBooleanTerm($q);

        if ($booleanTerm !== null) {
            $query->where(fn ($qb) => $qb
                ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
                ->when($this->shouldFallbackToLikeSearch(), fn ($qw) => $qw
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%")));
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
            $candidate = $this->stringInput($request, 'locale');

            return ($candidate !== '' && strlen($candidate) <= 8) ? $candidate : null;
        }

        $acceptLanguage = $request->header('Accept-Language', '');

        if (! $acceptLanguage) {
            return null;
        }

        $primary = explode(',', $acceptLanguage)[0];
        $candidate = trim(explode(';', $primary)[0]);

        return ($candidate && strlen($candidate) <= 8) ? $candidate : null;
    }

    /**
     * `q`/`locale` llegan del query string y pueden ser arrays (?q[]=a&q[]=b),
     * lo que hace explotar tanto strlen() (TypeError) como el cast implícito
     * de $request->string() (Stringable castea con (string) $value, que en
     * este proyecto error_reporting(-1) convierte el warning "Array to string
     * conversion" en ErrorException — comprobado en vivo, no es solo teórico).
     * Un valor array se trata como ausente en vez de intentar castearlo.
     */
    private function stringInput(Request $request, string $key, string $default = ''): string
    {
        if (is_array($request->input($key))) {
            return $default;
        }

        return (string) $request->string($key, $default);
    }

    private function findBySlug(string $slug, string $locale, ?User $user): HelpCenterArticle
    {
        // Try translation slug. La traducción debe estar publicada además del
        // artículo padre: sin este filtro, una traducción borrador era legible
        // públicamente conociendo/adivinando su slug.
        $byTranslation = HelpCenterArticle::query()
            ->whereHas('translations', fn ($q) => $q->where('slug', $slug)->where('locale', $locale)->where('is_published', true))
            ->published()
            ->visibleToRole($user)
            ->with('translations')
            ->first();

        if ($byTranslation) {
            return $byTranslation;
        }

        return HelpCenterArticle::query()
            ->where('slug', $slug)
            ->published()
            ->visibleToRole($user)
            ->with('translations')
            ->firstOrFail();
    }
}
