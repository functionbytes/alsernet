<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Http\Resources\PublicPageResource;
use Modules\Page\Jobs\RecordPageViewJob;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;
use Modules\Page\Services\PageCacheService;
use Modules\Page\Services\PageService;
use Modules\Template\Services\TemplateManager;

class PublicController extends Controller
{
    public function __construct(
        private readonly TemplateManager $templateManager
    ) {}

    /**
     * Display the homepage (page with template = 'homepage' or configured via settings).
     */
    public function showHomepage(): Response|RedirectResponse
    {
        $page = null;

        // Try to get the configured homepage from settings first
        $homepagePageId = (int) setting('homepage-page-id');

        if ($homepagePageId > 0) {
            $page = Page::where('id', $homepagePageId)->published()->first();
        }

        // Fallback to the page with template='homepage' if not configured or not found
        if (empty($page)) {
            $page = Page::where('template', 'homepage')->published()->first();
        }

        $page = $page ?? abort(404);

        $page->loadMissing(['translations.localeModel']);

        $locale = app()->getLocale();
        $langLinks = $this->buildLangLinks($page, '', $locale);

        view()->share('pageLangLinks', $langLinks);
        view()->share('currentPageLocale', $locale);
        view()->share('supportedLocales', PageService::getSupportedLocales());

        $this->applySeo($page, $locale, '', $langLinks);

        $data = $this->buildPageData($page, $locale, $langLinks);
        $viewName = $this->resolveViewName($page);

        $this->trackView($page);

        return response()
            ->view($viewName, $data)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600');
    }

    /**
     * Display the specified page by path (with optional prefix).
     */
    public function show(string $path): Response|RedirectResponse
    {
        [$slug, $prefix] = $this->parseSlugFromPath($path);

        [$page, $detectedLocale, $matchedTranslation] = $this->resolvePageBySlug($slug);

        app()->setLocale($detectedLocale);

        $langLinks = $this->buildLangLinks($page, $prefix, $detectedLocale);

        // Auto-detect visitor language on first visit and redirect if a better match exists.
        $redirect = $this->detectAndRedirectLocale($detectedLocale, $langLinks);
        if ($redirect) {
            return $redirect;
        }

        view()->share('pageLangLinks', $langLinks);
        view()->share('currentPageLocale', $detectedLocale);
        view()->share('supportedLocales', PageService::getSupportedLocales());

        $this->applySeo($page, $detectedLocale, $prefix, $langLinks);

        $data = $this->buildPageData($page, $detectedLocale, $langLinks);
        $viewName = $this->resolveViewName($page);

        $this->trackView($page);

        return response()
            ->view($viewName, $data)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600');
    }

    /**
     * On the visitor's first page view, detect preferred language from Accept-Language
     * and redirect to the matching translation if one exists and differs from current.
     */
    private function detectAndRedirectLocale(string $currentLocale, array $langLinks): ?RedirectResponse
    {
        // If the user navigated from within the site (e.g. clicked a language link),
        // they made a deliberate locale choice — always respect it.
        $referer = request()->header('Referer', '');
        $host = request()->getSchemeAndHttpHost();
        if ($referer && str_starts_with($referer, $host)) {
            return null;
        }

        // For external arrivals, only auto-redirect once per session.
        if (session()->has('locale_detected')) {
            return null;
        }

        session(['locale_detected' => true]);

        $supported = PageService::getSupportedLocales();
        $preferred = $this->parseAcceptLanguage(request()->header('Accept-Language', ''), $supported);

        if (! $preferred || $preferred === $currentLocale) {
            return null;
        }

        $target = $langLinks[$preferred] ?? null;

        if (! $target || empty($target['url']) || ! $target['published']) {
            return null;
        }

        return redirect($target['url'], 302);
    }

    /**
     * Parse Accept-Language header and return the best supported locale match.
     *
     * @param  string[]  $supported
     */
    private function parseAcceptLanguage(string $header, array $supported): ?string
    {
        if (! $header) {
            return null;
        }

        // Parse "es-419,es;q=0.9,en;q=0.8" into [lang => quality] sorted by quality.
        $langs = [];
        foreach (explode(',', $header) as $part) {
            [$tag, $q] = array_pad(explode(';q=', trim($part)), 2, '1');
            $lang = strtolower(substr(trim($tag), 0, 2)); // only primary subtag
            $langs[$lang] = max($langs[$lang] ?? 0, (float) $q);
        }

        arsort($langs);

        foreach (array_keys($langs) as $lang) {
            if (in_array($lang, $supported)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Return a paginated JSON listing of published pages (safe public fields only).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->get('search');

        $pages = Page::published()
            ->when($search, function ($query, string $term) {
                if (strlen($term) >= 3) {
                    try {
                        return $query->searchFullText($term);
                    } catch (\Exception) {
                        return $query->search($term);
                    }
                }

                return $query->search($term);
            })
            ->orderByDesc('published_at')
            ->paginate(config('page.per_page', 20));

        return PublicPageResource::collection($pages);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse the incoming path into [slug, prefix].
     *
     * @return array{string, string}
     */
    private function parseSlugFromPath(string $path): array
    {
        $prefix = setting('permalink-modules-page-models-page', '');
        $slug = $prefix ? ltrim(Str::replaceFirst($prefix.'/', '', $path), '/') : $path;

        return [$slug, $prefix];
    }

    /**
     * Resolve a Page (or cached stdClass) from the given slug.
     *
     * Returns [page, detectedLocale, matchedTranslation].
     *
     * @return array{Page|\stdClass, string, PageTranslation|null}
     */
    private function resolvePageBySlug(string $slug): array
    {
        $locale = app()->getLocale();
        $detectedLocale = $locale;
        $matchedTranslation = null;

        $cachedPage = PageCacheService::get($slug);

        if ($cachedPage) {
            $page = (object) $cachedPage;
            $detectedLocale = $page->locale ?? $locale;

            return [$page, $detectedLocale, null];
        }

        // Try exact locale match on translations first.
        $matchedTranslation = PageTranslation::forLocale($locale)
            ->where('slug', $slug)
            ->where('status', PageStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('localeModel')
            ->first();

        if ($matchedTranslation) {
            $detectedLocale = $matchedTranslation->locale ?? $locale;
            $page = Page::with('translations.localeModel')
                ->where('id', $matchedTranslation->page_id)
                ->firstOrFail();
        } else {
            // Fallback: any published translation with this slug.
            $matchedTranslation = PageTranslation::where('slug', $slug)
                ->where('status', PageStatus::Published->value)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with('localeModel')
                ->first();

            if ($matchedTranslation) {
                $detectedLocale = $matchedTranslation->locale ?? $locale;
                $page = Page::with('translations.localeModel')
                    ->where('id', $matchedTranslation->page_id)
                    ->firstOrFail();
            } else {
                // Fallback: hierarchical slug resolution on the pages table.
                $page = $this->resolveHierarchicalPage($slug) ?? abort(404);
            }
        }

        if (PageCacheService::isEnabled()) {
            PageCacheService::set($page, $detectedLocale);
        }

        return [$page, $detectedLocale, $matchedTranslation];
    }

    /**
     * Attempt hierarchical (parent/child) slug resolution, then plain slug lookup.
     *
     * For URLs like /servicios/ventanas/pvc-instalacion, validates the full ancestor
     * chain — not just the immediate parent — to prevent orphan pages from matching.
     */
    private function resolveHierarchicalPage(string $slug): ?Page
    {
        if (! str_contains($slug, '/')) {
            return Page::with('translations')
                ->where('slug', $slug)
                ->published()
                ->first();
        }

        // Try full path as slug first (e.g. "servicios/barandillas" stored directly).
        $fullPathMatch = Page::with('translations')
            ->where('slug', $slug)
            ->published()
            ->first();

        if ($fullPathMatch) {
            return $fullPathMatch;
        }

        // Fallback: hierarchical resolution via parent_id chain.
        $parts = array_values(array_filter(explode('/', $slug)));
        $leafSlug = array_pop($parts);

        // Eager-load up to 4 ancestor levels (covers servicios/ventanas/tipo/producto).
        $candidates = Page::with(['translations', 'parent.parent.parent.parent'])
            ->where('slug', $leafSlug)
            ->published()
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->matchesAncestorPath($candidate, $parts)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Verify that the page's ancestor slugs match the expected path exactly.
     *
     * @param  array<int, string>  $expectedPath  Ancestor slugs from root to immediate parent,
     *                                            e.g. ['servicios', 'ventanas'] for /servicios/ventanas/pvc-instalacion
     */
    private function matchesAncestorPath(Page $page, array $expectedPath): bool
    {
        if (empty($expectedPath)) {
            return $page->parent_id === null;
        }

        $current = $page;

        foreach (array_reverse($expectedPath) as $expectedSlug) {
            $parent = $current->parent; // already eager-loaded

            if (! $parent || $parent->slug !== $expectedSlug) {
                return false;
            }

            $current = $parent;
        }

        // The topmost matched ancestor must be a root page.
        return $current->parent_id === null;
    }

    /**
     * Resolve the Blade view name for the given page using the active theme.
     *
     * Checks theme-specific template views before falling back to the generic page view.
     */
    private function resolveViewName(Page|\stdClass $page): string
    {
        $template = $page->template ?: 'default';

        // Special case: homepage template maps directly to the theme index view.
        if ($template === 'homepage') {
            $homepageView = $this->templateManager->getThemeViewPath('index');
            if (view()->exists($homepageView)) {
                return $homepageView;
            }
        }

        $candidates = [
            $this->templateManager->getThemeViewPath("templates.{$template}"),
            $this->templateManager->getThemeViewPath('templates.default'),
            $this->templateManager->getThemeViewPath('page'),
        ];

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                return $candidate;
            }
        }

        $finalFallback = $this->templateManager->getThemeViewPath('page');

        if (! view()->exists($finalFallback)) {
            abort(404);
        }

        return $finalFallback;
    }

    /**
     * Build the hreflang / language-switcher links for the current page.
     *
     * @return array<string, array{url: string|null, published: bool}>
     */
    private function buildLangLinks(Page|\stdClass $page, string $prefix, string $detectedLocale): array
    {
        $pageId = $page instanceof Page ? $page->id : ($page->id ?? null);
        $supportedLocales = PageService::getSupportedLocales();
        $langLinks = [];

        if ($pageId) {
            $allTrans = ($page instanceof Page && $page->relationLoaded('translations'))
                ? $page->translations
                : PageTranslation::where('page_id', $pageId)
                    ->with('localeModel')
                    ->get(['locale_id', 'slug', 'status', 'published_at']);

            foreach ($allTrans as $trans) {
                $isPublished = $trans->status === PageStatus::Published->value
                    && $trans->published_at
                    && $trans->published_at <= now();

                $langLinks[$trans->locale] = [
                    'url' => $isPublished
                        ? url($prefix ? $prefix.'/'.$trans->slug : $trans->slug)
                        : null,
                    'published' => $isPublished,
                ];
            }
        }

        foreach ($supportedLocales as $loc) {
            $langLinks[$loc] ??= ['url' => null, 'published' => false];
        }

        return $langLinks;
    }

    /**
     * Push SEO meta data into the bound SeoService for the current page.
     *
     * @param  array<string, array{url: string|null, published: bool}>  $langLinks
     */
    private function applySeo(
        Page|\stdClass $page,
        string $detectedLocale,
        string $prefix,
        array $langLinks
    ): void {
        if (! app()->bound('seo')) {
            return;
        }

        $seo = app('seo');

        if ($page instanceof Page) {
            $page->loadMissing('translations');

            // Primary SEO source: global seo_metas record (locale = null).
            $seo->loadFromModel($page);

            // Locale-specific overlay: if a seo_metas row exists for this locale, apply it on top.
            $localeMeta = $page->seoMetaForLocale($detectedLocale);
            if ($localeMeta) {
                if (! empty($localeMeta->title)) {
                    $seo->setTitle($localeMeta->title, false);
                }
                if (! empty($localeMeta->description)) {
                    $seo->setDescription($localeMeta->description);
                }
                if (! empty($localeMeta->keywords)) {
                    $seo->setKeywords($localeMeta->keywords);
                }
                if (! empty($localeMeta->og_image)) {
                    $seo->setOgImage($localeMeta->og_image);
                }
                if (! empty($localeMeta->robots)) {
                    $seo->setRobots($localeMeta->robots);
                }
            }

            // Content fallbacks: use translation title/description if SEO fields are still empty.
            $trans = $page->translations->firstWhere('locale', $detectedLocale);

            if ($trans) {
                if (empty($seo->get('title')) && ! empty($trans->title)) {
                    $seo->setTitle($trans->title);
                }
                if (empty($seo->get('description')) && ! empty($trans->description)) {
                    $seo->setDescription($trans->description);
                }
            } elseif (! empty($page->title)) {
                if (empty($seo->get('title'))) {
                    $seo->setTitle($page->title);
                }
                if (empty($seo->get('description')) && ! empty($page->description)) {
                    $seo->setDescription($page->description);
                }
            }

            $canonicalSlug = $trans?->slug ?? $page->slug;
            $seo->setCanonical(url($prefix ? $prefix.'/'.$canonicalSlug : $canonicalSlug));
            $seo->loadSchemasFromModel($page);

            return;
        }

        // Cached stdClass page — seo_metas not available; fall back to content fields only.
        if (! empty($page->title)) {
            $seo->setTitle($page->title);
        }
        if (! empty($page->description)) {
            $seo->setDescription($page->description);
        }
        if (! empty($page->slug)) {
            $seo->setCanonical(url($prefix ? $prefix.'/'.$page->slug : $page->slug));
        }
        if (! empty($page->structured_data) && is_array($page->structured_data)) {
            $seo->addSchema($page->structured_data);
        }
    }

    /**
     * Prepare the view data array for the page view.
     *
     * @param  array<string, array{url: string|null, published: bool}>  $langLinks
     * @return array<string, mixed>
     */
    private function buildPageData(
        Page|\stdClass $page,
        string $detectedLocale,
        array $langLinks
    ): array {
        $activeTrans = ($page instanceof Page)
            ? $page->translations->firstWhere('locale', $detectedLocale)
            : null;

        $transTitle = $activeTrans?->title
            ?? ($page instanceof Page ? $page->trans('title', $detectedLocale) : null)
            ?? $page->title ?? null;

        $transDescription = $activeTrans?->description
            ?? ($page instanceof Page ? $page->trans('description', $detectedLocale) : null)
            ?? $page->description ?? null;

        $transKeywords = ($page instanceof Page ? $page->trans('keywords', $detectedLocale) : null)
            ?? $page->keywords ?? null;

        // SECURITY: $transContent is rendered unescaped ({!! $transContent !!}) in the template views.
        // Content must be sanitised at write-time (e.g. HTML Purifier) before reaching this point.
        $rawContent = ($page instanceof Page)
            ? ($page->trans('content', $detectedLocale) ?? $page->content)
            : ($page->content ?? null);

        $transContent = (function_exists('shortcode') && $rawContent !== null)
            ? shortcode($rawContent)
            : ($activeTrans?->content ?? $rawContent);

        $featuredImage = $page->featured_image
            ?: (($page instanceof Page && $page->seoMeta?->og_image) ? $page->seoMeta->og_image : null);

        $canonicalUrl = ! empty($langLinks[$detectedLocale]['url'])
            ? $langLinks[$detectedLocale]['url']
            : url()->current();

        $xDefaultUrl = $this->resolveXDefaultUrl($langLinks);

        return compact(
            'page',
            'transTitle', 'transDescription', 'transKeywords', 'transContent',
            'canonicalUrl', 'xDefaultUrl', 'featuredImage',
            'langLinks', 'detectedLocale',
        );
    }

    /**
     * Resolve the x-default hreflang URL (prefers 'es', then first published locale).
     *
     * @param  array<string, array{url: string|null, published: bool}>  $langLinks
     */
    private function resolveXDefaultUrl(array $langLinks): ?string
    {
        if (! empty($langLinks['es']['url']) && $langLinks['es']['published']) {
            return $langLinks['es']['url'];
        }

        foreach ($langLinks as $info) {
            if (! empty($info['url']) && $info['published']) {
                return $info['url'];
            }
        }

        return null;
    }

    /**
     * Increment the views_count counter and record granular analytics.
     * Deduplicates the views_count increment per IP per 24 hours.
     */
    private function trackView(Page|\stdClass $page): void
    {
        if (! ($page instanceof Page)) {
            return;
        }

        $pageId = $page->id;
        $ip = request()->ip();
        $viewKey = 'page_view:'.$pageId.':'.md5($ip);
        $isNewView = ! Cache::has($viewKey);

        if ($isNewView) {
            Cache::put($viewKey, 1, now()->addHours(24));

            dispatch(function () use ($pageId) {
                Page::where('id', $pageId)->increment('views_count');
            })->afterResponse();
        }

        RecordPageViewJob::dispatch(
            pageId: $pageId,
            ip: $ip,
            userAgent: request()->userAgent() ?? '',
            referrer: request()->header('referer', ''),
            locale: app()->getLocale(),
            sessionId: session()->getId(),
        );
    }
}
