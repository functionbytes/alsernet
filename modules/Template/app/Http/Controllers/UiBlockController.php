<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Template\Models\UiBlock;

class UiBlockController extends Controller
{
    public function index(): View
    {
        $blocks = UiBlock::orderBy('sort_order')->get();

        return view('template::ui-blocks.index', compact('blocks'));
    }

    public function create(): View
    {
        return view('template::ui-blocks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'                => ['required', 'string', 'max:100', 'unique:ui_blocks,key', 'regex:/^[a-z0-9\-]+$/'],
            'name'               => ['required', 'string', 'max:150'],
            'description'        => ['nullable', 'string', 'max:255'],
            'icon'               => ['nullable', 'string', 'max:100'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
            'config_fields'      => ['nullable', 'json'],
        ]);

        $data['config_fields'] = $data['config_fields'] ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);

        UiBlock::create($data);

        return redirect()->route('settings.ui-blocks.index')
            ->with('success', 'Bloque creado correctamente.');
    }

    public function edit(UiBlock $uiBlock): View
    {
        return view('template::ui-blocks.edit', compact('uiBlock'));
    }

    public function update(Request $request, UiBlock $uiBlock): RedirectResponse
    {
        $data = $request->validate([
            'key'                => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:ui_blocks,key,'.$uiBlock->id],
            'name'               => ['required', 'string', 'max:150'],
            'description'        => ['nullable', 'string', 'max:255'],
            'icon'               => ['nullable', 'string', 'max:100'],
            'shortcode_template' => ['nullable', 'string', 'max:500'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
            'config_fields'      => ['nullable', 'json'],
        ]);

        $data['config_fields'] = $data['config_fields'] ? json_decode($data['config_fields'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);

        $uiBlock->update($data);

        return redirect()->route('settings.ui-blocks.index')
            ->with('success', 'Bloque actualizado correctamente.');
    }

    public function destroy(UiBlock $uiBlock): RedirectResponse
    {
        $uiBlock->delete();

        return redirect()->route('settings.ui-blocks.index')
            ->with('success', 'Bloque eliminado correctamente.');
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $position => $id) {
            UiBlock::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function apiIndex(): JsonResponse
    {
        $blocks = UiBlock::active()->get(['id', 'key', 'name', 'description', 'icon', 'config_fields', 'shortcode_template']);

        return response()->json($blocks);
    }
}
