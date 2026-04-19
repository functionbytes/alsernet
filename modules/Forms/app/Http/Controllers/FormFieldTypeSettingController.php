<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\BulkActionFieldTypeRequest;
use Modules\Forms\Http\Requests\ReorderFieldTypeRequest;
use Modules\Forms\Http\Requests\UpdateFieldTypeFullRequest;
use Modules\Forms\Http\Requests\UpdateFieldTypeRequest;
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

    public function updateFull(UpdateFieldTypeFullRequest $request, FormFieldTypeSetting $typeSetting): RedirectResponse
    {
        $data = $request->validated();
        $data['is_enabled'] = $request->boolean('is_enabled');

        $typeSetting->update($data);

        return redirect()->route('settings.forms.field-types.edit', $typeSetting)
            ->with('success', 'Tipo de campo actualizado correctamente.');
    }

    public function update(UpdateFieldTypeRequest $request, FormFieldTypeSetting $typeSetting): JsonResponse
    {
        $typeSetting->update($request->validated());

        return response()->json(['success' => true]);
    }

    public function toggle(FormFieldTypeSetting $typeSetting): JsonResponse
    {
        $typeSetting->update(['is_enabled' => ! $typeSetting->is_enabled]);

        return response()->json(['success' => true, 'is_enabled' => $typeSetting->is_enabled]);
    }

    public function bulkAction(BulkActionFieldTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = FormFieldTypeSetting::query()->whereIn('id', $validated['ids'])
            ->update(['is_enabled' => $validated['action'] === 'enable']);

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function reorder(ReorderFieldTypeRequest $request): JsonResponse
    {
        foreach ($request->validated()['items'] as $item) {
            FormFieldTypeSetting::query()
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
