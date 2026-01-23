<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Helpcenter;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Helpcenters\HelpcenterArticle;
use Modules\HelpdeskChat\Models\Helpcenters\HelpcenterCategory;
use Spatie\Permission\Models\Role;

class HelpCenterController extends Controller
{
    /**
     * Display categories index page
     */
    public function index(Request $request): View
    {
        $query = HelpcenterCategory::query()
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

        return view('theme.views.helpdesk.helpcenter.categories.index', compact('categories'));
    }

    /**
     * Show create category form
     */
    public function create(): View
    {
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name', 'name');

        return view('theme.views.helpdesk.helpcenter.categories.create', compact('roles'));
    }

    /**
     * Store new category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'visible_to_role' => 'nullable|string|max:255',
            'managed_by_role' => 'nullable|string|max:255',
        ]);

        $position = HelpcenterCategory::whereNull('parent_id')->max('position') + 1;
        $validated['position'] = $position;
        $validated['is_section'] = false;

        $category = HelpcenterCategory::create($validated);

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
        $category = HelpcenterCategory::findOrFail($id);
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('theme.views.helpdesk.helpcenter.categories.edit', compact('category', 'roles'));
    }

    /**
     * Update category
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:helpdesk_helpcenter_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'visible_to_role' => 'nullable|string|max:255',
            'managed_by_role' => 'nullable|string|max:255',
        ]);

        $category = HelpcenterCategory::findOrFail($validated['id']);
        $category->update($validated);

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
        $category = HelpcenterCategory::with(['sections' => function ($query) {
            $query->withCount('articles')->orderBy('position');
        }])
            ->withCount(['sections', 'articles'])
            ->findOrFail($id);

        return view('theme.views.helpdesk.helpcenter.categories.show', compact('category'));
    }

    /**
     * Delete category
     */
    public function destroy(int $id): JsonResponse
    {
        $category = HelpcenterCategory::findOrFail($id);

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
        $categories = HelpcenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('name', 'asc')
            ->get();

        $parentId = $request->get('parent_id');
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name', 'name');

        return view('theme.views.helpdesk.helpcenter.sections.create', compact('categories', 'parentId', 'roles'));
    }

    /**
     * Store new section
     */
    public function storeSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'required|integer|exists:helpdesk_helpcenter_categories,id',
        ]);

        $position = HelpcenterCategory::where('parent_id', $validated['parent_id'])->max('position') + 1;
        $validated['position'] = $position;
        $validated['is_section'] = true;

        $section = HelpcenterCategory::create($validated);

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
        $section = HelpcenterCategory::findOrFail($id);
        $categories = HelpcenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('name', 'asc')
            ->get();
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name', 'name');

        return view('theme.views.helpdesk.helpcenter.sections.edit', compact('section', 'categories', 'roles'));
    }

    /**
     * Update section
     */
    public function updateSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:helpdesk_helpcenter_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'required|integer|exists:helpdesk_helpcenter_categories,id',
        ]);

        $section = HelpcenterCategory::findOrFail($validated['id']);
        $section->update($validated);

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
        $section = HelpcenterCategory::with(['parent', 'articles.author'])
            ->withCount('articles')
            ->findOrFail($id);

        return view('theme.views.helpdesk.helpcenter.sections.show', compact('section'));
    }

    /**
     * Delete section
     */
    public function destroySection(int $id): JsonResponse
    {
        $section = HelpcenterCategory::findOrFail($id);

        // Check if section has articles
        if ($section->articles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una sección que contiene artículos',
            ], 422);
        }

        $section->delete();

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
        $section = HelpcenterCategory::with('parent')->findOrFail($id);
        $sections = HelpcenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('theme.views.helpdesk.helpcenter.articles.create', compact('sections', 'section'));
    }

    /**
     * Display articles index page
     */
    public function articlesIndex(Request $request): View
    {
        $query = HelpcenterArticle::with(['categories', 'author']);

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // Filter by draft status
        if ($request->filled('draft')) {
            $query->where('draft', $request->draft);
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('theme.views.helpdesk.helpcenter.articles.index', compact('articles'));
    }

    /**
     * Show create article form
     */
    public function createArticle(): View
    {
        $sections = HelpcenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('theme.views.helpdesk.helpcenter.articles.create', compact('sections'));
    }

    /**
     * Store new article
     */
    public function storeArticle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'description' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'section_id' => 'required|integer|exists:helpdesk_helpcenter_categories,id',
            'position' => 'nullable|integer|min:0',
            'draft' => 'boolean',
            'hide_from_structure' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $article = HelpcenterArticle::create([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
            'description' => $validated['description'] ?? '',
            'meta_description' => $validated['meta_description'] ?? '',
            'position' => $validated['position'] ?? 0,
            'draft' => $request->has('draft') ? true : false,
            'hide_from_structure' => $request->has('hide_from_structure') ? true : false,
            'author_id' => auth()->id(),
        ]);

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $article->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        // Handle tags
        if ($request->filled('tags')) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                $tag = \App\Models\Helpdesk\HelpCenterTag::findOrCreateByName($tagName);
                $tagIds[] = $tag->id;
            }
            $article->tags()->sync($tagIds);
        }

        // Attach to section with position
        $categoryPosition = DB::table('helpdesk_helpcenter_category_article')
            ->where('category_id', $validated['section_id'])
            ->max('position') + 1;

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
        $article = HelpcenterArticle::with(['categories', 'tags'])->findOrFail($id);
        $sections = HelpcenterCategory::where('is_section', true)
            ->with('parent')
            ->orderBy('name', 'asc')
            ->get();

        return view('theme.views.helpdesk.helpcenter.articles.edit', compact('article', 'sections'));
    }

    /**
     * Update article
     */
    public function updateArticle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:helpdesk_helpcenter_articles,id',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'description' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'section_id' => 'required|integer|exists:helpdesk_helpcenter_categories,id',
            'position' => 'nullable|integer|min:0',
            'draft' => 'boolean',
            'hide_from_structure' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $article = HelpcenterArticle::findOrFail($validated['id']);
        $article->update([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
            'description' => $validated['description'] ?? '',
            'meta_description' => $validated['meta_description'] ?? '',
            'position' => $validated['position'] ?? $article->position,
            'draft' => $request->has('draft') ? true : false,
            'hide_from_structure' => $request->has('hide_from_structure') ? true : false,
        ]);

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
                    $tag = \App\Models\Helpdesk\HelpCenterTag::findOrCreateByName($tagName);
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
        $article = HelpcenterArticle::findOrFail($id);
        $article->delete();

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
        $article = HelpcenterArticle::where('draft', false)
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
    public function apiWidget(): JsonResponse
    {
        // Get all categories (not sections) with their sections and articles
        $categories = HelpcenterCategory::whereNull('parent_id')
            ->where('is_section', false)
            ->with([
                'sections.articles' => function ($query) {
                    $query->where('draft', false)
                        ->orderBy('id', 'desc');
                },
            ])
            ->orderBy('position', 'asc')
            ->get();

        // Transform data for widget
        $widgetCategories = [];
        $widgetArticles = [];

        foreach ($categories as $category) {
            $articleCount = 0;

            foreach ($category->sections as $section) {
                $articleCount += $section->articles->count();

                foreach ($section->articles as $article) {
                    $widgetArticles[] = [
                        'id' => (string) $article->id,
                        'title' => $article->title,
                        'excerpt' => $article->description ?: \Str::limit(strip_tags($article->body), 100),
                        'category' => $category->name,
                        'section' => $section->name,
                    ];
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

        return response()->json([
            'categories' => $widgetCategories,
            'articles' => $widgetArticles,
        ]);
    }
}
