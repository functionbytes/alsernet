<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Template\Http\Requests\StoreMenuRequest;
use Modules\Template\Http\Requests\UpdateMenuRequest;
use Modules\Template\Models\Menu;
use Modules\Template\Models\MenuItem;
use Modules\Template\Services\MenuService;

class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService
    ) {}

    /**
     * Display a listing of menus.
     */
    public function index()
    {
        $this->authorize('viewAny', Menu::class);

        $menus = Menu::query()->withCount('allItems')->latest()->get();

        $stats = [
            'total' => $menus->count(),
            'active' => $menus->where('status', true)->count(),
            'inactive' => $menus->where('status', false)->count(),
            'without_location' => $menus->whereNull('location')->count(),
        ];

        return view('template::settings.menus.index', compact('menus', 'stats'));
    }

    /**
     * Show the form for creating a new menu.
     */
    public function create()
    {
        $this->authorize('create', Menu::class);

        $locations = config('template.menu.locations');

        return view('template::settings.menus.create', compact('locations'));
    }

    /**
     * Store a newly created menu.
     */
    public function store(StoreMenuRequest $request)
    {
        $this->authorize('create', Menu::class);

        $validated = $request->validated();

        if (! isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $menu = $this->menuService->createMenu($validated);

        return redirect()->route('settings.menus.edit', $menu)
            ->with('success', 'Menu created successfully.');
    }

    /**
     * Show the form for editing the menu.
     */
    public function edit(Menu $menu)
    {
        $this->authorize('update', $menu);

        $menu->load(['items' => function ($query) {
            $query->with('children');
        }]);

        $locations = config('template.menu.locations');
        $references = $this->menuService->getAvailableReferences();

        return view('template::settings.menus.edit', compact('menu', 'locations', 'references'));
    }

    /**
     * Update the specified menu, its config, and its full item tree.
     *
     * Expects:
     *   - Standard menu fields (name, slug, location, status)
     *   - `menu_nodes`    JSON string representing the full nestable tree
     *   - `deleted_nodes` Space-separated string of item IDs to delete
     */
    public function update(UpdateMenuRequest $request, Menu $menu): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $menu);

        // Step 1: Update menu config
        $this->menuService->updateMenu($menu, $request->validated());

        // Step 2: Delete removed items
        $deletedIds = array_filter(explode(' ', $request->input('deleted_nodes', '')));

        if ($deletedIds) {
            $menu->allItems()->whereIn('id', $deletedIds)->delete();
        }

        // Step 3: Persist the full tree recursively
        $nodesJson = $request->input('menu_nodes');

        if ($nodesJson) {
            $nodes = json_decode($nodesJson, true) ?? [];
            $this->menuService->recursiveSaveMenu($nodes, $menu->id);
        }

        // Step 4: Clear cache
        $this->menuService->clearMenuCache($menu->location);

        return redirect()->route('settings.menus.edit', $menu)
            ->with('success', 'Menu actualizado correctamente.');
    }

    /**
     * Resolve a new node's URL and return its rendered HTML partial.
     *
     * GET /settings/menus/{menu}/node?data[type]=page&data[reference_id]=5&data[title]=Home
     */
    public function getNode(Request $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', $menu);

        $request->validate(['data' => 'required|array']);

        $data = $request->input('data');

        $menuItem = new MenuItem;
        $menuItem->fill($data);
        $this->menuService->resolveNodeUrl($data, $menuItem);
        $menuItem->menu_id = $menu->id;
        $menuItem->id = 'new-'.uniqid();

        $html = view('template::partials.menu-node', [
            'item' => $menuItem,
            'menu' => $menu,
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Remove the specified menu.
     */
    public function destroy(Menu $menu)
    {
        $this->authorize('delete', $menu);

        $this->menuService->deleteMenu($menu);

        return redirect()->route('settings.menus.index')
            ->with('success', 'Menu deleted successfully.');
    }

    /**
     * Update menu structure (drag & drop).
     */
    public function updateStructure(Request $request, Menu $menu)
    {
        $this->authorize('update', $menu);

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
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
            'css_class' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
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
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
            'css_class' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
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
        $this->authorize('update', $menu);

        $this->menuService->deleteMenuItem($item);

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully.',
        ]);
    }
}
