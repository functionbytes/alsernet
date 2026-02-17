<?php

namespace Modules\Menu\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Services\MenuService;

class MenuController extends Controller
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Display a listing of menus.
     */
    public function index()
    {
        $menus = Menu::withCount('allItems')->latest()->get();

        return view('menu::index', compact('menus'));
    }

    /**
     * Show the form for creating a new menu.
     */
    public function create()
    {
        $locations = config('menu.locations');

        return view('menu::create', compact('locations'));
    }

    /**
     * Store a newly created menu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:menus,slug',
            'location' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if (! isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $menu = $this->menuService->createMenu($validated);

        return redirect()->route('menu.edit', $menu)
            ->with('success', 'Menu created successfully.');
    }

    /**
     * Show the form for editing the menu.
     */
    public function edit(Menu $menu)
    {
        $menu->load(['items' => function ($query) {
            $query->with('children');
        }]);

        $locations = config('menu.locations');
        $references = $this->menuService->getAvailableReferences();

        return view('menu::edit', compact('menu', 'locations', 'references'));
    }

    /**
     * Update the specified menu.
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:menus,slug,'.$menu->id,
            'location' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $this->menuService->updateMenu($menu, $validated);

        return redirect()->route('menu.edit', $menu)
            ->with('success', 'Menu updated successfully.');
    }

    /**
     * Remove the specified menu.
     */
    public function destroy(Menu $menu)
    {
        $this->menuService->deleteMenu($menu);

        return redirect()->route('menu.index')
            ->with('success', 'Menu deleted successfully.');
    }

    /**
     * Update menu structure (drag & drop).
     */
    public function updateStructure(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'items' => 'required|array',
        ]);

        $this->menuService->updateMenuStructure($menu, $validated['items']);

        return response()->json([
            'success' => true,
            'message' => 'Menu structure updated successfully.',
        ]);
    }

    /**
     * Store a new menu item.
     */
    public function storeItem(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:menu_items,id',
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank,_parent,_top',
            'icon' => 'nullable|string|max:255',
            'css_class' => 'nullable|string|max:255',
            'type' => 'required|string|in:custom,page,post,category,route',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string',
        ]);

        $menuItem = $this->menuService->addMenuItem($menu, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully.',
            'item' => $menuItem->load('children'),
        ]);
    }

    /**
     * Update a menu item.
     */
    public function updateItem(Request $request, Menu $menu, MenuItem $item)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank,_parent,_top',
            'icon' => 'nullable|string|max:255',
            'css_class' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:custom,page,post,category,route',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string',
        ]);

        $menuItem = $this->menuService->updateMenuItem($item, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item updated successfully.',
            'item' => $menuItem->load('children'),
        ]);
    }

    /**
     * Remove a menu item.
     */
    public function destroyItem(Menu $menu, MenuItem $item)
    {
        $this->menuService->deleteMenuItem($item);

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully.',
        ]);
    }
}
