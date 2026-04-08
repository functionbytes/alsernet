<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Modules\Seo\Services\SchemaOrgService;

class BlogPublicController extends Controller
{
    public function index(Request $request): View
    {
        $page = $request->get('page', 1);

        $featuredPosts = Cache::remember('blog.public.index.featured', 900, function () {
            return BlogPost::query()
                ->with(['user', 'categories'])
                ->published()
                ->featured()
                ->recent()
                ->limit(3)
                ->get();
        });

        $posts = Cache::remember("blog.public.index.page.{$page}", 900, function () {
            return BlogPost::query()
                ->with(['user', 'categories', 'tags'])
                ->published()
                ->recent()
                ->paginate(config('blog.posts_per_page', 12));
        });

        $this->addBlogIndexSchema('Blog', url('/blog'));

        return view('template::views.blog', compact('posts', 'featuredPosts'));
    }

    public function post(string $slug): View
    {
        $post = BlogPost::query()
            ->with(['user', 'categories', 'tags'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $post->incrementViewCount();

        $comments = $post->comments()
            ->approved()
            ->topLevel()
            ->with('replies')
            ->orderBy('created_at')
            ->get();

        $commentsEnabled = config('blog.allow_comments', false);
        $related = $post->relatedPosts(4);

        return view('template::views.post', compact('post', 'comments', 'commentsEnabled', 'related'));
    }

    public function category(string $slug): View
    {
        $page = request()->get('page', 1);

        $category = Cache::remember("blog.public.category.{$slug}", 1800, function () use ($slug) {
            return BlogCategory::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->firstOrFail();
        });

        $posts = Cache::remember("blog.public.category.{$slug}.page.{$page}", 1800, function () use ($category) {
            return BlogPost::query()
                ->with(['user', 'categories'])
                ->published()
                ->whereHas('categories', fn ($q) => $q->where('blog_categories.id', $category->id))
                ->recent()
                ->paginate(config('blog.posts_per_page', 12));
        });

        $this->addBlogIndexSchema($category->name, url()->current());

        return view('template::views.category', compact('category', 'posts'));
    }

    public function tag(string $slug): View
    {
        $page = request()->get('page', 1);

        $tag = Cache::remember("blog.public.tag.{$slug}", 1800, function () use ($slug) {
            return BlogTag::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->firstOrFail();
        });

        $posts = Cache::remember("blog.public.tag.{$slug}.page.{$page}", 1800, function () use ($tag) {
            return BlogPost::query()
                ->with(['user', 'categories'])
                ->published()
                ->whereHas('tags', fn ($q) => $q->where('blog_tags.id', $tag->id))
                ->recent()
                ->paginate(config('blog.posts_per_page', 12));
        });

        $this->addBlogIndexSchema($tag->name, url()->current());

        return view('template::views.tag', compact('tag', 'posts'));
    }

    public function search(Request $request): View
    {
        $query = $request->get('q', '');

        $posts = BlogPost::query()
            ->with(['user', 'categories'])
            ->published()
            ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            }))
            ->recent()
            ->paginate(config('blog.posts_per_page', 12));

        $this->addBlogIndexSchema('Buscar: '.$query, url()->current());

        return view('template::views.search', compact('posts', 'query'));
    }

    /**
     * Add WebPage + Organization schemas to the SEO service for listing pages.
     */
    private function addBlogIndexSchema(string $pageTitle, string $pageUrl): void
    {
        if (! app()->bound('seo')) {
            return;
        }

        $schemaService = app(SchemaOrgService::class);
        $seo = app('seo');

        $seo->addSchema($schemaService->generateWebPageSchema([
            'title' => $pageTitle.' - '.config('app.name'),
            'url' => $pageUrl,
        ]));

        $seo->addSchema($schemaService->generateOrganizationSchema());
    }

    public function rss(): Response
    {
        $posts = BlogPost::query()
            ->with('user')
            ->published()
            ->recent()
            ->limit(20)
            ->get();

        $siteUrl = config('app.url');
        $siteName = config('app.name');
        $feedUrl = route('blog.rss');
        $buildDate = now()->toRfc7231String();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
        $xml .= '<channel>'."\n";
        $xml .= '<title>'.e($siteName).' - Blog</title>'."\n";
        $xml .= '<link>'.e($siteUrl.'/blog').'</link>'."\n";
        $xml .= '<description>Ultimas publicaciones del blog de '.e($siteName).'</description>'."\n";
        $xml .= '<language>'.e(app()->getLocale()).'</language>'."\n";
        $xml .= '<lastBuildDate>'.e($buildDate).'</lastBuildDate>'."\n";
        $xml .= '<atom:link href="'.e($feedUrl).'" rel="self" type="application/rss+xml"/>'."\n";

        foreach ($posts as $post) {
            $postUrl = $siteUrl.'/blog/'.$post->slug;
            $pubDate = $post->published_at?->toRfc7231String() ?? $post->created_at->toRfc7231String();

            $xml .= '<item>'."\n";
            $xml .= '<title>'.e($post->title).'</title>'."\n";
            $xml .= '<link>'.e($postUrl).'</link>'."\n";
            $xml .= '<guid isPermaLink="true">'.e($postUrl).'</guid>'."\n";
            $xml .= '<description><![CDATA['.$post->excerpt.']]></description>'."\n";
            $xml .= '<pubDate>'.e($pubDate).'</pubDate>'."\n";

            if ($post->user) {
                $xml .= '<author>'.e($post->user->email.' ('.$post->user->name.')').'</author>'."\n";
            }

            $xml .= '</item>'."\n";
        }

        $xml .= '</channel>'."\n";
        $xml .= '</rss>';

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }
}
