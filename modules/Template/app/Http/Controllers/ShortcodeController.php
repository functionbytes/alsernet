<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Template\Models\Shortcode;

class ShortcodeController extends Controller
{
    public function index(): View
    {
        $shortcodes = Shortcode::orderBy('sort_order')->get();

        return view('template::shortcodes.index', compact('shortcodes'));
    }

    public function create(): View
    {
        return view('template::shortcodes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Shortcode::class);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:shortcodes,key', 'regex:/^[a-z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'render_template' => ['nullable', 'string', 'max:10000'],
            'js_code' => ['nullable', 'string', 'max:50000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'config_fields' => ['nullable', 'json'],
        ]);

        $data['config_fields'] = $data['config_fields'] ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->has('is_active');

        Shortcode::create($data);

        return redirect()->route('settings.shortcodes.index')
            ->with('success', 'Shortcode creado correctamente.');
    }

    public function edit(Shortcode $shortcode): View
    {
        return view('template::shortcodes.edit', compact('shortcode'));
    }

    public function update(Request $request, Shortcode $shortcode): RedirectResponse
    {
        $this->authorize('update', $shortcode);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:shortcodes,key,'.$shortcode->id],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'render_template' => ['nullable', 'string', 'max:10000'],
            'js_code' => ['nullable', 'string', 'max:50000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'config_fields' => ['nullable', 'json'],
        ]);

        $data['config_fields'] = $data['config_fields'] ? json_decode($data['config_fields'], true) : [];
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

        return response()->json(['success' => true]);
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
