<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Template\Models\Shortcode;
use Modules\Template\Models\ShortcodeCategory;

class ShortcodeCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ShortcodeCategory::query()->orderBy('sort_order')->get();

        $row = ShortcodeCategory::selectRaw('
            COUNT(*) as total,
            SUM(is_active = 1) as active,
            SUM(is_active = 0) as inactive
        ')->first();

        $stats = [
            'total' => (int) $row->total,
            'active' => (int) $row->active,
            'inactive' => (int) $row->inactive,
        ];

        return view('template::shortcode-categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        return view('template::shortcode-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ShortcodeCategory::class);

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'unique:shortcode_categories,slug', 'regex:/^[a-z0-9\-]+$/'],
            'label' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        ShortcodeCategory::create($data);

        return redirect()->route('settings.shortcode-categories.index')
            ->with('success', 'Categoria creada correctamente.');
    }

    public function edit(ShortcodeCategory $shortcodeCategory): View
    {
        return view('template::shortcode-categories.edit', compact('shortcodeCategory'));
    }

    public function update(Request $request, ShortcodeCategory $shortcodeCategory): RedirectResponse
    {
        $this->authorize('update', $shortcodeCategory);

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/', 'unique:shortcode_categories,slug,'.$shortcodeCategory->id],
            'label' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $shortcodeCategory->update($data);

        return redirect()->route('settings.shortcode-categories.index')
            ->with('success', 'Categoria actualizada correctamente.');
    }

    public function destroy(Request $request, ShortcodeCategory $shortcodeCategory): JsonResponse
    {
        $this->authorize('delete', $shortcodeCategory);

        if (Shortcode::where('category', $shortcodeCategory->slug)->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la categoría porque existen shortcodes que la utilizan.',
            ], 422);
        }

        $shortcodeCategory->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(Request $request, ShortcodeCategory $shortcodeCategory): JsonResponse
    {
        $this->authorize('update', $shortcodeCategory);

        $shortcodeCategory->update(['is_active' => ! $shortcodeCategory->is_active]);

        return response()->json(['is_active' => $shortcodeCategory->is_active]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer', 'exists:shortcode_categories,id'],
        ]);

        $count = count($request->ids);

        if ($request->action === 'delete') {
            $usedSlugs = Shortcode::query()
                ->whereIn('category', ShortcodeCategory::whereIn('id', $request->ids)->pluck('slug'))
                ->pluck('category')
                ->unique()
                ->all();

            $deletable = ShortcodeCategory::whereIn('id', $request->ids)
                ->whereNotIn('slug', $usedSlugs)
                ->pluck('id');

            $skipped = $count - $deletable->count();
            ShortcodeCategory::whereIn('id', $deletable)->delete();

            return response()->json(['count' => $deletable->count(), 'skipped' => $skipped]);
        }

        match ($request->action) {
            'activate' => ShortcodeCategory::whereIn('id', $request->ids)->update(['is_active' => true]),
            'deactivate' => ShortcodeCategory::whereIn('id', $request->ids)->update(['is_active' => false]),
        };

        return response()->json(['count' => $count, 'skipped' => 0]);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $this->authorize('update', ShortcodeCategory::class);

        $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer', 'exists:shortcode_categories,id'],
        ]);

        $cases = collect($request->ids)
            ->map(fn ($id, $i) => 'WHEN '.(int) $id.' THEN '.(int) $i)
            ->join(' ');

        ShortcodeCategory::whereIn('id', $request->ids)
            ->update(['sort_order' => DB::raw("CASE id {$cases} END")]);

        return response()->json(['success' => true]);
    }
}
