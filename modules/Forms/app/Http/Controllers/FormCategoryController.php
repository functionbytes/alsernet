<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Forms\Models\FormCategory;

class FormCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('Forms.categories.manage');

        $query = FormCategory::query()->withCount('forms');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->ordered()->get();

        $stats = [
            'total' => FormCategory::query()->count(),
            'active' => FormCategory::query()->where('is_active', true)->count(),
            'inactive' => FormCategory::query()->where('is_active', false)->count(),
            'with_forms' => FormCategory::query()->has('forms')->count(),
        ];

        return view('forms::settings.categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('Forms.categories.manage');

        return view('forms::settings.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('Forms.categories.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['sort_order'] = FormCategory::query()->max('sort_order') + 1;

        FormCategory::query()->create($data);

        session()->flash('success', 'Categoría creada correctamente.');

        return redirect()->route('settings.forms.categories.index');
    }

    public function edit(FormCategory $category): View
    {
        $this->authorize('Forms.categories.manage');

        return view('forms::settings.categories.edit', compact('category'));
    }

    public function update(Request $request, FormCategory $category): RedirectResponse
    {
        $this->authorize('Forms.categories.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $category->update($data);

        session()->flash('success', 'Categoría actualizada correctamente.');

        return redirect()->route('settings.forms.categories.index');
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('Forms.categories.manage');

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $categories = FormCategory::whereIn('id', $validated['ids'])->get();
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

    public function destroy(FormCategory $category): RedirectResponse
    {
        $this->authorize('Forms.categories.manage');

        if ($category->forms()->where('is_active', true)->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar la categoría porque tiene formularios activos.');
        }

        $category->delete();

        session()->flash('success', 'Categoría eliminada correctamente.');

        return redirect()->route('settings.forms.categories.index');
    }

    public function toggle(FormCategory $category): JsonResponse
    {
        $this->authorize('Forms.categories.manage');

        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success' => true,
            'message' => $category->is_active ? 'Categoría activada' : 'Categoría desactivada',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('Forms.categories.manage');

        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            FormCategory::query()
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

        while (FormCategory::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
