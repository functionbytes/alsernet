<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    ];

    public function show(Request $request, string $key): JsonResponse
    {
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

    private function assertKey(string $key): void
    {
        abort_unless(in_array($key, self::ALLOWED_KEYS, true), 422, 'Preference key not allowed.');
    }
}
