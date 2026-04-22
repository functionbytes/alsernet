<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\HelpCenterArticle;
use Modules\Helpdesk\Models\HelpCenterCategory;

class HelpCenterPublicController extends Controller
{
    public function index(Request $request): View
    {
        $query = HelpCenterArticle::query()
            ->where('is_published', true)
            ->select('id', 'title', 'slug', 'excerpt', 'content', 'category_id', 'views_count', 'published_at');

        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('content', 'like', "%{$term}%"));
        }

        $articles = $query->orderByDesc('views_count')->paginate(12);

        $categories = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->where('is_section', false)
            ->orderBy('position')
            ->get();

        return view('helpdesk::public.helpcenter.index', compact('articles', 'categories'));
    }

    public function show(string $slug): View
    {
        $article = HelpCenterArticle::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $article->increment('views_count');

        $related = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->select('id', 'title', 'slug', 'excerpt')
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        return view('helpdesk::public.helpcenter.show', compact('article', 'related'));
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        if (strlen($q) < 3) {
            return response()->json(['articles' => []]);
        }

        $articles = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where(fn ($qb) => $qb->where('title', 'like', "%{$q}%")->orWhere('content', 'like', "%{$q}%"))
            ->select('id', 'title', 'slug', 'excerpt')
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        return response()->json(['articles' => $articles]);
    }
}
