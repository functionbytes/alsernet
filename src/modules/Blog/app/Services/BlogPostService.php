<?php

namespace Modules\Blog\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Blog\Enums\PostStatus;
use Modules\Blog\Enums\TranslationStatus;
use Modules\Blog\Http\Controllers\BlogPostTranslationController;
use Modules\Blog\Models\BlogPost;

class BlogPostService
{
    public static function getSupportedLocales(): array
    {
        $fromSetting = setting('blog-supported-locales');

        if ($fromSetting) {
            return array_filter(array_map('trim', explode(',', $fromSetting)));
        }

        return config('blog.supported_locales', ['en', 'pt']);
    }

    public function createPost(array $data): BlogPost
    {
        return DB::transaction(function () use ($data): BlogPost {
            $data['slug'] = $this->generateSlug($data['slug'] ?? $data['title']);
            $data['user_id'] = $data['user_id'] ?? auth()->id();

            if (($data['status'] ?? null) === PostStatus::Published->value && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $post = BlogPost::query()->create($data);

            $this->syncCategories($post, $data['categories'] ?? []);
            $this->syncTags($post, $data['tags'] ?? []);
            $this->syncTranslations($post, $data['translations'] ?? []);

            return $post;
        });
    }

    public function updatePost(BlogPost $post, array $data): BlogPost
    {
        return DB::transaction(function () use ($post, $data): BlogPost {
            if (isset($data['slug']) && $data['slug'] !== $post->slug) {
                $data['slug'] = $this->generateSlug($data['slug'], $post->id);
            } elseif (isset($data['title']) && ! isset($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title'], $post->id);
            }

            $wasPublished = $post->status === PostStatus::Published;
            $becomesPublished = ($data['status'] ?? null) === PostStatus::Published->value;

            if (! $wasPublished && $becomesPublished && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $post->fill($data);
            $sourceChanged = $post->isDirty('title') || $post->isDirty('content');
            $post->save();

            if ($sourceChanged) {
                $this->markTranslationsStale($post);
            }

            $this->syncCategories($post, $data['categories'] ?? []);
            $this->syncTags($post, $data['tags'] ?? []);
            $this->syncTranslations($post, $data['translations'] ?? []);

            $this->saveVersion($post, $data['change_summary'] ?? null);

            return $post->fresh();
        });
    }

    public function syncTranslations(BlogPost $post, array $translations): void
    {
        $supported = self::getSupportedLocales();

        foreach ($translations as $locale => $transData) {
            if (! in_array($locale, $supported)) {
                continue;
            }

            $fillable = array_filter([
                'title' => $transData['title'] ?? null,
                'slug' => $transData['slug'] ?? null,
                'description' => $transData['description'] ?? null,
                'content' => $transData['content'] ?? null,
                'seo_title' => $transData['seo_title'] ?? null,
                'seo_description' => $transData['seo_description'] ?? null,
                'seo_keywords' => $transData['seo_keywords'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (empty($fillable)) {
                continue;
            }

            $existing = $post->translations()->where('locale', $locale)->first();
            $hasContentChanges = $existing && (
                ($fillable['title'] ?? null) !== $existing->title
                || ($fillable['content'] ?? null) !== $existing->content
            );

            if ($hasContentChanges && $existing->status === TranslationStatus::AutoTranslated) {
                $fillable['status'] = TranslationStatus::Manual->value;
            }

            $post->translations()->updateOrCreate(['locale' => $locale], $fillable);
        }
    }

    public function publishPost(BlogPost $post): void
    {
        $post->update([
            'status' => PostStatus::Published,
            'published_at' => $post->published_at ?? now(),
        ]);
    }

    public function unpublishPost(BlogPost $post): void
    {
        $post->update(['status' => PostStatus::Draft]);
    }

    public function deletePost(BlogPost $post): bool
    {
        if ($post->trashed()) {
            return false;
        }

        return (bool) $post->delete();
    }

    public function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title, config('blog.slug_separator', '-'));
        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function syncCategories(BlogPost $post, array $categoryIds): void
    {
        $post->categories()->sync($categoryIds);
    }

    public function syncTags(BlogPost $post, array $tagIds): void
    {
        $post->tags()->sync($tagIds);
    }

    /**
     * @return array{total: int, published: int, draft: int, pending: int}
     */
    public function getStats(): array
    {
        $row = BlogPost::query()
            ->selectRaw('COUNT(*) as total, SUM(status=?) as published, SUM(status=?) as draft, SUM(status=?) as pending', [PostStatus::Published->value, PostStatus::Draft->value, PostStatus::Pending->value])
            ->first();

        return ['total' => (int) $row->total, 'published' => (int) $row->published, 'draft' => (int) $row->draft, 'pending' => (int) $row->pending];
    }

    private function markTranslationsStale(BlogPost $post): void
    {
        $currentHash = BlogPostTranslationController::computeSourceHash($post);

        $post->translations()
            ->where('source_hash', '!=', $currentHash)
            ->where('status', '!=', TranslationStatus::Stale->value)
            ->update(['status' => TranslationStatus::Stale->value]);
    }

    private function saveVersion(BlogPost $post, ?string $changeSummary): void
    {
        $versionNumber = $post->versions()->max('version_number') + 1;

        $post->versions()->create([
            'created_by' => auth()->id() ?? $post->user_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content ?? '',
            'excerpt' => $post->description ?? '',
            'version_number' => $versionNumber,
            'change_summary' => $changeSummary,
        ]);
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        return BlogPost::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
