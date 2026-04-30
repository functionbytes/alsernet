<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Helpcenters;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;
use Modules\Chat\Services\Helpcenters\HelpcenterArticleService;
use Modules\Chat\Services\Helpcenters\HelpcenterCategoryService;
use Modules\Chat\Services\Helpcenters\HelpcenterWidgetService;

class HelpcenterController extends Controller
{
    public function __construct(
        private readonly HelpcenterCategoryService $categoryService,
        private readonly HelpcenterArticleService $articleService,
        private readonly HelpcenterWidgetService $widgetService,
    ) {}

    // -------------------------------------------------------------------------
    // Categories
    // -------------------------------------------------------------------------

    /**
     * Display categories index page.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', HelpcenterCategory::class);

        $categories = $this->categoryService->listCategories($request->filled('search') ? $request->search : null);

        return view('Chat::chats.helpcenter.categories.index', compact('categories'));
    }

    /**
     * Show create category form.
     */
    public function create(): View
    {
        $this->authorize('create', HelpcenterCategory::class);

        $roles = $this->categoryService->roleOptions();

        return view('Chat::chats.helpcenter.categories.create', compact('roles'));
    }

    /**
     * Store new category.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', HelpcenterCategory::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'visible_to_role' => 'nullable|string|max:255',
            'managed_by_role' => 'nullable|string|max:255',
        ]);

        $this->categoryService->createCategory($validated);

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'redirect' => route('manager.chat.helpcenter.categories'),
        ]);
    }

    /**
     * Show edit category form.
     */
    public function edit(int $id): View
    {
        $category = HelpcenterCategory::findOrFail($id);
        $this->authorize('update', $category);

        $roles = $this->categoryService->roleOptions();

        return view('Chat::chats.helpcenter.categories.edit', compact('category', 'roles'));
    }

    /**
     * Update category.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:chat_helpcenter_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'visible_to_role' => 'nullable|string|max:255',
            'managed_by_role' => 'nullable|string|max:255',
        ]);

        $category = HelpcenterCategory::findOrFail($validated['id']);
        $this->authorize('update', $category);

        $this->categoryService->updateCategory($category, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'redirect' => route('manager.chat.helpcenter.categories'),
        ]);
    }

    /**
     * Show category with its sections.
     */
    public function showCategory(int $id): View
    {
        $category = $this->categoryService->findCategoryWithSections($id);
        $this->authorize('view', $category);

        return view('Chat::chats.helpcenter.categories.show', compact('category'));
    }

    /**
     * Delete category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = HelpcenterCategory::findOrFail($id);
        $this->authorize('delete', $category);

        $error = $this->categoryService->deleteCategory($category);

        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        return response()->json(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
    }

    // -------------------------------------------------------------------------
    // Sections
    // -------------------------------------------------------------------------

    /**
     * Show create section form.
     */
    public function createSection(Request $request): View
    {
        $this->authorize('create', HelpcenterCategory::class);

        $categories = $this->categoryService->allCategories();
        $parentId = $request->get('parent_id');
        $roles = $this->categoryService->roleOptions();

        return view('Chat::chats.helpcenter.sections.create', compact('categories', 'parentId', 'roles'));
    }

    /**
     * Store new section.
     */
    public function storeSection(Request $request): JsonResponse
    {
        $this->authorize('create', HelpcenterCategory::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'required|integer|exists:chat_helpcenter_categories,id',
        ]);

        $this->categoryService->createSection($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sección creada exitosamente',
            'redirect' => route('manager.chat.helpcenter.categories'),
        ]);
    }

    /**
     * Show edit section form.
     */
    public function editSection(int $id): View
    {
        $section = HelpcenterCategory::findOrFail($id);
        $this->authorize('update', $section);

        $categories = $this->categoryService->allCategories();
        $roles = $this->categoryService->roleOptions();

        return view('Chat::chats.helpcenter.sections.edit', compact('section', 'categories', 'roles'));
    }

    /**
     * Update section.
     */
    public function updateSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:chat_helpcenter_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'required|integer|exists:chat_helpcenter_categories,id',
        ]);

        $section = HelpcenterCategory::findOrFail($validated['id']);
        $this->authorize('update', $section);

        $this->categoryService->updateSection($section, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Sección actualizada exitosamente',
            'redirect' => route('manager.chat.helpcenter.categories'),
        ]);
    }

    /**
     * Show section with its articles.
     */
    public function showSection(int $id): View
    {
        $section = $this->categoryService->findSectionWithArticles($id);
        $this->authorize('view', $section);

        return view('Chat::chats.helpcenter.sections.show', compact('section'));
    }

    /**
     * Delete section.
     */
    public function destroySection(int $id): JsonResponse
    {
        $section = HelpcenterCategory::findOrFail($id);
        $this->authorize('delete', $section);

        $error = $this->categoryService->deleteSection($section);

        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        return response()->json(['success' => true, 'message' => 'Sección eliminada exitosamente']);
    }

    // -------------------------------------------------------------------------
    // Articles
    // -------------------------------------------------------------------------

    /**
     * Show create article form in section context.
     */
    public function createArticleInSection(int $id): View
    {
        $this->authorize('create', HelpcenterArticle::class);

        $section = HelpcenterCategory::with('parent')->findOrFail($id);
        $sections = $this->articleService->allSections();

        return view('Chat::chats.helpcenter.articles.create', compact('sections', 'section'));
    }

    /**
     * Display articles index page.
     */
    public function articlesIndex(Request $request): View
    {
        $this->authorize('viewAny', HelpcenterArticle::class);

        $draft = $request->filled('draft') ? (bool) $request->draft : null;
        $articles = $this->articleService->listArticles(
            $request->filled('search') ? $request->search : null,
            $draft,
        );

        return view('Chat::chats.helpcenter.articles.index', compact('articles'));
    }

    /**
     * Show create article form.
     */
    public function createArticle(): View
    {
        $this->authorize('create', HelpcenterArticle::class);

        $sections = $this->articleService->allSections();

        return view('Chat::chats.helpcenter.articles.create', compact('sections'));
    }

    /**
     * Store new article.
     */
    public function storeArticle(Request $request): JsonResponse
    {
        $this->authorize('create', HelpcenterArticle::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'description' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'section_id' => 'required|integer|exists:chat_helpcenter_categories,id',
            'position' => 'nullable|integer|min:0',
            'draft' => 'boolean',
            'hide_from_structure' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $this->articleService->createArticle($validated, $request);

        return response()->json([
            'success' => true,
            'message' => 'Artículo creado exitosamente',
            'redirect' => route('manager.chat.helpcenter.articles'),
        ]);
    }

    /**
     * Show edit article form.
     */
    public function editArticle(int $id): View
    {
        $article = $this->articleService->findArticleForEdit($id);
        $this->authorize('update', $article);

        $sections = $this->articleService->allSections();

        return view('Chat::chats.helpcenter.articles.edit', compact('article', 'sections'));
    }

    /**
     * Update article.
     */
    public function updateArticle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:chat_helpcenter_articles,id',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'description' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'section_id' => 'required|integer|exists:chat_helpcenter_categories,id',
            'position' => 'nullable|integer|min:0',
            'draft' => 'boolean',
            'hide_from_structure' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $article = HelpcenterArticle::findOrFail($validated['id']);
        $this->authorize('update', $article);

        $this->articleService->updateArticle($article, $validated, $request);

        return response()->json([
            'success' => true,
            'message' => 'Artículo actualizado exitosamente',
            'redirect' => route('manager.chat.helpcenter.articles'),
        ]);
    }

    /**
     * Delete article.
     */
    public function destroyArticle(int $id): JsonResponse
    {
        $article = HelpcenterArticle::findOrFail($id);
        $this->authorize('delete', $article);

        $this->articleService->deleteArticle($article);

        return response()->json(['success' => true, 'message' => 'Artículo eliminado exitosamente']);
    }

    // -------------------------------------------------------------------------
    // Widget API
    // -------------------------------------------------------------------------

    /**
     * API endpoint for widget - Get single article.
     */
    public function apiArticle(int $id): JsonResponse
    {
        return response()->json($this->widgetService->getArticle($id));
    }

    /**
     * API endpoint for widget - Get categories with articles.
     */
    public function apiWidget(): JsonResponse
    {
        return response()->json($this->widgetService->getWidgetData());
    }
}
