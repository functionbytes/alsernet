<?php

namespace Modules\Blog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Blog\Http\Resources\BlogCategoryResource;
use Modules\Blog\Http\Resources\BlogPostResource;
use Modules\Blog\Http\Resources\BlogTagResource;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;

class BlogApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = BlogPost::query()
            ->with(['categories', 'tags', 'user'])
            ->published()
            ->when($request->get('featured'), fn ($q) => $q->featured())
            ->recent()
            ->paginate($request->integer('per_page', 15));

        return BlogPostResource::collection($posts);
    }

    public function show(string $slug): BlogPostResource
    {
        $post = BlogPost::query()
            ->with(['categories', 'tags', 'user'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('views');

        return new BlogPostResource($post);
    }

    public function categories(Request $request): AnonymousResourceCollection
    {
        $categories = BlogCategory::query()
            ->published()
            ->when($request->get('root'), fn ($q) => $q->root())
            ->ordered()
            ->get();

        return BlogCategoryResource::collection($categories);
    }

    public function tags(Request $request): AnonymousResourceCollection
    {
        $tags = BlogTag::query()
            ->published()
            ->orderBy('name')
            ->get();

        return BlogTagResource::collection($tags);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->get('q', '');

        $posts = BlogPost::query()
            ->with(['categories', 'tags', 'user'])
            ->published()
            ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            }))
            ->recent()
            ->paginate($request->integer('per_page', 15));

        return BlogPostResource::collection($posts);
    }
}
