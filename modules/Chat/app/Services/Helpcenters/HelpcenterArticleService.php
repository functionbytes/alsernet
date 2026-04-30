<?php

namespace Modules\Chat\Services\Helpcenters;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;
use Modules\Chat\Models\Helpcenters\HelpcenterTag;

class HelpcenterArticleService
{
    /**
     * Paginated list of articles with optional search and draft filter.
     */
    public function listArticles(?string $search, ?bool $draft): LengthAwarePaginator
    {
        return HelpcenterArticle::with(['categories', 'author'])
            ->when($search, fn ($q, $s) => $q->search($s))
            ->when($draft !== null, fn ($q) => $q->draftStatus($draft))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * All sections with their parent category (for article create/edit dropdowns).
     *
     * @return Collection<int, HelpcenterCategory>
     */
    public function allSections(): Collection
    {
        return HelpcenterCategory::sections()
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find an article with its categories and tags (for edit form).
     */
    public function findArticleForEdit(int $id): HelpcenterArticle
    {
        return HelpcenterArticle::with(['categories', 'tags'])->findOrFail($id);
    }

    /**
     * Create a new article, attach its featured image, tags, and section pivot.
     */
    public function createArticle(array $data, Request $request): HelpcenterArticle
    {
        $article = HelpcenterArticle::create([
            'title' => $data['title'],
            'body' => $data['body'] ?? '',
            'description' => $data['description'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'position' => $data['position'] ?? 0,
            'draft' => $request->has('draft'),
            'hide_from_structure' => $request->has('hide_from_structure'),
            'author_id' => auth()->id(),
        ]);

        $this->syncFeaturedImage($article, $request);
        $this->syncTags($article, $data['tags'] ?? null);
        $this->attachToSection($article, (int) $data['section_id']);

        return $article;
    }

    /**
     * Update an existing article, replacing image and syncing tags and section.
     */
    public function updateArticle(HelpcenterArticle $article, array $data, Request $request): void
    {
        $article->update([
            'title' => $data['title'],
            'body' => $data['body'] ?? '',
            'description' => $data['description'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'position' => $data['position'] ?? $article->position,
            'draft' => $request->has('draft'),
            'hide_from_structure' => $request->has('hide_from_structure'),
        ]);

        if ($request->hasFile('featured_image')) {
            $article->clearMediaCollection('featured_image');
            $this->syncFeaturedImage($article, $request);
        }

        if ($request->has('tags')) {
            $this->syncTags($article, $request->filled('tags') ? ($data['tags'] ?? null) : null);
        }

        $this->syncSection($article, (int) $data['section_id']);
    }

    /**
     * Delete an article.
     */
    public function deleteArticle(HelpcenterArticle $article): void
    {
        $article->delete();
    }

    /**
     * Upload the featured image via Spatie Media Library.
     */
    private function syncFeaturedImage(HelpcenterArticle $article, Request $request): void
    {
        if (! $request->hasFile('featured_image')) {
            return;
        }

        $article->addMediaFromRequest('featured_image')
            ->toMediaCollection('featured_image');
    }

    /**
     * Sync article tags. Passing null clears all tags.
     *
     * @param  array<int, string>|null  $tagNames
     */
    private function syncTags(HelpcenterArticle $article, ?array $tagNames): void
    {
        if ($tagNames === null) {
            $article->tags()->sync([]);

            return;
        }

        $tagIds = array_map(
            fn (string $name) => HelpcenterTag::findOrCreateByName($name)->id,
            $tagNames
        );

        $article->tags()->sync($tagIds);
    }

    /**
     * Attach article to a section for the first time (on create), positioning at the end.
     */
    private function attachToSection(HelpcenterArticle $article, int $sectionId): void
    {
        $position = DB::table('chat_helpcenter_category_article')
            ->where('category_id', $sectionId)
            ->max('position') + 1;

        $article->categories()->attach($sectionId, ['position' => $position]);
    }

    /**
     * Sync the section on update, preserving the existing pivot position.
     */
    private function syncSection(HelpcenterArticle $article, int $sectionId): void
    {
        $currentPivot = $article->categories()->first();
        $pivotPosition = $currentPivot?->pivot->position ?? 0;

        $article->categories()->sync([$sectionId => ['position' => $pivotPosition]]);
    }
}
