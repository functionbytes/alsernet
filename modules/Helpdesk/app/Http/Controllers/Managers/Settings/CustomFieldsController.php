<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreCustomFieldRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateCustomFieldRequest;
use Modules\Helpdesk\Models\CustomField;

class CustomFieldsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.custom-fields.view')->only(['index']);
        $this->middleware('can:helpdesk.custom-fields.manage')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request): View
    {
        $query = CustomField::query()->ordered();

        if ($request->filled('entity_type')) {
            $query->forEntity($request->entity_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        $fields = $query->paginate(20);

        $statsRow = CustomField::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN entity_type = ? THEN 1 ELSE 0 END) as customers,
            SUM(CASE WHEN entity_type = ? THEN 1 ELSE 0 END) as conversations
        ', [CustomField::ENTITY_CUSTOMER, CustomField::ENTITY_CONVERSATION])->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'customers' => (int) $statsRow->customers,
            'conversations' => (int) $statsRow->conversations,
        ];

        return view('helpdesk::settings.custom-fields.index', compact('fields', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.custom-fields.create', [
            'field' => null,
            'types' => CustomField::TYPES,
        ]);
    }

    public function store(StoreCustomFieldRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['key'] = Str::slug($request->label, '_');
        $data['is_required'] = $request->boolean('is_required');
        $data['options'] = $request->options
            ? array_values(array_filter(explode("\n", trim($request->options))))
            : null;

        CustomField::create($data);

        return redirect()
            ->route('settings.helpdesk.custom-fields.index')
            ->with('success', 'Campo personalizado creado exitosamente.');
    }

    public function edit(CustomField $customField): View
    {
        return view('helpdesk::settings.custom-fields.edit', [
            'field' => $customField,
            'types' => CustomField::TYPES,
        ]);
    }

    public function update(UpdateCustomFieldRequest $request, CustomField $customField): RedirectResponse
    {
        $data = $request->validated();
        $data['is_required'] = $request->boolean('is_required');
        $data['options'] = $request->options
            ? array_values(array_filter(explode("\n", trim($request->options))))
            : null;

        $customField->update($data);

        return redirect()
            ->route('settings.helpdesk.custom-fields.index')
            ->with('success', 'Campo personalizado actualizado exitosamente.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();

        return redirect()
            ->route('settings.helpdesk.custom-fields.index')
            ->with('success', 'Campo personalizado eliminado exitosamente.');
    }
}
