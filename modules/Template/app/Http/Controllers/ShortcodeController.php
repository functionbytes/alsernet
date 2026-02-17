<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:shortcodes,key', 'regex:/^[a-z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'render_template' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'config_fields' => ['nullable', 'json'],
        ]);

        $data['config_fields'] = $data['config_fields'] ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);

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
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:shortcodes,key,'.$shortcode->id],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'render_template' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'config_fields' => ['nullable', 'json'],
        ]);

        $data['config_fields'] = $data['config_fields'] ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);

        $shortcode->update($data);

        return redirect()->route('settings.shortcodes.index')
            ->with('success', 'Shortcode actualizado correctamente.');
    }

    public function destroy(Shortcode $shortcode): RedirectResponse
    {
        $shortcode->delete();

        return redirect()->route('settings.shortcodes.index')
            ->with('success', 'Shortcode eliminado correctamente.');
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $position => $id) {
            Shortcode::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function apiIndex(): JsonResponse
    {
        $shortcodes = Shortcode::active()->get([
            'id', 'key', 'name', 'description', 'icon', 'config_fields', 'shortcode_template',
        ]);

        return response()->json($shortcodes);
    }
}
