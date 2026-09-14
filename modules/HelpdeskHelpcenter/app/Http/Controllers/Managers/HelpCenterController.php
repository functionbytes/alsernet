<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\HelpdeskHelpcenter\Concerns\BuildsFulltextSearch;
use Modules\HelpdeskHelpcenter\Http\Controllers\SitemapController;
use Modules\HelpdeskHelpcenter\Http\Requests\StoreHelpCenterArticleRequest;
use Modules\HelpdeskHelpcenter\Http\Requests\StoreHelpCenterCategoryRequest;
use Modules\HelpdeskHelpcenter\Http\Requests\StoreHelpCenterSectionRequest;
use Modules\HelpdeskHelpcenter\Http\Requests\UpdateHelpCenterArticleRequest;
use Modules\HelpdeskHelpcenter\Http\Requests\UpdateHelpCenterCategoryRequest;
use Modules\HelpdeskHelpcenter\Http\Requests\UpdateHelpCenterSectionRequest;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;
use Modules\HelpdeskHelpcenter\Models\HelpCenterTag;
use Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService;
use Spatie\Permission\Models\Role;

class HelpCenterController extends Controller
{
    use BuildsFulltextSearch;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', HelpCenterCategory::class);

        abort_if(! helpdesk_helpcenter_enabled(), 404);

        $query = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->where('is_section', false)
            ->with(['sections' => function ($q) {
                $q->withCount('articles')->orderBy('position');
            }])
            ->withCount(['sections', 'articles']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('visible_to_role')) {
            $query->where('visible_to_role', $request->visible_to_role);
        }

        // Una categoria vacia es la unica que se puede borrar, asi que
        // encontrarlas de un vistazo evita ir probando una a una.
        if ($request->filled('content')) {
            $request->content === 'empty'
                ? $query->doesntHave('articles')->doesntHave('sections')
                : $query->where(fn ($q) => $q->has('articles')->orHas('sections'));
        }

        $categories = $query->orderBy('position', 'asc')
            ->paginate(config('helpdeskhelpcenter.pagination.managers', 20))
            ->withQueryString();

        $stats = [
            'total_categories' => HelpCenterCategory::query()->whereNull('parent_id')->where('is_section', false)->count(),
            'total_sections' => HelpCenterCategory::query()->where('is_section', true)->count(),
            'total_articles' => HelpCenterArticle::query()->count(),
            'published_articles' => HelpCenterArticle::query()->where('draft', false)->count(),
        ];

        $roles = HelpCenterCategory::query()
            ->whereNotNull('visible_to_role')
            ->distinct()
            ->orderBy('visible_to_role')
            ->pluck('visible_to_role');

        return view('helpdeskhelpcenter::helpcenter.categories.index', compact('categories', 'stats', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', HelpCenterCategory::class);

        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdeskhelpcenter::helpcenter.categories.create', compact('roles'));
    }

    public function store(StoreHelpCenterCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['position'] = HelpCenterCategory::whereNull('parent_id')->max('position') + 1;
        $validated['is_section'] = false;
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        HelpCenterCategory::create($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'redirect' => route('manager.helpcenter.categories'),
        ]);
    }

    public function edit(int $id): View
    {
        $category = HelpCenterCategory::findOrFail($id);
        $this->authorize('update', $category);

        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdeskhelpcenter::helpcenter.categories.edit', compact('category', 'roles'));
    }

    public function update(UpdateHelpCenterCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = HelpCenterCategory::findOrFail($validated['id']);
        $this->authorize('update', $category);

        $category->update($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'redirect' => route('manager.helpcenter.categories'),
        ]);
    }

    public function showCategory(int $id): View
    {
        $category = HelpCenterCategory::with(['sections' => function ($q) {
            $q->withCount('articles')->orderBy('position');
        }])
            ->withCount(['sections', 'articles'])
            ->findOrFail($id);

        $this->authorize('view', $category);

        return view('helpdeskhelpcenter::helpcenter.categories.show', compact('category'));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = HelpCenterCategory::findOrFail($id);
        $this->authorize('delete', $category);

        if ($category->sections()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría que contiene secciones',
            ], 422);
        }

        if ($category->articles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría que contiene artículos',
            ], 422);
        }

        $category->delete();
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente',
        ]);
    }

    /**
     * Borrado en lote de categorias.
     *
     * Respeta la misma proteccion que destroy(): una categoria con secciones o
     * articulos no se borra, se omite. Un lote no puede ser la via para saltarse
     * lo que la accion individual impide.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = HelpCenterCategory::whereIn('id', $validated['ids'])->get();
        $count = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            if (! auth()->user()->can('delete', $category)) {
                $skipped++;

                continue;
            }

            if ($category->sections()->count() > 0 || $category->articles()->count() > 0) {
                $skipped++;

                continue;
            }

            $category->delete();
            $count++;
        }

        $this->clearWidgetCache();

        $message = $count.' categoria(s) eliminada(s).';

        if ($skipped > 0) {
            $message .= ' '.$skipped.' omitida(s) por tener contenido o por permisos.';
        }

        return response()->json(['message' => $message, 'count' => $count, 'skipped' => $skipped]);
    }

    /**
     * Publicar, pasar a borrador o eliminar varios articulos a la vez.
     */
    public function articlesBulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:publish,draft,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $articles = HelpCenterArticle::whereIn('id', $validated['ids'])->get();
        $count = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            $ability = $validated['action'] === 'delete' ? 'delete' : 'update';

            if (! auth()->user()->can($ability, $article)) {
                $skipped++;

                continue;
            }

            match ($validated['action']) {
                'delete' => $article->delete(),
                'publish' => $article->update(['draft' => false, 'published_at' => $article->published_at ?? now()]),
                'draft' => $article->update(['draft' => true]),
            };

            $count++;
        }

        $this->clearWidgetCache();

        $labels = ['delete' => 'eliminado(s)', 'publish' => 'publicado(s)', 'draft' => 'pasado(s) a borrador'];
        $message = $count.' articulo(s) '.$labels[$validated['action']].'.';

        if ($skipped > 0) {
            $message .= ' '.$skipped.' omitido(s) por permisos.';
        }

        return response()->json(['message' => $message, 'count' => $count, 'skipped' => $skipped]);
    }

    public function createSection(Request $request): View
    {
        $this->authorize('create', HelpCenterCategory::class);

        $categories = HelpCenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('name', 'asc')
            ->get();

        $parentId = $request->get('parent_id');
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdeskhelpcenter::helpcenter.sections.create', compact('categories', 'parentId', 'roles'));
    }

    public function storeSection(StoreHelpCenterSectionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['position'] = HelpCenterCategory::where('parent_id', $validated['parent_id'])->max('position') + 1;
        $validated['is_section'] = true;
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        HelpCenterCategory::create($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Sección creada exitosamente',
            'redirect' => route('manager.helpcenter.categories'),
        ]);
    }

    public function editSection(int $id): View
    {
        $section = HelpCenterCategory::findOrFail($id);
        $this->authorize('update', $section);

        $categories = HelpCenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('name', 'asc')
            ->get();
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdeskhelpcenter::helpcenter.sections.edit', compact('section', 'categories', 'roles'));
    }

    public function updateSection(UpdateHelpCenterSectionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $section = HelpCenterCategory::findOrFail($validated['id']);
        $this->authorize('update', $section);

        $section->update($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Sección actualizada exitosamente',
            'redirect' => route('manager.helpcenter.categories'),
        ]);
    }

    public function showSection(int $id): View
    {
        $section = HelpCenterCategory::with(['parent', 'articles.author'])
            ->withCount('articles')
            ->findOrFail($id);

        $this->authorize('view', $section);

        return view('helpdeskhelpcenter::helpcenter.sections.show', compact('section'));
    }

    public function destroySection(int $id): JsonResponse
    {
        $section = HelpCenterCategory::findOrFail($id);
        $this->authorize('delete', $section);

        if ($section->articles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una sección que contiene artículos',
            ], 422);
        }

        $section->delete();
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Sección eliminada exitosamente',
        ]);
    }

    public function createArticleInSection(int $id): View
    {
        $this->authorize('create', HelpCenterArticle::class);

        $section = HelpCenterCategory::with('parent')->findOrFail($id);
        $sections = HelpCenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('helpdeskhelpcenter::helpcenter.articles.create', compact('sections', 'section'));
    }

    public function articlesIndex(Request $request): View
    {
        $this->authorize('viewAny', HelpCenterArticle::class);

        $query = HelpCenterArticle::query()->with(['categories', 'author']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('draft')) {
            $query->where('draft', $request->draft);
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('categories', fn ($q) => $q->where('helpdesk_helpcenter_categories.id', $categoryId));
        }

        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }

        $articles = $query->orderBy('created_at', 'desc')
            ->paginate(config('helpdeskhelpcenter.pagination.managers', 20))
            ->withQueryString();

        $articleStats = HelpCenterArticle::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN draft = 0 THEN 1 ELSE 0 END) as published')
            ->selectRaw('SUM(CASE WHEN draft = 1 THEN 1 ELSE 0 END) as drafts')
            ->selectRaw('COALESCE(SUM(views_count), 0) as total_views')
            ->first();

        $stats = [
            'total' => (int) ($articleStats->total ?? 0),
            'published' => (int) ($articleStats->published ?? 0),
            'drafts' => (int) ($articleStats->drafts ?? 0),
            'total_views' => (int) ($articleStats->total_views ?? 0),
        ];

        $categories = HelpCenterCategory::query()->orderBy('name')->get(['id', 'name']);

        $authors = User::query()
            ->whereIn('id', HelpCenterArticle::query()->whereNotNull('author_id')->distinct()->pluck('author_id'))
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname', 'email']);

        return view('helpdeskhelpcenter::helpcenter.articles.index', compact('articles', 'stats', 'categories', 'authors'));
    }

    public function createArticle(): View
    {
        $this->authorize('create', HelpCenterArticle::class);

        $sections = HelpCenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('helpdeskhelpcenter::helpcenter.articles.create', compact('sections'));
    }

    public function storeArticle(StoreHelpCenterArticleRequest $request): JsonResponse
    {
        $this->authorize('create', HelpCenterArticle::class);

        $validated = $request->validated();

        DB::connection('helpdesk')->transaction(function () use ($request, $validated, &$article) {
            $isDraft = $request->boolean('draft');
            $isPublished = ! $isDraft;

            $article = HelpCenterArticle::create([
                'title' => $validated['title'],
                'body' => $validated['body'] ?? '',
                'description' => $validated['description'] ?? '',
                'meta_description' => $validated['meta_description'] ?? '',
                'position' => $validated['position'] ?? 0,
                'draft' => $isDraft,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
                'hide_from_structure' => $request->boolean('hide_from_structure'),
                'author_id' => auth()->id(),
            ]);

            if ($request->hasFile('featured_image')) {
                $article->addMediaFromRequest('featured_image')
                    ->toMediaCollection('featured_image');
            }

            if ($request->filled('tags')) {
                $tagIds = collect($validated['tags'])->map(fn ($name) => HelpCenterTag::findOrCreateByName($name)->id)->all();
                $article->tags()->sync($tagIds);
            }

            $categoryPosition = (int) $article->categories()
                ->where('category_id', $validated['section_id'])
                ->max('helpdesk_helpcenter_category_article.position') + 1;

            $article->categories()->attach($validated['section_id'], ['position' => $categoryPosition]);
        });

        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Artículo creado exitosamente',
            'redirect' => route('manager.helpcenter.articles'),
        ]);
    }

    public function editArticle(int $id): View
    {
        $article = HelpCenterArticle::with(['categories', 'tags'])->findOrFail($id);
        $this->authorize('update', $article);

        $sections = HelpCenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('helpdeskhelpcenter::helpcenter.articles.edit', compact('article', 'sections'));
    }

    public function updateArticle(UpdateHelpCenterArticleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $article = HelpCenterArticle::findOrFail($validated['id']);
        $this->authorize('update', $article);

        DB::connection('helpdesk')->transaction(function () use ($request, $validated, $article) {
            $isDraft = $request->boolean('draft');
            $isPublished = ! $isDraft;
            $publishedAt = match (true) {
                $isPublished && $article->published_at !== null => $article->published_at,
                $isPublished => now(),
                default => null,
            };

            $article->update([
                'title' => $validated['title'],
                'body' => $validated['body'] ?? '',
                'description' => $validated['description'] ?? '',
                'meta_description' => $validated['meta_description'] ?? '',
                'position' => $validated['position'] ?? $article->position,
                'draft' => $isDraft,
                'is_published' => $isPublished,
                'published_at' => $publishedAt,
                'hide_from_structure' => $request->boolean('hide_from_structure'),
            ]);

            if ($request->hasFile('featured_image')) {
                $article->clearMediaCollection('featured_image');
                $article->addMediaFromRequest('featured_image')
                    ->toMediaCollection('featured_image');
            }

            if ($request->has('tags')) {
                $tagIds = $request->filled('tags')
                    ? collect($validated['tags'])->map(fn ($name) => HelpCenterTag::findOrCreateByName($name)->id)->all()
                    : [];

                $article->tags()->sync($tagIds);
            }

            $currentPivot = $article->categories()->first();
            $pivotPosition = $currentPivot?->pivot->position ?? 0;

            $article->categories()->sync([
                $validated['section_id'] => ['position' => $pivotPosition],
            ]);
        });

        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Artículo actualizado exitosamente',
            'redirect' => route('manager.helpcenter.articles'),
        ]);
    }

    public function destroyArticle(int $id): JsonResponse
    {
        $article = HelpCenterArticle::findOrFail($id);
        $this->authorize('delete', $article);

        $article->delete();
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Artículo eliminado exitosamente',
        ]);
    }

    public function searchArticles(Request $request): JsonResponse
    {
        if (! helpdesk_helpcenter_enabled()) {
            return response()->json(['data' => []]);
        }

        $this->authorize('viewAny', HelpCenterArticle::class);

        $q = $request->get('q', '');

        $articles = HelpCenterArticle::query()
            ->where('is_published', true)
            ->when($q, function ($query) use ($q) {
                $booleanTerm = $this->buildBooleanTerm($q);

                if ($booleanTerm !== null) {
                    // FULLTEXT (title, body) index; LIKE solo se combina en tests
                    // (shouldFallbackToLikeSearch) — en producción anulaba el
                    // índice en cada búsqueda del listado de artículos.
                    $query->where(fn ($sub) => $sub
                        ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm])
                        ->when($this->shouldFallbackToLikeSearch(), fn ($sw) => $sw
                            ->orWhere('title', 'like', "%{$q}%")
                            ->orWhere('body', 'like', "%{$q}%")))
                        ->orderByRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm]);

                    return;
                }

                $query->where(fn ($sub) => $sub
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%"));
            })
            ->select('id', 'title', 'slug', 'body')
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'excerpt' => Str::limit(strip_tags($a->body ?? ''), 100),
                'url' => route('public.helpcenter.show', $a->slug),
            ]);

        return response()->json(['data' => $articles]);
    }

    public function apiCategories(): JsonResponse
    {
        $this->authorize('viewAny', HelpCenterCategory::class);

        $categories = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->where('is_section', false)
            ->withCount(['sections', 'articles'])
            ->orderBy('position')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'is_section' => false,
                'sections_count' => $c->sections_count,
                'articles_count' => $c->articles_count,
            ]);

        return response()->json(['categories' => $categories]);
    }

    public function apiSections(int $categoryId): JsonResponse
    {
        $this->authorize('viewAny', HelpCenterCategory::class);

        $sections = HelpCenterCategory::query()
            ->where('parent_id', $categoryId)
            ->where('is_section', true)
            ->withCount('articles')
            ->orderBy('position')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'is_section' => true,
                'articles_count' => $s->articles_count,
            ]);

        return response()->json(['categories' => $sections]);
    }

    public function apiSectionArticles(int $sectionId): JsonResponse
    {
        $this->authorize('viewAny', HelpCenterArticle::class);

        $articles = HelpCenterArticle::query()
            ->whereHas('categories', fn ($q) => $q->where('helpdesk_helpcenter_categories.id', $sectionId))
            ->select('id', 'title', 'draft', 'views_count', 'position')
            ->orderBy('position')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'draft' => $a->draft,
                'views' => $a->views_count ?? 0,
            ]);

        return response()->json(['articles' => $articles]);
    }

    public function apiReorderCategories(Request $request): JsonResponse
    {
        $this->authorize('manage', HelpCenterCategory::class);

        $ids = $request->input('ids', []);
        DB::connection('helpdesk')->transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                HelpCenterCategory::where('id', $id)->update(['position' => $position]);
            }
        });
        $this->clearWidgetCache();

        return response()->json(['success' => true]);
    }

    public function apiReorderArticles(Request $request, int $sectionId): JsonResponse
    {
        $this->authorize('manage', HelpCenterArticle::class);

        $ids = $request->input('ids', []);
        DB::connection('helpdesk')->transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                HelpCenterArticle::where('id', $id)->update(['position' => $position]);
            }
        });
        $this->clearWidgetCache();

        return response()->json(['success' => true]);
    }

    private function clearWidgetCache(): void
    {
        // La clave que realmente lee HelpcenterWidgetService::getWidgetData()
        // (antes se incrementaba una versión 'helpdesk:widget:version' que nadie
        // leía, y el widget servía el payload cacheado hasta 1h después de un
        // cambio en categorías/artículos).
        Cache::forget(HelpcenterWidgetService::WIDGET_CACHE_KEY);

        // El sitemap público cachea la misma fuente (artículos publicados) con
        // el mismo TTL de 1h; invalidarlo aquí mantiene ambos en sync.
        Cache::forget(SitemapController::CACHE_KEY);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (HelpCenterCategory::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
