<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Blog\Http\Requests\StoreBlogCategoryRequest;
use Modules\Blog\Http\Requests\UpdateBlogCategoryRequest;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Services\BlogPostService;

class BlogCategoryController extends Controller
{
    /** @var array<string, string> */
    private const LOCALE_LABELS = [
        'en' => 'English',
        'pt' => 'Português',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogCategory::class);

        $categories = BlogCategory::query()
            ->with('parent')
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->ordered()
            ->paginate(20);

        $stats = Cache::remember('blog:category_stats', 300, function () {
            $row = BlogCategory::query()
                ->selectRaw("COUNT(*) as total, SUM(status='published') as published, SUM(status='draft') as draft, SUM(is_featured=1) as featured")
                ->first();

            return ['total' => (int) $row->total, 'published' => (int) $row->published, 'draft' => (int) $row->draft, 'featured' => (int) $row->featured];
        });

        return view('blog::categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', BlogCategory::class);

        $category = new BlogCategory;
        $parentCategories = BlogCategory::query()->root()->ordered()->get();
        $availableLocales = $this->getAvailableLocales();

        return view('blog::categories.create', compact('category', 'parentCategories', 'availableLocales'));
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogCategory::class);

        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? '') ?: Str::slug($data['name']);
        $data['parent_id'] = $data['parent_id'] ?? 0;
        $data['user_id'] = auth()->id();

        $category = BlogCategory::query()->create($data);

        $this->syncTranslations($category, $data['translations'] ?? []);

        return redirect()
            ->route('blog.categories.index')
            ->with('success', __('blog::messages.category_created'));
    }

    public function edit(BlogCategory $category): View
    {
        $this->authorize('update', $category);

        $category->load('translations');

        $parentCategories = BlogCategory::query()
            ->where('id', '!=', $category->id)
            ->ordered()
            ->get();

        $availableLocales = $this->getAvailableLocales();

        return view('blog::categories.edit', compact('category', 'parentCategories', 'availableLocales'));
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? '') ?: Str::slug($data['name']);
        $data['parent_id'] = $data['parent_id'] ?? 0;

        $category->update($data);

        $this->syncTranslations($category, $data['translations'] ?? []);

        return redirect()
            ->route('blog.categories.index')
            ->with('success', __('blog::messages.category_updated'));
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->posts()->detach();
        $category->delete();

        return redirect()
            ->route('blog.categories.index')
            ->with('success', __('blog::messages.category_deleted'));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('create', BlogCategory::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:publish,unpublish,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $validated['ids'];

        match ($validated['action']) {
            'publish' => BlogCategory::whereIn('id', $ids)->update(['status' => 'published']),
            'unpublish' => BlogCategory::whereIn('id', $ids)->update(['status' => 'draft']),
            'delete' => (function () use ($ids) {
                BlogCategory::whereIn('id', $ids)->get()->each(fn ($category) => $category->posts()->detach());
                BlogCategory::whereIn('id', $ids)->delete();
            })(),
        };

        $count = count($ids);

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function ajaxSlug(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ignoreId' => ['nullable', 'integer'],
        ]);

        $slug = Str::slug($request->input('name'));
        $original = $slug;
        $counter = 1;

        while (BlogCategory::query()
            ->where('slug', $slug)
            ->when($request->integer('ignoreId'), fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    /** @return array<string, string> */
    private function getAvailableLocales(): array
    {
        $supported = BlogPostService::getSupportedLocales();
        $labels = self::LOCALE_LABELS;

        return collect($supported)
            ->mapWithKeys(fn ($locale) => [$locale => $labels[$locale] ?? strtoupper($locale)])
            ->all();
    }

    private function syncTranslations(BlogCategory $category, array $translations): void
    {
        $supported = BlogPostService::getSupportedLocales();

        foreach ($translations as $locale => $transData) {
            if (! in_array($locale, $supported)) {
                continue;
            }

            $fillable = array_filter([
                'name' => $transData['name'] ?? null,
                'slug' => $transData['slug'] ?? null,
                'description' => $transData['description'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (empty($fillable)) {
                continue;
            }

            $category->translations()->updateOrCreate(['locale' => $locale], $fillable);
        }
    }
}
