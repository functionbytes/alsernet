<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Blog\Http\Requests\StoreBlogTagRequest;
use Modules\Blog\Http\Requests\UpdateBlogTagRequest;
use Modules\Blog\Models\BlogTag;
use Modules\Blog\Services\BlogPostService;

class BlogTagController extends Controller
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
        $this->authorize('viewAny', BlogTag::class);

        $tags = BlogTag::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate(20);

        $stats = Cache::remember('blog:tag_stats', 300, function () {
            $row = BlogTag::query()->selectRaw("COUNT(*) as total, SUM(status='published') as published, SUM(status='draft') as draft")->first();

            return ['total' => (int) $row->total, 'published' => (int) $row->published, 'draft' => (int) $row->draft, 'used' => (int) BlogTag::has('posts')->count()];
        });

        return view('blog::tags.index', compact('tags', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', BlogTag::class);

        $tag = new BlogTag;
        $availableLocales = $this->getAvailableLocales();

        return view('blog::tags.create', compact('tag', 'availableLocales'));
    }

    public function store(StoreBlogTagRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogTag::class);

        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? '') ?: Str::slug($data['name']);
        $data['user_id'] = auth()->id();

        $tag = BlogTag::query()->create($data);

        $this->syncTranslations($tag, $data['translations'] ?? []);

        return redirect()
            ->route('blog.tags.index')
            ->with('success', __('blog::messages.tag_created'));
    }

    public function edit(BlogTag $tag): View
    {
        $this->authorize('update', $tag);

        $tag->load('translations');
        $availableLocales = $this->getAvailableLocales();

        return view('blog::tags.edit', compact('tag', 'availableLocales'));
    }

    public function update(UpdateBlogTagRequest $request, BlogTag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? '') ?: Str::slug($data['name']);

        $tag->update($data);

        $this->syncTranslations($tag, $data['translations'] ?? []);

        return redirect()
            ->route('blog.tags.index')
            ->with('success', __('blog::messages.tag_updated'));
    }

    public function destroy(BlogTag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $tag->posts()->detach();
        $tag->delete();

        return redirect()
            ->route('blog.tags.index')
            ->with('success', __('blog::messages.tag_deleted'));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('create', BlogTag::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:publish,unpublish,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $validated['ids'];

        match ($validated['action']) {
            'publish' => BlogTag::whereIn('id', $ids)->update(['status' => 'published']),
            'unpublish' => BlogTag::whereIn('id', $ids)->update(['status' => 'draft']),
            'delete' => (function () use ($ids) {
                BlogTag::whereIn('id', $ids)->get()->each(fn ($tag) => $tag->posts()->detach());
                BlogTag::whereIn('id', $ids)->delete();
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

        while (BlogTag::query()
            ->where('slug', $slug)
            ->when($request->integer('ignoreId'), fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    public function ajaxAll(): JsonResponse
    {
        $tags = BlogTag::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($tags);
    }

    private function getAvailableLocales(): array
    {
        $supported = BlogPostService::getSupportedLocales();
        $labels = self::LOCALE_LABELS;

        return collect($supported)
            ->mapWithKeys(fn ($locale) => [$locale => $labels[$locale] ?? strtoupper($locale)])
            ->all();
    }

    private function syncTranslations(BlogTag $tag, array $translations): void
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

            $tag->translations()->updateOrCreate(['locale' => $locale], $fillable);
        }
    }
}
