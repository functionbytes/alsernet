<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Http\Requests\StoreBlogPostRequest;
use Modules\Blog\Http\Requests\UpdateBlogPostRequest;
use Modules\Blog\Jobs\TranslateBlogPostJob;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostVersion;
use Modules\Blog\Models\BlogTag;
use Modules\Blog\Services\BlogPostService;

class BlogPostController extends Controller
{
    /** @var array<string, string> */
    private const LOCALE_LABELS = [
        'es' => 'Español',
        'en' => 'English',
        'pt' => 'Português',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
    ];

    public function __construct(
        private readonly BlogPostService $service
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogPost::class);

        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'translation_status' => $request->get('translation_status'),
        ];

        $posts = BlogPost::query()
            ->with(['user', 'categories', 'translations'])
            ->when($filters['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($filters['search'], fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($filters['category'], fn ($q, $c) => $q->whereHas('categories', fn ($q) => $q->where('blog_categories.id', $c)))
            ->when($filters['translation_status'], function ($q, $ts) {
                return match ($ts) {
                    'complete' => $q->whereHas('translations', fn ($q) => $q->whereNotNull('title')->whereNotNull('content')),
                    'incomplete' => $q->where(function ($q) {
                        $q->whereDoesntHave('translations')
                            ->orWhereHas('translations', fn ($q) => $q->whereNull('title')->orWhereNull('content'));
                    }),
                    'stale' => $q->whereHas('translations', fn ($q) => $q->where('status', 'stale')),
                    'none' => $q->whereDoesntHave('translations'),
                    default => $q,
                };
            })
            ->latest()
            ->paginate(20);

        $stats = Cache::remember('blog:post_stats', 300, fn () => $this->service->getStats());
        $categories = BlogCategory::query()->orderBy('name')->limit(200)->get();

        return view('blog::posts.index', compact('posts', 'filters', 'stats', 'categories'));
    }

    public function create(): View
    {
        $this->authorize('create', BlogPost::class);

        $post = new BlogPost;
        $categories = BlogCategory::query()->published()->ordered()->limit(200)->get();
        $tags = BlogTag::query()->published()->orderBy('name')->limit(200)->get();
        $statuses = PostStatus::options();
        $availableLocales = $this->getAvailableLocales();

        return view('blog::posts.create', compact('post', 'categories', 'tags', 'statuses', 'availableLocales'));
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogPost::class);

        $post = $this->service->createPost($request->validated());

        return redirect()
            ->route('blog.posts.edit', $post->id)
            ->with('success', __('blog::messages.post_created'));
    }

    public function edit(BlogPost $post): View
    {
        $this->authorize('update', $post);

        $post->load(['categories', 'tags', 'translations']);
        $categories = BlogCategory::query()->published()->ordered()->limit(200)->get();
        $tags = BlogTag::query()->published()->orderBy('name')->limit(200)->get();
        $statuses = PostStatus::options();
        $availableLocales = $this->getAvailableLocales();

        return view('blog::posts.edit', compact('post', 'categories', 'tags', 'statuses', 'availableLocales'));
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $post);

        $this->service->updatePost($post, $request->validated());

        if ($request->boolean('auto_save')) {
            return response()->json(['auto_saved' => true]);
        }

        return redirect()
            ->route('blog.posts.edit', $post->id)
            ->with('success', __('blog::messages.post_updated'));
    }

    public function destroy(Request $request, BlogPost $post): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $post);

        $this->service->deletePost($post);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('blog.posts.index')
            ->with('success', __('blog::messages.post_deleted'));
    }

    public function publish(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorize('publish', $post);

        $this->service->publishPost($post);

        return response()->json(['success' => true, 'message' => __('blog::messages.post_published')]);
    }

    public function unpublish(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorize('publish', $post);

        $this->service->unpublishPost($post);

        return response()->json(['success' => true, 'message' => __('blog::messages.post_unpublished')]);
    }

    public function ajaxSlug(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'ignoreId' => ['nullable', 'integer'],
        ]);

        $slug = $this->service->generateSlug(
            $request->input('title'),
            $request->integer('ignoreId') ?: null
        );

        return response()->json(['slug' => $slug]);
    }

    /** @return array<string, string> */
    private function getAvailableLocales(): array
    {
        $appLocale = app()->getLocale();
        $supported = BlogPostService::getSupportedLocales();
        $labels = self::LOCALE_LABELS;

        $translationLocales = collect($supported)
            ->mapWithKeys(fn ($locale) => [$locale => $labels[$locale] ?? strtoupper($locale)])
            ->all();

        if (! isset($translationLocales[$appLocale])) {
            return [$appLocale => $labels[$appLocale] ?? strtoupper($appLocale)] + $translationLocales;
        }

        return $translationLocales;
    }

    public function versions(BlogPost $post): View
    {
        $this->authorize('update', $post);

        $versions = $post->versions()->with('author:id,firstname,lastname')->paginate(10);

        return view('blog::posts.versions', compact('post', 'versions'));
    }

    public function restoreVersion(BlogPost $post, BlogPostVersion $version): RedirectResponse
    {
        $this->authorize('update', $post);

        $this->service->updatePost($post, [
            'title' => $version->title,
            'slug' => $version->slug,
            'content' => $version->content,
            'description' => $version->excerpt,
            'change_summary' => 'Restaurado desde versión #'.$version->version_number,
        ]);

        return redirect()
            ->route('blog.posts.edit', $post)
            ->with('success', __('blog::messages.post_version_restored', ['number' => $version->version_number]));
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['delete', 'publish', 'unpublish', 'translate_all'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:blog_posts,id'],
        ]);

        $ids = $request->input('ids', []);

        match ($request->input('action')) {
            'publish' => BlogPost::whereIn('id', $ids)->update(['status' => PostStatus::Published->value, 'published_at' => now()]),
            'unpublish' => BlogPost::whereIn('id', $ids)->update(['status' => PostStatus::Draft->value]),
            'delete' => BlogPost::whereIn('id', $ids)->delete(),
            'translate_all' => collect($ids)->each(fn ($id) => TranslateBlogPostJob::dispatch($id, null, null, null, auth()->id())),
            default => null,
        };

        $count = count($ids);

        return response()->json([
            'success' => true,
            'message' => __('blog::messages.posts_bulk_processed', ['count' => $count]),
        ]);
    }

    public function duplicate(BlogPost $post): RedirectResponse
    {
        $this->authorize('create', BlogPost::class);

        $newPost = $this->service->createPost([
            'title' => 'Copia de '.$post->title,
            'slug' => '',
            'description' => $post->description,
            'content' => $post->content,
            'status' => 'draft',
            'is_featured' => false,
            'image' => $post->image,
            'format_type' => $post->format_type,
            'seo_title' => $post->seo_title,
            'seo_description' => $post->seo_description,
            'seo_keywords' => $post->seo_keywords,
            'categories' => $post->categories->pluck('id')->toArray(),
            'tags' => $post->tags->pluck('id')->toArray(),
            'send_newsletter' => false,
        ]);

        return redirect()
            ->route('blog.posts.edit', $newPost)
            ->with('success', 'Post duplicado exitosamente.');
    }

    public function versionDiff(BlogPost $post, BlogPostVersion $version): JsonResponse
    {
        $this->authorize('update', $post);

        return response()->json([
            'version_number' => $version->version_number,
            'created_at' => $version->created_at->format('d/m/Y H:i'),
            'author' => $version->author?->name ?? 'Sistema',
            'current' => [
                'title' => $post->title,
                'content' => strip_tags($post->content ?? ''),
            ],
            'version' => [
                'title' => $version->title,
                'content' => strip_tags($version->content ?? ''),
            ],
        ]);
    }

    public function preview(BlogPost $post): RedirectResponse
    {
        $this->authorize('update', $post);

        return redirect($post->url ?? route('blog.public.post', $post->slug));
    }
}
