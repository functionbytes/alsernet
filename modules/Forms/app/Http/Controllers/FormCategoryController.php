<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\BulkActionFormCategoryRequest;
use Modules\Forms\Http\Requests\ReorderFormCategoryRequest;
use Modules\Forms\Http\Requests\StoreFormCategoryRequest;
use Modules\Forms\Http\Requests\UpdateFormCategoryRequest;
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

        $categories = $query->ordered()->paginate(15)->withQueryString();

        $stats = FormCategory::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
            ')
            ->first();

        $withForms = FormCategory::has('forms')->count();

        $stats = [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'inactive' => (int) $stats->inactive,
            'with_forms' => $withForms,
        ];

        return view('forms::settings.categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('Forms.categories.manage');

        return view('forms::settings.categories.create');
    }

    public function store(StoreFormCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['sort_order'] = FormCategory::query()->max('sort_order') + 1;

        FormCategory::query()->create($data);

        session()->flash('success', __('forms::messages.category.created'));

        return redirect()->route('settings.forms.categories.index');
    }

    public function edit(FormCategory $category): View
    {
        $this->authorize('Forms.categories.manage');

        return view('forms::settings.categories.edit', compact('category'));
    }

    public function update(UpdateFormCategoryRequest $request, FormCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        session()->flash('success', __('forms::messages.category.updated'));

        return redirect()->route('settings.forms.categories.index');
    }

    public function bulkAction(BulkActionFormCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ids = $validated['ids'];

        $count = DB::transaction(function () use ($validated, $ids): int {
            match ($validated['action']) {
                'activate' => FormCategory::query()->whereIn('id', $ids)->update(['is_active' => true]),
                'deactivate' => FormCategory::query()->whereIn('id', $ids)->update(['is_active' => false]),
                'delete' => FormCategory::query()->whereIn('id', $ids)->get()->each->delete(),
            };

            return count($ids);
        });

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function destroy(FormCategory $category): RedirectResponse
    {
        $this->authorize('Forms.categories.manage');

        if ($category->forms()->where('is_active', true)->exists()) {
            return redirect()->back()
                ->with('error', __('forms::messages.category.delete_has_active_forms'));
        }

        $category->delete();

        session()->flash('success', __('forms::messages.category.deleted'));

        return redirect()->route('settings.forms.categories.index');
    }

    public function toggle(FormCategory $category): JsonResponse
    {
        $this->authorize('Forms.categories.manage');

        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success' => true,
            'message' => $category->is_active ? __('forms::messages.category.activated') : __('forms::messages.category.deactivated'),
        ]);
    }

    public function reorder(ReorderFormCategoryRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated()['items'] as $item) {
                FormCategory::query()
                    ->where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

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
