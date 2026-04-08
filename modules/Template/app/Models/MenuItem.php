<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Post;
use Modules\Page\Models\Page;
use Modules\Template\Database\Factories\MenuItemFactory;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    /** @var Page|Post|Category|null Cached resolved reference model (per request). */
    private mixed $resolvedReference = false;

    protected static function newFactory(): MenuItemFactory
    {
        return MenuItemFactory::new();
    }

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'target',
        'icon',
        'css_class',
        'order',
        'has_child',
        'type',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'order' => 'integer',
        'has_child' => 'boolean',
    ];

    protected $appends = [];

    /**
     * Get the menu that owns the item.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order')->orderBy('id');
    }

    /**
     * Get the full URL for the menu item.
     *
     * Uses a direct model lookup instead of morphTo() because reference_type
     * stores a short alias (e.g. 'page') rather than a fully-qualified class name.
     */
    /**
     * Get the locale-aware display title.
     *
     * When the item references a Page/Post/Category, returns the translated title
     * for the current locale. Falls back to the stored title.
     */
    public function getDisplayTitleAttribute(): string
    {
        $model = $this->resolveReference();

        if ($model instanceof Page) {
            $locale = app()->getLocale();
            $translation = $model->translations()
                ->forLocale($locale)
                ->whereNotNull('title')
                ->first();

            $rawTitle = $translation?->title ?? $model->title ?? $this->title;

            // Strip site name suffix (e.g. "Mosquiteiras em Alcobaça | Caixilharia Blanco" → "Mosquiteiras em Alcobaça")
            return str_contains($rawTitle, ' | ')
                ? trim(explode(' | ', $rawTitle)[0])
                : $rawTitle;
        }

        return $this->title;
    }

    public function getFullUrlAttribute(): string
    {
        $model = $this->resolveReference();

        if ($model) {
            if ($model instanceof Page) {
                return $this->resolvePageUrl($model);
            }

            return $model->url ?? $model->full_url ?? $this->url ?? '#';
        }

        return $this->url ?: '#';
    }

    /**
     * Resolve and cache the referenced model (Page, Post, or Category).
     * Returns null if no reference is set or the model doesn't exist.
     */
    private function resolveReference(): mixed
    {
        if ($this->resolvedReference !== false) {
            return $this->resolvedReference;
        }

        if (! $this->reference_type || ! $this->reference_id) {
            return $this->resolvedReference = null;
        }

        return $this->resolvedReference = match ($this->reference_type) {
            Page::class => Page::with('translations.localeModel')->find($this->reference_id),
            Post::class => Post::find($this->reference_id),
            Category::class => Category::find($this->reference_id),
            default => null,
        };
    }

    /**
     * Resolve the locale-aware URL for a Page reference.
     *
     * Prefers the published translation for the current locale.
     * Falls back to the page's default URL.
     */
    private function resolvePageUrl(Page $page): string
    {
        if ($page->template === 'homepage') {
            return url('/');
        }

        $locale = app()->getLocale();
        $prefix = setting('permalink-modules-page-models-page', '');

        $translation = $page->translations()
            ->forLocale($locale)
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->first();

        if ($translation) {
            $path = $prefix ? "{$prefix}/{$translation->slug}" : $translation->slug;

            return url("/{$path}");
        }

        return $page->url;
    }

    /**
     * Check if the menu item has children.
     */
    public function hasChildren(): bool
    {
        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        return $this->children()->exists();
    }

    /**
     * Check if the menu item matches the current request URL.
     * Handles trailing slashes, query params, and http/https differences.
     */
    public function isActive(): bool
    {
        $itemUrl = rtrim($this->full_url, '/');
        $currentUrl = rtrim(request()->url(), '/');
        $currentPath = rtrim(request()->path(), '/');

        if ($itemUrl === $currentUrl) {
            return true;
        }

        $itemPath = rtrim(parse_url($itemUrl, PHP_URL_PATH) ?? $itemUrl, '/');

        return $itemPath !== '' && $itemPath === '/'.ltrim($currentPath, '/');
    }

    /**
     * Check if any child is active.
     */
    public function hasActiveChild(): bool
    {
        foreach ($this->children as $child) {
            if ($child->isActive() || $child->hasActiveChild()) {
                return true;
            }
        }

        return false;
    }
}
