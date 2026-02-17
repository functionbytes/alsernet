<?php

namespace Modules\Menu\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

class MenuService
{
    /**
     * Render a menu by location.
     */
    public function renderMenu(string $location, array $attributes = []): string
    {
        $menu = Cache::remember("menu.{$location}", 3600, function () use ($location) {
            return Menu::where('location', $location)
                ->where('status', true)
                ->with(['items' => function ($query) {
                    $query->with('children');
                }])
                ->first();
        });

        if (! $menu) {
            return '';
        }

        $class = $attributes['class'] ?? '';
        $html = "<ul class=\"{$class}\">";

        foreach ($menu->items as $item) {
            $html .= $this->renderMenuItem($item);
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Render a single menu item.
     */
    protected function renderMenuItem(MenuItem $item, int $depth = 0): string
    {
        $maxDepth = config('menu.max_depth', 3);

        if ($depth >= $maxDepth) {
            return '';
        }

        $hasChildren = $item->children->isNotEmpty();
        $isActive = $item->isActive() || $item->hasActiveChild();
        $activeClass = $isActive ? 'active' : '';
        $cssClass = trim("{$item->css_class} {$activeClass}");

        $html = '<li';
        if ($cssClass) {
            $html .= " class=\"{$cssClass}\"";
        }
        $html .= '>';

        $html .= "<a href=\"{$item->full_url}\" target=\"{$item->target}\"";
        if ($cssClass) {
            $html .= " class=\"{$cssClass}\"";
        }
        $html .= '>';

        if ($item->icon) {
            $html .= "<i class=\"{$item->icon}\"></i> ";
        }

        $html .= htmlspecialchars($item->title);
        $html .= '</a>';

        if ($hasChildren) {
            $html .= '<ul>';
            foreach ($item->children as $child) {
                $html .= $this->renderMenuItem($child, $depth + 1);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Update menu structure from drag & drop.
     * Delegates to recursiveSaveMenu for full tree persistence.
     */
    public function updateMenuStructure(Menu $menu, array $items): void
    {
        $this->recursiveSaveMenu($items, $menu->id);
        $this->clearMenuCache($menu->location);
    }

    /**
     * Recursively persist the full nestable menu tree.
     *
     * Each node may carry an `id` (existing DB id or "new-xxx" string for new items).
     * Handles creation and update in a single traversal.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     */
    public function recursiveSaveMenu(array $nodes, int $menuId, int $parentId = 0): void
    {
        foreach ($nodes as $index => $node) {
            $rawId = $node['id'] ?? null;

            // IDs like "new-123" are new items; cast only numeric strings to int
            $isNew = ! $rawId || ! is_numeric($rawId);
            $menuItem = $isNew ? new MenuItem : MenuItem::findOrNew((int) $rawId);

            $menuItem->menu_id = $menuId;
            $menuItem->parent_id = $parentId ?: null;
            $menuItem->order = $index;
            $menuItem->has_child = count($node['children'] ?? []) > 0;

            $this->resolveNodeUrl($node, $menuItem);

            $menuItem->save();

            if (! empty($node['children'])) {
                $this->recursiveSaveMenu($node['children'], $menuId, $menuItem->id);
            }
        }
    }

    /**
     * Resolve and assign the URL for a menu item node.
     *
     * - type === 'custom' or empty reference_type: stores literal url, clears reference fields.
     * - reference_type present: resolves the referenced model and extracts its URL.
     *
     * @param  array<string, mixed>  $item
     */
    public function resolveNodeUrl(array $item, MenuItem $node): MenuItem
    {
        $node->title = $item['title'] ?? $node->title;
        $node->target = $item['target'] ?? $node->target ?? '_self';
        $node->icon = $item['icon'] ?? $node->icon;
        $node->css_class = $item['css_class'] ?? $node->css_class;
        $node->type = $item['type'] ?? $node->type ?? 'custom';

        $referenceType = $item['reference_type'] ?? null;

        if (! $referenceType || ($item['type'] ?? '') === 'custom') {
            $node->url = $item['url'] ?? $node->url;
            $node->reference_id = null;
            $node->reference_type = null;

            return $node;
        }

        $referenceId = $item['reference_id'] ?? null;

        if ($referenceId && class_exists($referenceType)) {
            $referenced = $referenceType::find($referenceId);

            if ($referenced) {
                $node->url = $referenced->full_url ?? $referenced->slug ?? $node->url;
            }
        }

        $node->reference_id = $referenceId;
        $node->reference_type = $referenceType;

        return $node;
    }

    /**
     * Add a new menu item.
     */
    public function addMenuItem(Menu $menu, array $data): MenuItem
    {
        $order = $menu->allItems()->max('order') + 1;

        $menuItem = $menu->allItems()->create([
            'parent_id' => $data['parent_id'] ?? null,
            'title' => $data['title'],
            'url' => $data['url'] ?? null,
            'target' => $data['target'] ?? '_self',
            'icon' => $data['icon'] ?? null,
            'css_class' => $data['css_class'] ?? null,
            'order' => $order,
            'type' => $data['type'] ?? 'custom',
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
        ]);

        $this->clearMenuCache($menu->location);

        return $menuItem;
    }

    /**
     * Update a menu item.
     */
    public function updateMenuItem(MenuItem $menuItem, array $data): MenuItem
    {
        $menuItem->update([
            'title' => $data['title'] ?? $menuItem->title,
            'url' => $data['url'] ?? $menuItem->url,
            'target' => $data['target'] ?? $menuItem->target,
            'icon' => $data['icon'] ?? $menuItem->icon,
            'css_class' => $data['css_class'] ?? $menuItem->css_class,
            'type' => $data['type'] ?? $menuItem->type,
            'reference_id' => $data['reference_id'] ?? $menuItem->reference_id,
            'reference_type' => $data['reference_type'] ?? $menuItem->reference_type,
        ]);

        $this->clearMenuCache($menuItem->menu->location);

        return $menuItem;
    }

    /**
     * Delete a menu item.
     */
    public function deleteMenuItem(MenuItem $menuItem): void
    {
        $location = $menuItem->menu->location;
        $menuItem->delete();
        $this->clearMenuCache($location);
    }

    /**
     * Get available references for menu items.
     */
    public function getAvailableReferences(): array
    {
        $references = [];

        // Pages
        if (class_exists(\Modules\Page\Models\Page::class)) {
            $pages = \Modules\Page\Models\Page::where('status', 'published')
                ->select('id', 'title', 'slug')
                ->get()
                ->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'title' => $page->title,
                        'type' => 'page',
                        'reference_type' => \Modules\Page\Models\Page::class,
                    ];
                });

            $references['pages'] = $pages;
        }

        // Posts
        if (class_exists(\Modules\Blog\Models\Post::class)) {
            $posts = \Modules\Blog\Models\Post::where('status', 'published')
                ->select('id', 'title', 'slug')
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'type' => 'post',
                        'reference_type' => \Modules\Blog\Models\Post::class,
                    ];
                });

            $references['posts'] = $posts;
        }

        // Categories
        if (class_exists(\Modules\Blog\Models\Category::class)) {
            $categories = \Modules\Blog\Models\Category::select('id', 'name', 'slug')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'title' => $category->name,
                        'type' => 'category',
                        'reference_type' => \Modules\Blog\Models\Category::class,
                    ];
                });

            $references['categories'] = $categories;
        }

        return $references;
    }

    /**
     * Clear menu cache.
     */
    public function clearMenuCache(?string $location = null): void
    {
        if ($location) {
            Cache::forget("menu.{$location}");
        } else {
            Cache::flush();
        }
    }

    /**
     * Create a menu with items.
     */
    public function createMenu(array $data): Menu
    {
        $menu = Menu::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'location' => $data['location'] ?? null,
            'status' => $data['status'] ?? true,
        ]);

        return $menu;
    }

    /**
     * Update a menu.
     */
    public function updateMenu(Menu $menu, array $data): Menu
    {
        $oldLocation = $menu->location;

        $menu->update([
            'name' => $data['name'] ?? $menu->name,
            'slug' => $data['slug'] ?? $menu->slug,
            'location' => $data['location'] ?? $menu->location,
            'status' => $data['status'] ?? $menu->status,
        ]);

        $this->clearMenuCache($oldLocation);
        $this->clearMenuCache($menu->location);

        return $menu;
    }

    /**
     * Delete a menu.
     */
    public function deleteMenu(Menu $menu): void
    {
        $location = $menu->location;
        $menu->delete();
        $this->clearMenuCache($location);
    }
}
