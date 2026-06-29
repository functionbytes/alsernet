<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Page\Models\Page;

class SearchController extends Controller
{
    /**
     * Search published pages.
     */
    public function search(Request $request): JsonResponse
    {
        // Validate search query
        $request->validate([
            'q' => 'required|string|min:1|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('q');
        $perPage = $request->input('per_page', 10);

        $useFullText = strlen($query) >= 3;

        $pages = Page::published()
            ->searchPages($query, $useFullText)
            ->select(['id', 'title', 'slug', 'content', 'description', 'published_at'])
            ->when($useFullText, fn ($q) => $q->orderByRaw(
                'MATCH(title, content, description) AGAINST(? IN BOOLEAN MODE) DESC',
                [$query.'*']
            ))
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        // Transform results
        $results = $pages->through(function ($page) use ($query) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'excerpt' => $this->generateExcerpt($page, $query),
                'url' => $page->url,
                'published_at' => $page->published_at?->format('Y-m-d H:i:s'),
                'highlighted_title' => $this->highlightTerm($page->title, $query),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'query' => $query,
        ]);
    }

    /**
     * Generate excerpt with search term context.
     */
    protected function generateExcerpt(Page $page, string $query, int $length = 200): string
    {
        $content = strip_tags($page->content ?? '');
        $description = strip_tags($page->description ?? '');

        // Try to find the search term in content
        $position = stripos($content, $query);

        if ($position !== false) {
            // Extract context around the search term
            $start = max(0, $position - 100);
            $excerpt = substr($content, $start, $length);

            // Add ellipsis if needed
            if ($start > 0) {
                $excerpt = '...'.$excerpt;
            }
            if (strlen($content) > $start + $length) {
                $excerpt .= '...';
            }

            return $this->highlightTerm($excerpt, $query);
        }

        // Fallback to description or beginning of content
        if (! empty($description)) {
            return $this->highlightTerm(Str::limit($description, $length), $query);
        }

        return $this->highlightTerm(Str::limit($content, $length), $query);
    }

    /**
     * Highlight search term in text.
     */
    protected function highlightTerm(string $text, string $term): string
    {
        if (empty($term) || empty($text)) {
            return $text;
        }

        // Escape special regex characters in the search term
        $term = preg_quote($term, '/');

        // Highlight the term (case-insensitive)
        return preg_replace(
            '/('.$term.')/iu',
            '<mark>$1</mark>',
            $text
        );
    }

    /**
     * Quick search for autocomplete (returns limited results).
     */
    public function quickSearch(Request $request): JsonResponse
    {
        // Validate search query
        $request->validate([
            'q' => 'required|string|min:1|max:255',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 5);

        // Quick search - limit results for autocomplete
        $pages = Page::published()
            ->searchPages($query)
            ->select(['id', 'title', 'slug'])
            ->limit($limit)
            ->get();

        // Transform results
        $results = $pages->map(function ($page) use ($query) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => $page->url,
                'highlighted_title' => $this->highlightTerm($page->title, $query),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $results,
            'query' => $query,
        ]);
    }
}
