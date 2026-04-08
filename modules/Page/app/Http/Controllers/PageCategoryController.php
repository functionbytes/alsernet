<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageCategory;

class PageCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('update', Page::class);

        $query = PageCategory::query()->withCount('pages');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->ordered()->get();

        $stats = [
            'total' => PageCategory::query()->count(),
            'active' => PageCategory::query()->where('is_active', true)->count(),
            'inactive' => PageCategory::query()->where('is_active', false)->count(),
            'with_pages' => PageCategory::query()->has('pages')->count(),
        ];

        return view('page::pages.categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('update', Page::class);

        return view('page::pages.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', Page::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['sort_order'] = PageCategory::query()->max('sort_order') + 1;

        PageCategory::query()->create($data);

        session()->flash('success', 'Categoría creada correctamente.');

        return redirect()->route('pages.categories.index');
    }

    public function edit(PageCategory $category): View
    {
        $this->authorize('update', Page::class);

        $category->loadCount('pages');

        return view('page::pages.categories.edit', compact('category'));
    }

    public function update(Request $request, PageCategory $category): RedirectResponse
    {
        $this->authorize('update', Page::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $category->update($data);

        session()->flash('success', 'Categoría actualizada correctamente.');

        return redirect()->route('pages.categories.index');
    }

    public function destroy(PageCategory $category): RedirectResponse
    {
        $this->authorize('update', Page::class);

        if ($category->pages()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar la categoría porque tiene páginas asociadas.');
        }

        $category->delete();

        session()->flash('success', 'Categoría eliminada correctamente.');

        return redirect()->route('pages.categories.index');
    }

    public function toggle(PageCategory $category): JsonResponse
    {
        $this->authorize('update', Page::class);

        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success' => true,
            'message' => $category->is_active ? 'Categoría activada' : 'Categoría desactivada',
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('update', Page::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = PageCategory::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($categories as $category) {
            match ($validated['action']) {
                'activate' => $category->update(['is_active' => true]),
                'deactivate' => $category->update(['is_active' => false]),
                'delete' => $category->delete(),
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('update', Page::class);

        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            PageCategory::query()
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (PageCategory::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
