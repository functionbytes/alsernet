<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\StoreSlaPolicyRequest;
use Modules\Helpdesk\Http\Requests\UpdateSlaPolicyRequest;
use Modules\Helpdesk\Models\SlaPolicy;

class SlaPoliciesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.sla-policies.view')->only(['index']);
        $this->middleware('can:helpdesk.sla-policies.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.sla-policies.update')->only(['edit', 'update', 'toggle']);
        $this->middleware('can:helpdesk.sla-policies.delete')->only(['destroy']);
    }

    public function index(): View
    {
        $policies = SlaPolicy::query()->latest()->paginate(15);

        $row = SlaPolicy::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ')->first();

        $stats = [
            'total' => (int) $row->total,
            'active' => (int) $row->active,
            'inactive' => (int) $row->inactive,
        ];

        return view('helpdesk::settings.sla-policies.index', compact('policies', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.sla-policies.create');
    }

    public function store(StoreSlaPolicyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['business_hours_only'] = $request->boolean('business_hours_only');
        $data['warning_threshold_percent'] = $data['warning_threshold_percent'] ?? 80;

        SlaPolicy::create($data);

        return redirect()
            ->route('settings.helpdesk.sla-policies.index')
            ->with('success', 'Politica SLA creada exitosamente.');
    }

    public function edit(SlaPolicy $slaPolicy): View
    {
        return view('helpdesk::settings.sla-policies.edit', compact('slaPolicy'));
    }

    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $slaPolicy): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['business_hours_only'] = $request->boolean('business_hours_only');

        $slaPolicy->update($data);

        return redirect()
            ->route('settings.helpdesk.sla-policies.index')
            ->with('success', 'Politica SLA actualizada exitosamente.');
    }

    public function destroy(SlaPolicy $slaPolicy): RedirectResponse
    {
        $slaPolicy->delete();

        return redirect()
            ->route('settings.helpdesk.sla-policies.index')
            ->with('success', 'Politica SLA eliminada exitosamente.');
    }

    public function toggle(SlaPolicy $slaPolicy): JsonResponse
    {
        $slaPolicy->update(['is_active' => ! $slaPolicy->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $slaPolicy->is_active,
        ]);
    }
}
