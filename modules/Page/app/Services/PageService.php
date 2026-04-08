<?php

namespace Modules\Page\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Locales\Models\Locale;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Events\PagePublishedForSubscribers;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTag;
use Modules\Page\Models\PageTranslation;
use Modules\Page\Notifications\PagePublishedNotification;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoRedirect;

class PageService
{
    /**
     * Get the list of supported locales.
     *
     * Priority: Locales module DB > legacy setting > config fallback.
     */
    public static function getSupportedLocales(): array
    {
        if (class_exists(Locale::class)) {
            try {
                $codes = Locale::active()->pluck('code')->toArray();

                if (! empty($codes)) {
                    return $codes;
                }
            } catch (Exception) {
                // Table may not exist yet during migrations
            }
        }

        $fromSetting = setting('page-supported-locales');

        if ($fromSetting) {
            return array_filter(array_map('trim', explode(',', $fromSetting)));
        }

        return config('page.supported_locales', ['es']);
    }

    /**
     * Sanitize HTML content to prevent XSS using HTMLPurifier.
     *
     * Allows a safe subset of block and inline elements while stripping
     * event handlers, javascript:/data: URIs, style expressions, and
     * SVG/math injection vectors that regex-based approaches miss.
     */
    public function sanitizeContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed',
            'p,br,strong,b,em,i,ul,ol,li,h1,h2,h3,h4,h5,h6,'
            .'blockquote,a[href|title|target],img[src|alt|width|height],'
            .'table,thead,tbody,tr,td[colspan|rowspan],th[colspan|rowspan],'
            .'div[class],span[class],figure,figcaption,pre,code'
        );
        $config->set('HTML.AllowedAttributes', 'a.href,a.title,a.target,img.src,img.alt,img.width,img.height,td.colspan,td.rowspan,th.colspan,th.rowspan,div.class,span.class');
        $config->set('URI.SafeIframeRegexp', null);
        $config->set('AutoFormat.RemoveEmpty', false);
        $config->set('Output.Newline', "\n");
        // Disallow javascript: and data: URIs in href/src
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        // Disable caching to avoid filesystem permission issues in shared environments
        $config->set('Cache.DefinitionImpl', null);

        $purifier = new \HTMLPurifier($config);
        $clean = $purifier->purify($content);

        // Remove auto-generated TOC (page-toc) that may have been injected
        $clean = preg_replace('/<div[^>]*class="[^"]*page-toc[^"]*"[^>]*>.*?<\/div>\s*/is', '', $clean) ?? $clean;

        return $clean;
    }

    /**
     * Create a new page.
     *
     * @throws Exception
     */
    public function createPage(array $data): Page
    {
        try {
            DB::beginTransaction();

            if (isset($data['content'])) {
                $data['content'] = $this->sanitizeContent($data['content']);
            }

            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title']);
            }

            if (Auth::check() && ! isset($data['user_id'])) {
                $data['user_id'] = Auth::id();
            }

            $data = $this->applySchedulingDefaults($data);

            $page = Page::create($data);

            if (! empty($data['translations'])) {
                $this->syncTranslations($page, $data['translations']);
            }

            SeoMeta::create([
                'seoable_type' => Page::class,
                'seoable_id' => $page->id,
                'title' => $data['title'] ?? null,
                'robots' => 'index,follow',
                'locale' => null,
            ]);

            $this->syncRelations($page, $data);

            if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $this->handleMedia($page, $data['featured_image']);
            } elseif (array_key_exists('featured_image_url', $data)) {
                $page->featured_image_url = $data['featured_image_url'] ?: null;
                $page->saveQuietly();
            }

            DB::commit();

            return $page->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing page.
     *
     * @throws Exception
     */
    public function updatePage(Page $page, array $data): Page
    {
        try {
            DB::beginTransaction();

            if (isset($data['content'])) {
                $data['content'] = $this->sanitizeContent($data['content']);
            }

            if (isset($data['title']) && $data['title'] !== $page->title && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title'], ignoreId: $page->id);
            }

            $data = $this->applySchedulingDefaults($data, $page);

            $oldSlug = $page->slug;

            $page->update($data);

            if (isset($data['translations'])) {
                if (PageCacheService::isEnabled()) {
                    PageCacheService::forgetAllLocales($oldSlug);
                }
                $this->syncTranslations($page, $data['translations']);
            }

            $this->syncRelations($page, $data);

            if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $this->handleMedia($page, $data['featured_image']);
            }

            DB::commit();

            return $page->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a page.
     *
     * @throws Exception
     */
    public function deletePage(Page $page): bool
    {
        $this->invalidateStatsCache();

        try {
            DB::beginTransaction();

            $page->clearMediaCollection('featured');
            $page->clearMediaCollection('gallery');

            $deleted = $page->delete();

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Force delete a page permanently.
     *
     * @throws Exception
     */
    public function forceDeletePage(Page $page): bool
    {
        $this->invalidateStatsCache();

        try {
            DB::beginTransaction();

            $page->clearMediaCollection('featured');
            $page->clearMediaCollection('gallery');

            $deleted = $page->forceDelete();

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted page.
     */
    public function restorePage(Page $page): bool
    {
        $this->invalidateStatsCache();

        return $page->restore();
    }

    /**
     * Handle media upload for a page.
     */
    public function handleMedia(Page $page, UploadedFile $file, string $collection = 'featured'): void
    {
        $page->addMedia($file)
            ->toMediaCollection($collection);
    }

    /**
     * Generate a unique slug from a title or base slug.
     *
     * Consolidates generateSlug(), generateSlugForLocale(), and uniqueSlug() into one method.
     * When $locale is provided, uniqueness is checked against page_translations for that locale.
     * When $ignoreId is provided, that page ID is excluded from the uniqueness check.
     */
    public function generateUniqueSlug(string $title, ?string $locale = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $candidate = $base;
        $counter = 1;

        while ($this->slugExists($candidate, $locale, $ignoreId)) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }

    /**
     * Publish a page and notify configured admin emails and subscribers.
     */
    public function publishPage(Page $page): Page
    {
        $this->invalidateStatsCache();

        $page->publish();

        $fresh = $page->fresh();

        $this->handleNotifications($fresh);
        app(PageWebhookService::class)->dispatch('published', $fresh);

        if ($fresh->notify_subscribers) {
            event(new PagePublishedForSubscribers($fresh));
        }

        return $fresh;
    }

    /**
     * Unpublish a page.
     */
    public function unpublishPage(Page $page): Page
    {
        $slug = $page->slug;

        $page->unpublish();

        if (PageCacheService::isEnabled()) {
            PageCacheService::forgetAllLocales($slug);
        }

        return $page->fresh();
    }

    /**
     * Duplicate a page.
     */
    public function duplicatePage(Page $page): Page
    {
        $page->load('translations');

        $newPage = $page->replicate();
        $newPage->title = $page->title.' (Copy)';
        $newPage->slug = $this->generateUniqueSlug($newPage->title);
        $newPage->status = PageStatus::Draft->value;
        $newPage->published_at = null;
        $newPage->save();

        foreach ($page->translations as $translation) {
            $newPage->translations()->create([
                'locale_id' => $translation->locale_id,
                'title' => ($translation->title ?? $newPage->title).' (Copy)',
                'slug' => $this->generateUniqueSlug(
                    ($translation->slug ?? $newPage->slug).'-copy',
                    $translation->locale
                ),
                'content' => $this->sanitizeContent($translation->content),
                'description' => $translation->description,
                'seo_title' => $translation->seo_title,
                'seo_description' => $translation->seo_description,
                'seo_keywords' => $translation->seo_keywords,
                'seo_image_url' => $translation->seo_image_url,
                'seo_noindex' => $translation->seo_noindex,
                'status' => PageStatus::Draft->value,
                'published_at' => null,
            ]);
        }

        if ($page->hasMedia('featured')) {
            $media = $page->getFirstMedia('featured');
            $newPage->addMedia($media->getPath())
                ->preservingOriginal()
                ->toMediaCollection('featured');
        }

        return $newPage;
    }

    /**
     * Get page status counts from cache (10-minute TTL).
     *
     * @return array{total: int, published: int, draft: int, pending: int, trashed: int}
     */
    public function getStatsCache(): array
    {
        return Cache::remember('pages:stats:counts', now()->addMinutes(10), function () {
            $counts = Page::query()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return [
                'total' => $counts->sum(),
                'published' => $counts->get('published', 0),
                'draft' => $counts->get('draft', 0),
                'pending' => $counts->get('pending', 0),
                'trashed' => Page::onlyTrashed()->count(),
            ];
        });
    }

    /**
     * Get pages with filters.
     *
     * @return LengthAwarePaginator
     */
    public function getPages(array $filters = [])
    {
        $query = Page::with([
            'user:id,firstname,lastname,email',
            'translations:id,page_id,locale_id,slug,status',
            'categories:id,name,color,slug',
            'tags:id,name,slug',
        ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['template'])) {
            $query->where('template', $filters['template']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $allowedSorts = ['created_at', 'updated_at', 'published_at', 'title', 'status', 'views_count', 'order'];
        $sortBy = in_array($filters['sort_by'] ?? 'created_at', $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? config('page.per_page', 20);

        return $query->paginate($perPage);
    }

    /**
     * Get soft-deleted (trashed) pages with filters.
     *
     * @return LengthAwarePaginator
     */
    public function getTrashedPages(array $filters = [])
    {
        $query = Page::onlyTrashed()->with([
            'user:id,firstname,lastname,email',
            'translations:id,page_id,locale_id,slug,status',
            'categories:id,name,color,slug',
            'tags:id,name,slug',
        ]);

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('deleted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('deleted_at', '<=', $filters['date_to']);
        }

        $query->orderBy('deleted_at', 'desc');

        return $query->paginate($filters['per_page'] ?? config('page.per_page', 20));
    }

    /**
     * Sync both categories and tags for a page from the given data array.
     */
    private function syncRelations(Page $page, array $data): void
    {
        $this->syncCategories($page, $data['categories'] ?? null);
        $this->syncTags($page, $data['tags_input'] ?? null);
    }

    /**
     * Dispatch publish notification to configured admin emails (if enabled).
     */
    private function handleNotifications(Page $page): void
    {
        if (! config('page.notifications.on_publish', false)) {
            return;
        }

        $emails = config('page.notifications.publish_notify_emails', []);

        if (empty($emails)) {
            return;
        }

        $publishedBy = Auth::user()?->name ?? 'Sistema';
        $notification = new PagePublishedNotification($page, $publishedBy, 'manual');

        collect($emails)->each(
            fn (string $email) => Notification::route('mail', $email)->notify($notification)
        );
    }

    /**
     * Forget the pages stats cache key.
     */
    private function invalidateStatsCache(): void
    {
        Cache::forget('pages:stats:counts');
    }

    /**
     * Apply publish/draft scheduling defaults to the data array.
     *
     * On create, $existing is null. On update, $existing is the current page model.
     */
    private function applySchedulingDefaults(array $data, ?Page $existing = null): array
    {
        $status = $data['status'] ?? null;

        if ($status === PageStatus::Published->value) {
            // Set published_at when first publishing
            $alreadyPublished = $existing && $existing->status === PageStatus::Published;
            if (! $alreadyPublished && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            // Clear scheduled publish_at since we are publishing immediately
            if ($existing === null || $existing->publish_at) {
                $data['publish_at'] = null;
            }
        }

        if ($status === PageStatus::Draft->value) {
            // Clear scheduled unpublish when reverting to draft
            if ($existing === null || $existing->unpublish_at) {
                $data['unpublish_at'] = null;
            }
        }

        return $data;
    }

    /**
     * Check if a slug exists, optionally scoped to a locale translation table.
     */
    private function slugExists(string $slug, ?string $locale, ?int $ignoreId): bool
    {
        if ($locale !== null) {
            return PageTranslation::query()
                ->forLocale($locale)
                ->where('slug', $slug)
                ->exists();
        }

        $query = Page::query()->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Sync translations for a page.
     */
    private function syncTranslations(Page $page, array $translations): void
    {
        $supported = self::getSupportedLocales();
        $localeIdMap = $this->getLocaleIdMap();

        foreach ($translations as $locale => $data) {
            if (! in_array($locale, $supported)) {
                continue;
            }

            // SEO fields removed — managed via seo_metas (SeoMetaWebController)
            $fillable = array_filter([
                'title' => $data['title'] ?? null,
                'slug' => $data['slug'] ?? null,
                'content' => $this->sanitizeContent($data['content'] ?? null),
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? null,
                'published_at' => isset($data['status']) && $data['status'] === PageStatus::Published->value
                    ? ($data['published_at'] ?? now()->toDateTimeString())
                    : ($data['published_at'] ?? null),
                'locale_id' => $localeIdMap[$locale] ?? null,
            ], fn ($v) => $v !== null);

            if (empty($fillable)) {
                continue;
            }

            if (! isset($localeIdMap[$locale])) {
                continue;
            }

            $newSlug = $fillable['slug'] ?? null;
            if ($newSlug) {
                $existingTrans = $page->translations()->forLocale($locale)->first();
                $oldSlug = $existingTrans?->slug;
                if ($oldSlug && $oldSlug !== $newSlug) {
                    $slugStillInUse = $page->translations()
                        ->notLocale($locale)
                        ->where('slug', $oldSlug)
                        ->exists();
                    if (! $slugStillInUse) {
                        $this->handleRedirectCreation($oldSlug, $newSlug);
                    }
                }
            }

            $page->translations()->updateOrCreate(['locale_id' => $localeIdMap[$locale]], $fillable);

            if (($fillable['status'] ?? '') === PageStatus::Published->value && PageCacheService::isEnabled()) {
                $page->load('translations');
                PageCacheService::set($page, $locale);
            }
        }
    }

    /**
     * Returns a map of locale code => locale_id from the Locales module.
     *
     * @return array<string, int>
     */
    private function getLocaleIdMap(): array
    {
        if (! class_exists(Locale::class)) {
            return [];
        }

        try {
            return Locale::query()
                ->pluck('id', 'code')
                ->all();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Create a 301 SEO redirect when a slug changes (if the Seo module is available).
     */
    private function handleRedirectCreation(string $oldSlug, string $newSlug): void
    {
        if (! class_exists(SeoRedirect::class)) {
            return;
        }

        $prefix = setting('permalink-modules-page-models-page', '');
        $sourcePath = '/'.($prefix ? $prefix.'/'.$oldSlug : $oldSlug);
        $targetPath = '/'.($prefix ? $prefix.'/'.$newSlug : $newSlug);

        $exists = SeoRedirect::where('source_path', $sourcePath)->exists();

        if (! $exists) {
            SeoRedirect::create([
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'status_code' => 301,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Sync categories for a page.
     *
     * @param  array<int>|null  $categoryIds
     */
    private function syncCategories(Page $page, ?array $categoryIds): void
    {
        if ($categoryIds === null) {
            return;
        }

        $page->categories()->sync(array_filter(array_map('intval', $categoryIds)));
    }

    /**
     * Sync tags for a page from a comma-separated string.
     */
    private function syncTags(Page $page, ?string $tagsInput): void
    {
        if ($tagsInput === null) {
            return;
        }

        $tagNames = array_filter(array_map('trim', explode(',', $tagsInput)));

        $tagIds = collect($tagNames)->map(fn (string $name) => PageTag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        )->id);

        $page->tags()->sync($tagIds->all());
    }
}
