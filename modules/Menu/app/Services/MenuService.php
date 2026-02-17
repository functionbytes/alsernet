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
     */
    public function updateMenuStructure(Menu $menu, array $items): void
    {
        $this->updateMenuItems($items, $menu->id);
        $this->clearMenuCache($menu->location);
    }

    /**
     * Recursively update menu items.
     */
    protected function updateMenuItems(array $items, int $menuId, ?int $parentId = null, int $order = 0): void
    {
        foreach ($items as $index => $item) {
            $menuItem = MenuItem::find($item['id']);

            if ($menuItem && $menuItem->menu_id == $menuId) {
                $menuItem->update([
                    'parent_id' => $parentId,
                    'order' => $order + $index,
                ]);

                if (isset($item['children']) && is_array($item['children'])) {
                    $this->updateMenuItems($item['children'], $menuId, $menuItem->id, 0);
                }
            }
        }
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
