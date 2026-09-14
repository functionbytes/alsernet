<?php

namespace Modules\HelpdeskHelpcenter\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Concerns\BuildsFulltextSearch;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleVote;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;

class HelpcenterWidgetService
{
    use BuildsFulltextSearch;

    /**
     * Cache key for the (relatively expensive) widget payload. Invalidated by the
     * article/translation observers whenever content that feeds it changes.
     */
    public const WIDGET_CACHE_KEY = 'helpdeskhelpcenter:widget_data';

    /**
     * @return array{categories: array<int, array<string, mixed>>, popular_articles: array<int, array<string, mixed>>, articles: array<int, array<string, mixed>>}
     */
    public function getWidgetData(): array
    {
        return Cache::remember(self::WIDGET_CACHE_KEY, now()->addHour(), fn (): array => $this->buildWidgetData());
    }

    /**
     * @return array{categories: array<int, array<string, mixed>>, popular_articles: array<int, array<string, mixed>>, articles: array<int, array<string, mixed>>}
     */
    private function buildWidgetData(): array
    {
        // Count published+active articles per pivot category_id (any depth).
        // Articles created via the manager have category_id=NULL (pivot manages associations),
        // so we must count via the helpdesk_helpcenter_category_article pivot, not category_id.
        // This payload is cached globally (WIDGET_CACHE_KEY, shared across every
        // visitor for up to 1h), so it can never vary per-user: role-restricted
        // articles are always excluded here, same as an anonymous visitor.
        $pivotCounts = HelpCenterCategory::query()
            ->withCount(['articles as published_count' => fn ($q) => $q
                ->published()
                ->visibleToRole(null)])
            ->get()
            ->pluck('published_count', 'id');

        // For root categories aggregate direct count + all children (sections) counts.
        $categories = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->with('sections:id,parent_id')
            ->orderBy('position')
            ->get()
            ->map(function (HelpCenterCategory $c) use ($pivotCounts) {
                $sectionIds = $c->sections->pluck('id');
                $count = (int) ($pivotCounts[$c->id] ?? 0)
                    + $sectionIds->sum(fn (int $id) => (int) ($pivotCounts[$id] ?? 0));

                return [
                    'id' => (string) $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug ?? null,
                    'icon' => $c->icon ?? null,
                    'count' => $count,
                ];
            })
            ->values()
            ->all();

        $popular = HelpCenterArticle::query()
            ->published()
            ->visibleToRole(null)
            ->with('categories.parent')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get()
            ->map(fn (HelpCenterArticle $a) => $this->articleToArray($a))
            ->values()
            ->all();

        $articles = HelpCenterArticle::query()
            ->published()
            ->visibleToRole(null)
            ->with('categories.parent')
            ->orderByDesc('views_count')
            ->limit(50)
            ->get()
            ->map(fn (HelpCenterArticle $a) => $this->articleToArray($a))
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'popular_articles' => $popular,
            'articles' => $articles,
        ];
    }

    /**
     * @return array{id: string, title: string, slug: string, excerpt: string, url: string}[]
     */
    /**
     * Busca artículos: primero por significado, y si eso no da nada, por texto.
     *
     * El módulo tenía búsqueda semántica construida (EmbeddingsService::search,
     * con troceado y coseno) y este widget —que alimenta el buscador público,
     * los artículos sugeridos al agente y la deflexión del portal— no la usaba:
     * iba por fulltext de MySQL y LIKE. Es decir, quien preguntaba «no me deja
     * pagar» no encontraba el artículo titulado «Errores al finalizar la
     * compra», porque no comparten ni una palabra.
     *
     * El literal se conserva como respaldo, no por nostalgia: sin clave de
     * embeddings configurada, o cuando el corpus todavía no está indexado, es
     * lo único que hay. Y para una búsqueda por título exacto sigue siendo
     * mejor.
     */
    public function searchArticles(string $query, string $locale = ''): array
    {
        $semantic = $this->searchArticlesSemantically($query, $locale);

        if ($semantic !== []) {
            return $semantic;
        }

        return $this->searchArticlesLiterally($query, $locale);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchArticlesSemantically(string $query, string $locale): array
    {
        if (! config('helpdeskhelpcenter.semantic_search', true) || ! class_exists(EmbeddingsService::class)) {
            return [];
        }

        try {
            $hits = app(EmbeddingsService::class)->search($query, 10, $locale ?: null);
        } catch (\Throwable $e) {
            Log::warning('HelpcenterWidgetService: búsqueda semántica no disponible', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $minSimilarity = (float) config('helpdeskhelpcenter.semantic_min_similarity', 0.75);

        // Un artículo puede aparecer en varios trozos: se queda el mejor.
        $ranked = collect($hits)
            ->filter(fn (array $h) => ($h['similarity'] ?? 0) >= $minSimilarity)
            ->groupBy('article_id')
            ->map(fn ($group) => $group->max('similarity'))
            ->sortDesc();

        if ($ranked->isEmpty()) {
            return [];
        }

        $articles = HelpCenterArticle::query()
            ->whereIn('id', $ranked->keys())
            ->published()
            ->visibleToRole(null)
            ->get()
            ->keyBy('id');

        return $ranked->keys()
            ->filter(fn ($id) => $articles->has($id))
            ->map(fn ($id) => $this->mapArticle($articles->get($id)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchArticlesLiterally(string $query, string $locale = ''): array
    {
        $booleanTerm = $this->buildBooleanTerm($query);

        $builder = HelpCenterArticle::query()
            ->published()
            ->visibleToRole(null)
            ->where(fn ($q) => $q
                ->when($booleanTerm !== null, fn ($qb) => $qb
                    ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm]))
                ->when($booleanTerm === null || $this->shouldFallbackToLikeSearch(), fn ($qb) => $qb
                    ->orWhere('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhereHas('translations', fn ($qt) => $qt
                        ->when($locale, fn ($ql) => $ql->where('locale', $locale))
                        ->where(fn ($qtt) => $qtt->where('title', 'like', "%{$query}%")->orWhere('body', 'like', "%{$query}%"))
                    )
                )
            )
            ->when($booleanTerm !== null, fn ($q) => $q
                ->orderByRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm]))
            ->limit(10)
            ->get();

        return $builder
            ->map(fn (HelpCenterArticle $a) => $this->mapArticle($a))
            ->values()
            ->all();
    }

    public function getArticle(int $id): ?array
    {
        $article = HelpCenterArticle::query()
            ->published()
            ->visibleToRole(null)
            ->with('categories.parent')
            ->find($id);

        if (! $article) {
            return null;
        }

        $article->incrementQuietly('views_count');

        [$category, $section] = $this->resolveCategorySection($article);

        return [
            'id' => (string) $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            // Sanitizado con HTMLPurifier (clean_html()) igual que la vista pública:
            // el widget lo renderiza con dangerouslySetInnerHTML, así que sin
            // esto un editor podría inyectar XSS almacenado en cualquier sitio
            // que embeba el widget.
            'body' => clean_html($article->content ?? $article->body ?? ''),
            'description' => $article->excerpt ?? $article->description ?? '',
            'category' => $category,
            'section' => $section,
            'helpful_count' => (int) ($article->helpful_count ?? 0),
            'unhelpful_count' => (int) ($article->unhelpful_count ?? 0),
        ];
    }

    /**
     * Registra el feedback del widget creando/actualizando una fila real de
     * voto (HelpCenterArticleVote), en vez de un increment() directo. Antes el
     * increment quedaba huérfano y el observer de votos —que recalcula los
     * contadores contando filas reales cuando alguien vota desde la web— lo
     * sobrescribía, perdiendo el feedback del widget. Ahora ambos caminos
     * escriben en la misma tabla y el observer mantiene los contadores.
     */
    public function recordFeedback(int $id, bool $helpful, ?string $cookieId = null, ?string $ipHash = null): bool
    {
        $article = HelpCenterArticle::query()
            ->where('id', $id)
            ->where('is_published', true)
            ->first();

        if (! $article) {
            return false;
        }

        $voteValue = $helpful ? 1 : -1;

        // Misma identidad que ArticleVoteController: cookie_id es la clave real
        // del votante, ip_hash solo bloquea un segundo voto tras borrar la
        // cookie — nunca se usa para localizar/editar el voto de otro
        // visitante detrás de la misma IP.
        $existing = $cookieId
            ? HelpCenterArticleVote::query()
                ->where('article_id', $article->id)
                ->where('cookie_id', $cookieId)
                ->first()
            : null;

        if ($existing) {
            // El observer recalcula solo si cambia; forzar saved() con update.
            $existing->update(['vote' => $voteValue]);

            return true;
        }

        $ipAlreadyVoted = $ipHash && HelpCenterArticleVote::query()
            ->where('article_id', $article->id)
            ->where('ip_hash', $ipHash)
            ->exists();

        if ($ipAlreadyVoted) {
            return true;
        }

        try {
            HelpCenterArticleVote::create([
                'article_id' => $article->id,
                'cookie_id' => $cookieId,
                'ip_hash' => $ipHash,
                'vote' => $voteValue,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Insert concurrente con la misma clave única (mismo cookie_id o
            // ip_hash) — el voto ya quedó registrado por la otra petición.
        }

        return true;
    }

    /**
     * Forma del resultado, compartida por la vía semántica y la literal: si
     * cada una devolviera claves distintas, quien las consume (widget público,
     * artículos sugeridos al agente, deflexión del portal) rompería según cuál
     * de las dos hubiera acertado.
     *
     * @return array{id: string, title: string, slug: string, excerpt: string, url: string}
     */
    private function mapArticle(HelpCenterArticle $a): array
    {
        return [
            'id' => (string) $a->id,
            'title' => $a->title,
            'slug' => $a->slug,
            'excerpt' => $a->excerpt ?? Str::limit(strip_tags($a->content ?? $a->body ?? ''), 100),
            'url' => route('public.helpcenter.show', $a->slug),
        ];
    }

    private function articleToArray(HelpCenterArticle $a): array
    {
        [$category, $section] = $this->resolveCategorySection($a);

        return [
            'id' => (string) $a->id,
            'title' => $a->title,
            'excerpt' => $a->excerpt ?? Str::limit(strip_tags($a->content ?? $a->body ?? ''), 100),
            'category' => $category,
            'section' => $section,
        ];
    }

    /**
     * Resolves category and section names from the article's first associated category.
     *
     * If the first category has `is_section = true`, it is treated as a section
     * whose parent is the category. Otherwise it is treated as the category directly.
     *
     * @return array{0: string|null, 1: string|null} [$category, $section]
     */
    private function resolveCategorySection(HelpCenterArticle $article): array
    {
        $first = $article->categories->first();

        if (! $first) {
            return [null, null];
        }

        if ($first->is_section) {
            return [$first->parent?->name, $first->name];
        }

        return [$first->name, null];
    }
}
