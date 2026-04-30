<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\StoreHelpCenterArticleRequest;
use Modules\Helpdesk\Http\Requests\StoreHelpCenterCategoryRequest;
use Modules\Helpdesk\Http\Requests\StoreHelpCenterSectionRequest;
use Modules\Helpdesk\Http\Requests\UpdateHelpCenterArticleRequest;
use Modules\Helpdesk\Http\Requests\UpdateHelpCenterCategoryRequest;
use Modules\Helpdesk\Http\Requests\UpdateHelpCenterSectionRequest;
use Modules\Helpdesk\Models\HelpCenterArticle;
use Modules\Helpdesk\Models\HelpCenterCategory;
use Modules\Helpdesk\Models\HelpCenterTag;
use Spatie\Permission\Models\Role;

class HelpCenterController extends Controller
{
    /**
     * Display categories index page
     */
    public function index(Request $request): View
    {
        $query = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->where('is_section', false)
            ->with(['sections' => function ($query) {
                $query->withCount('articles')->orderBy('position');
            }])
            ->withCount(['sections', 'articles']);

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $categories = $query->orderBy('position', 'asc')->paginate(20);

        return view('helpdesk::managers.helpcenter.categories.index', compact('categories'));
    }

    /**
     * Show create category form
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdesk::managers.helpcenter.categories.create', compact('roles'));
    }

    /**
     * Store new category
     */
    private function clearWidgetCache(): void
    {
        Cache::increment('helpdesk:widget:version');
    }

    public function store(StoreHelpCenterCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $position = HelpCenterCategory::whereNull('parent_id')->max('position') + 1;
        $validated['position'] = $position;
        $validated['is_section'] = false;

        $category = HelpCenterCategory::create($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'redirect' => route('manager.helpdesk.helpcenter.categories'),
        ]);
    }

    /**
     * Show edit category form
     */
    public function edit(int $id): View
    {
        $category = HelpCenterCategory::findOrFail($id);
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdesk::managers.helpcenter.categories.edit', compact('category', 'roles'));
    }

    /**
     * Update category
     */
    public function update(UpdateHelpCenterCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = HelpCenterCategory::findOrFail($validated['id']);
        $category->update($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'redirect' => route('manager.helpdesk.helpcenter.categories'),
        ]);
    }

    /**
     * Show category with its sections
     */
    public function showCategory(int $id): View
    {
        $category = HelpCenterCategory::with(['sections' => function ($query) {
            $query->withCount('articles')->orderBy('position');
        }])
            ->withCount(['sections', 'articles'])
            ->findOrFail($id);

        return view('helpdesk::managers.helpcenter.categories.show', compact('category'));
    }

    /**
     * Delete category
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('manage_helpdesk');

        $category = HelpCenterCategory::findOrFail($id);

        // Check if category has sections
        if ($category->sections()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría que contiene secciones',
            ], 422);
        }

        // Check if category has articles
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
     * Show create section form
     */
    public function createSection(Request $request): View
    {
        $categories = HelpCenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('name', 'asc')
            ->get();

        $parentId = $request->get('parent_id');
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdesk::managers.helpcenter.sections.create', compact('categories', 'parentId', 'roles'));
    }

    /**
     * Store new section
     */
    public function storeSection(StoreHelpCenterSectionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $position = HelpCenterCategory::where('parent_id', $validated['parent_id'])->max('position') + 1;
        $validated['position'] = $position;
        $validated['is_section'] = true;

        $section = HelpCenterCategory::create($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Sección creada exitosamente',
            'redirect' => route('manager.helpdesk.helpcenter.categories'),
        ]);
    }

    /**
     * Show edit section form
     */
    public function editSection(int $id): View
    {
        $section = HelpCenterCategory::findOrFail($id);
        $categories = HelpCenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('name', 'asc')
            ->get();
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('helpdesk::managers.helpcenter.sections.edit', compact('section', 'categories', 'roles'));
    }

    /**
     * Update section
     */
    public function updateSection(UpdateHelpCenterSectionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $section = HelpCenterCategory::findOrFail($validated['id']);
        $section->update($validated);
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Sección actualizada exitosamente',
            'redirect' => route('manager.helpdesk.helpcenter.categories'),
        ]);
    }

    /**
     * Show section with its articles
     */
    public function showSection(int $id): View
    {
        $section = HelpCenterCategory::with(['parent', 'articles.author'])
            ->withCount('articles')
            ->findOrFail($id);

        return view('helpdesk::managers.helpcenter.sections.show', compact('section'));
    }

    /**
     * Delete section
     */
    public function destroySection(int $id): JsonResponse
    {
        $this->authorize('manage_helpdesk');

        $section = HelpCenterCategory::findOrFail($id);

        // Check if section has articles
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

    /**
     * Show create article form in section context
     */
    public function createArticleInSection(int $id): View
    {
        $section = HelpCenterCategory::with('parent')->findOrFail($id);
        $sections = HelpCenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('helpdesk::managers.helpcenter.articles.create', compact('sections', 'section'));
    }

    /**
     * Display articles index page
     */
    public function articlesIndex(Request $request): View
    {
        $query = HelpCenterArticle::with(['categories', 'author']);

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // Filter by draft status
        if ($request->filled('draft')) {
            $query->where('draft', $request->draft);
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('helpdesk::managers.helpcenter.articles.index', compact('articles'));
    }

    /**
     * Show create article form
     */
    public function createArticle(): View
    {
        $sections = HelpCenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('helpdesk::managers.helpcenter.articles.create', compact('sections'));
    }

    /**
     * Store new article
     */
    public function storeArticle(StoreHelpCenterArticleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $article = HelpCenterArticle::create([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
            'description' => $validated['description'] ?? '',
            'meta_description' => $validated['meta_description'] ?? '',
            'position' => $validated['position'] ?? 0,
            'draft' => $request->has('draft') ? true : false,
            'hide_from_structure' => $request->has('hide_from_structure') ? true : false,
            'author_id' => auth()->id(),
        ]);
        $this->clearWidgetCache();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $article->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        // Handle tags
        if ($request->filled('tags')) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                $tag = HelpCenterTag::findOrCreateByName($tagName);
                $tagIds[] = $tag->id;
            }
            $article->tags()->sync($tagIds);
        }

        // Attach to section with position
        $categoryPosition = (int) $article->categories()
            ->where('category_id', $validated['section_id'])
            ->max('helpdesk_helpcenter_category_article.position') + 1;

        $article->categories()->attach($validated['section_id'], ['position' => $categoryPosition]);

        return response()->json([
            'success' => true,
            'message' => 'Artículo creado exitosamente',
            'redirect' => route('manager.helpdesk.helpcenter.articles'),
        ]);
    }

    /**
     * Show edit article form
     */
    public function editArticle(int $id): View
    {
        $article = HelpCenterArticle::with(['categories', 'tags'])->findOrFail($id);
        $sections = HelpCenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('helpdesk::managers.helpcenter.articles.edit', compact('article', 'sections'));
    }

    /**
     * Update article
     */
    public function updateArticle(UpdateHelpCenterArticleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $article = HelpCenterArticle::findOrFail($validated['id']);
        $article->update([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
            'description' => $validated['description'] ?? '',
            'meta_description' => $validated['meta_description'] ?? '',
            'position' => $validated['position'] ?? $article->position,
            'draft' => $request->has('draft') ? true : false,
            'hide_from_structure' => $request->has('hide_from_structure') ? true : false,
        ]);
        $this->clearWidgetCache();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $article->clearMediaCollection('featured_image');
            $article->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        // Handle tags
        if ($request->has('tags')) {
            if ($request->filled('tags')) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = HelpCenterTag::findOrCreateByName($tagName);
                    $tagIds[] = $tag->id;
                }
                $article->tags()->sync($tagIds);
            } else {
                $article->tags()->sync([]);
            }
        }

        // Update section association - preserve existing pivot position
        $currentPivot = $article->categories()->first();
        $pivotPosition = $currentPivot ? $currentPivot->pivot->position : 0;

        $article->categories()->sync([
            $validated['section_id'] => [
                'position' => $pivotPosition,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artículo actualizado exitosamente',
            'redirect' => route('manager.helpdesk.helpcenter.articles'),
        ]);
    }

    /**
     * Delete article
     */
    public function destroyArticle(int $id): JsonResponse
    {
        $this->authorize('manage_helpdesk');

        $article = HelpCenterArticle::findOrFail($id);
        $article->delete();
        $this->clearWidgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Artículo eliminado exitosamente',
        ]);
    }

    /**
     * API endpoint for widget - Get single article
     */
    public function apiArticle(int $id): JsonResponse
    {
        $article = HelpCenterArticle::where('draft', false)
            ->with('categories')
            ->findOrFail($id);

        // Get first category and section
        $category = $article->categories->first();

        return response()->json([
            'id' => (string) $article->id,
            'title' => $article->title,
            'body' => $article->body,
            'description' => $article->description,
            'category' => $category->parent->name ?? 'General',
            'section' => $category->name ?? null,
        ]);
    }

    /**
     * API endpoint for widget - Get categories with articles
     */
    public function apiWidget(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 50), 200);
        $page = max((int) $request->input('page', 1), 1);

        $version = Cache::get('helpdesk:widget:version', 1);
        $cacheKey = "helpdesk:widget:articles:{$version}:{$limit}:{$page}";

        return Cache::remember($cacheKey, 3600, function () use ($limit, $page) {
            $categories = HelpCenterCategory::whereNull('parent_id')
                ->where('is_section', false)
                ->with(['sections' => function ($q) {
                    $q->with(['articles' => function ($q2) {
                        $q2->where('draft', false)
                            ->orderBy('position')
                            ->limit(30);
                    }])->orderBy('position');
                }])
                ->orderBy('position')
                ->get();

            $widgetArticles = [];
            $widgetCategories = [];

            foreach ($categories as $category) {
                $articleCount = 0;

                foreach ($category->sections as $section) {
                    foreach ($section->articles as $article) {
                        $widgetArticles[] = [
                            'id' => (string) $article->id,
                            'title' => $article->title,
                            'excerpt' => $article->description ?: \Str::limit(strip_tags($article->body ?? ''), 100),
                            'category' => $category->name,
                            'section' => $section->name,
                        ];
                        $articleCount++;
                    }
                }

                if ($articleCount > 0) {
                    $widgetCategories[] = [
                        'id' => (string) $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon ?: '📄',
                        'count' => $articleCount,
                    ];
                }
            }

            // Manual pagination over flat article list
            $total = count($widgetArticles);
            $offset = ($page - 1) * $limit;
            $paged = array_slice($widgetArticles, $offset, $limit);

            return response()->json([
                'categories' => $widgetCategories,
                'articles' => $paged,
                'meta' => [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => (int) ceil($total / $limit),
                ],
            ]);
        });
    }
}
