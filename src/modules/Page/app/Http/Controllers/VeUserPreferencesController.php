<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Modules\Page\Models\VeUserPreference;

/**
 * Preferencias persistentes del Visual Editor por usuario.
 * Reemplaza/complementa el almacenamiento en localStorage para
 * shortcode favorites, panel states y opciones del editor.
 */
class VeUserPreferencesController extends Controller
{
    private const ALLOWED_KEYS = [
        'shortcode_favorites',
        'panel_states',
        'inspector_collapsed',
        'last_panel',
        'last_breakpoint',
        // Editor view state (view R).
        'wireframe_enabled',
        'ruler_enabled',
        'split_view_enabled',
        'zoom_mode',   // fit | manual
        'zoom_level',  // "1", "1.25", etc. stored as string
    ];

    public function show(Request $request, string $key): JsonResponse
    {
        $this->authorize('viewAny', Page::class);
        $this->assertKey($key);

        $pref = VeUserPreference::query()
            ->where('user_id', $request->user()->id)
            ->where('key', $key)
            ->first();

        return response()->json([
            'success' => true,
            'key' => $key,
            'value' => $pref?->value ?? [],
        ]);
    }

    public function store(Request $request, string $key): JsonResponse
    {
        $this->authorize('update', Page::class);
        $this->assertKey($key);

        $data = $request->validate([
            'value' => ['required', 'array'],
            'value.*' => ['nullable', 'string', 'max:200'],
        ]);

        VeUserPreference::updateOrCreate(
            ['user_id' => $request->user()->id, 'key' => $key],
            ['value' => array_values(array_unique(array_filter($data['value'])))],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Read several preferences in a single request.
     * Body: { keys: ["last_panel", "last_breakpoint", ...] }.
     */
    public function bulkShow(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Page::class);
        $data = $request->validate([
            'keys' => ['required', 'array', 'max:20'],
            'keys.*' => ['string'],
        ]);

        $requested = array_values(array_intersect($data['keys'], self::ALLOWED_KEYS));
        $stored = VeUserPreference::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('key', $requested)
            ->pluck('value', 'key');

        $out = [];
        foreach ($requested as $k) {
            $out[$k] = $stored[$k] ?? [];
        }

        return response()->json(['success' => true, 'data' => $out]);
    }

    /**
     * Write several preferences in a single request.
     * Body: { values: { "last_panel": ["inspector"], "last_breakpoint": ["tablet"] } }.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $this->authorize('update', Page::class);
        $data = $request->validate([
            'values' => ['required', 'array', 'max:20'],
            'values.*' => ['required', 'array'],
            'values.*.*' => ['nullable', 'string', 'max:200'],
        ]);

        foreach ($data['values'] as $key => $raw) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }
            VeUserPreference::updateOrCreate(
                ['user_id' => $request->user()->id, 'key' => $key],
                ['value' => array_values(array_unique(array_filter($raw)))],
            );
        }

        return response()->json(['success' => true]);
    }

    private function assertKey(string $key): void
    {
        abort_unless(in_array($key, self::ALLOWED_KEYS, true), 422, 'Preference key not allowed.');
    }
}
