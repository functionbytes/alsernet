<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Template\Facades\Theme;
use Modules\Template\Http\Requests\StoreShortcodeRequest;
use Modules\Template\Http\Requests\UpdateShortcodeRequest;
use Modules\Template\Models\Shortcode;

class ShortcodeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shortcode::class);

        $query = Shortcode::query()->orderBy('sort_order');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('is_active', $status === 'active');
        }

        $shortcodes = $query->get();

        $row = Shortcode::selectRaw("
            COUNT(*) as total,
            SUM(is_active = 1) as active,
            SUM(is_active = 0) as inactive,
            SUM(render_template IS NOT NULL AND render_template != '') as with_render
        ")->first();
        $stats = [
            'total' => (int) $row->total,
            'active' => (int) $row->active,
            'inactive' => (int) $row->inactive,
            'with_render' => (int) $row->with_render,
        ];

        return view('template::shortcodes.index', compact('shortcodes', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Shortcode::class);

        return view('template::shortcodes.create');
    }

    public function store(StoreShortcodeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['config_fields'] = ! empty($data['config_fields']) ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->has('is_active');

        Shortcode::create($data);

        return redirect()->route('settings.shortcodes.index')
            ->with('success', 'Shortcode creado correctamente.');
    }

    public function edit(Shortcode $shortcode): View
    {
        $activeTheme = setting('template', 'default');
        Theme::uses($activeTheme);
        $styleAssets = Theme::asset()->container('default')->getAssets()['style'] ?? [];
        $scriptAssets = Theme::asset()->container('footer')->getAssets()['script'] ?? [];
        $themeCssUrls = array_values(array_column($styleAssets, 'source'));
        $themeJsUrls = array_values(array_column($scriptAssets, 'source'));

        $locale = app()->getLocale();
        $langFile = base_path("platform/themes/{$activeTheme}/lang/{$locale}.json");
        $themeTranslations = file_exists($langFile)
            ? json_decode(file_get_contents($langFile), true) ?? []
            : [];

        return view('template::shortcodes.edit', compact('shortcode', 'themeCssUrls', 'themeJsUrls', 'themeTranslations'));
    }

    public function update(UpdateShortcodeRequest $request, Shortcode $shortcode): RedirectResponse
    {
        $data = $request->validated();
        $data['config_fields'] = ! empty($data['config_fields']) ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->has('is_active');

        $shortcode->update($data);

        return redirect()->route('settings.shortcodes.index')
            ->with('success', 'Shortcode actualizado correctamente.');
    }

    public function destroy(Request $request, Shortcode $shortcode): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $shortcode);

        $shortcode->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('settings.shortcodes.index')
            ->with('success', 'Shortcode eliminado correctamente.');
    }

    public function toggle(Shortcode $shortcode): JsonResponse
    {
        $this->authorize('update', $shortcode);

        $shortcode->update(['is_active' => ! $shortcode->is_active]);

        return response()->json(['is_active' => $shortcode->is_active]);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $this->authorize('update', Shortcode::class);

        $request->validate([
            'order' => ['required', 'array', 'max:500'],
            'order.*' => ['integer', 'exists:shortcodes,id'],
        ]);

        $cases = collect($request->order)
            ->map(fn ($id, $i) => 'WHEN '.(int) $id.' THEN '.(int) $i)
            ->join(' ');

        Shortcode::whereIn('id', $request->order)
            ->update(['sort_order' => DB::raw("CASE id {$cases} END")]);

        Shortcode::flushCache();

        return response()->json(['success' => true]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('update', Shortcode::class);

        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer', 'exists:shortcodes,id'],
        ]);

        $count = count($request->ids);

        match ($request->action) {
            'activate' => Shortcode::whereIn('id', $request->ids)->update(['is_active' => true]),
            'deactivate' => Shortcode::whereIn('id', $request->ids)->update(['is_active' => false]),
            'delete' => Shortcode::whereIn('id', $request->ids)->delete(),
        };

        Shortcode::flushCache();

        return response()->json(['count' => $count]);
    }

    public function apiIndex(): JsonResponse
    {
        $shortcodes = Shortcode::active()->get([
            'id', 'key', 'name', 'description', 'icon', 'config_fields', 'shortcode_template',
        ]);

        return response()->json($shortcodes);
    }

    /**
     * AJAX: shortcodes registrados en runtime por el compilador (ShortcodeServiceProvider).
     * Combina los shortcodes del compilador con los de la base de datos activos.
     */
    public function apiRuntimeIndex(): JsonResponse
    {
        $runtime = app('shortcode')->getRegistered();

        $dbKeys = Shortcode::active()->pluck('key')->toArray();

        $shortcodes = array_map(function (array $item) use ($dbKeys): array {
            return array_merge($item, ['source' => in_array($item['name'], $dbKeys, true) ? 'db' : 'runtime']);
        }, $runtime);

        return response()->json($shortcodes);
    }
}
