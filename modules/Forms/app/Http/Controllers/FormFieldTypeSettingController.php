<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forms\Models\FormFieldTypeSetting;

class FormFieldTypeSettingController extends Controller
{
    public function index(Request $request): View
    {
        $query = FormFieldTypeSetting::query()->ordered();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('label', 'like', '%'.$request->search.'%')
                    ->orWhere('type', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_enabled', $request->status === 'enabled');
        }

        $groups = $query->get()->groupBy('group_name');

        return view('forms::settings.field-types.index', compact('groups'));
    }

    public function edit(FormFieldTypeSetting $typeSetting): View
    {
        return view('forms::settings.field-types.edit', compact('typeSetting'));
    }

    public function updateFull(Request $request, FormFieldTypeSetting $typeSetting): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:100'],
            'group_name' => ['required', 'string', 'max:100'],
            'group_order' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_enabled' => ['boolean'],
            'default_css_class' => ['nullable', 'string', 'max:255'],
            'default_placeholder' => ['nullable', 'string', 'max:255'],
            'custom_css' => ['nullable', 'string'],
            'custom_html' => ['nullable', 'string'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');

        $typeSetting->update($data);

        return redirect()->route('settings.forms.field-types.edit', $typeSetting)
            ->with('success', 'Tipo de campo actualizado correctamente.');
    }

    public function update(Request $request, FormFieldTypeSetting $typeSetting): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:100'],
            'is_enabled' => ['boolean'],
            'default_css_class' => ['nullable', 'string', 'max:255'],
            'default_placeholder' => ['nullable', 'string', 'max:255'],
            'default_settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
            'group_name' => ['nullable', 'string', 'max:100'],
        ]);

        $typeSetting->update($data);

        return response()->json(['success' => true]);
    }

    public function toggle(FormFieldTypeSetting $typeSetting): JsonResponse
    {
        $typeSetting->update(['is_enabled' => ! $typeSetting->is_enabled]);

        return response()->json(['success' => true, 'is_enabled' => $typeSetting->is_enabled]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:enable,disable'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = FormFieldTypeSetting::whereIn('id', $validated['ids'])
            ->update(['is_enabled' => $validated['action'] === 'enable']);

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            FormFieldTypeSetting::query()
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
