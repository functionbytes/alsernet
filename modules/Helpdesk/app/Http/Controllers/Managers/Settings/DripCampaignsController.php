<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreDripCampaignRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateDripCampaignRequest;
use Modules\Helpdesk\Jobs\ProcessDripStepJob;
use Modules\Helpdesk\Models\Campaigns\DripCampaign;
use Modules\Helpdesk\Models\Campaigns\DripExecution;
use Modules\Helpdesk\Models\Campaigns\DripStep;

class DripCampaignsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.drip-campaigns.view')->only(['index']);
        $this->middleware('can:helpdesk.drip-campaigns.manage')->only(['create', 'store', 'edit', 'update', 'destroy', 'toggle', 'enroll']);
    }

    public function index(Request $request): View
    {
        $query = DripCampaign::query()->withCount(['steps', 'executions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $campaigns = $query->latest()->paginate(15);

        $stats = [
            'total' => DripCampaign::count(),
            'active' => DripCampaign::where('is_active', true)->count(),
            'executions' => DripExecution::count(),
        ];

        return view('helpdesk::settings.drip-campaigns.index', compact('campaigns', 'stats'));
    }

    public function create(): View
    {
        $triggerTypes = DripCampaign::TRIGGER_TYPES;

        return view('helpdesk::settings.drip-campaigns.create', compact('triggerTypes'));
    }

    public function store(StoreDripCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::connection('helpdesk')->transaction(function () use ($validated, $request) {
            $campaign = DripCampaign::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'trigger_type' => $validated['trigger_type'],
                'trigger_value' => $validated['trigger_value'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            foreach ($request->input('steps', []) as $i => $stepData) {
                DripStep::create([
                    'campaign_id' => $campaign->id,
                    'step_order' => $i + 1,
                    'delay_minutes' => $stepData['delay_minutes'] ?? 0,
                    'channel' => $stepData['channel'],
                    'template_type' => $stepData['template_type'] ?? 'text',
                    'body' => $stepData['body'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('settings.helpdesk.drip-campaigns.index')
            ->with('success', 'Campaña drip creada exitosamente.');
    }

    public function edit(DripCampaign $dripCampaign): View
    {
        $dripCampaign->load('steps');
        $triggerTypes = DripCampaign::TRIGGER_TYPES;

        return view('helpdesk::settings.drip-campaigns.edit', compact('dripCampaign', 'triggerTypes'));
    }

    public function update(UpdateDripCampaignRequest $request, DripCampaign $dripCampaign): RedirectResponse
    {
        $validated = $request->validated();

        DB::connection('helpdesk')->transaction(function () use ($validated, $request, $dripCampaign) {
            $dripCampaign->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'trigger_type' => $validated['trigger_type'],
                'trigger_value' => $validated['trigger_value'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $dripCampaign->steps()->delete();

            foreach ($request->input('steps', []) as $i => $stepData) {
                DripStep::create([
                    'campaign_id' => $dripCampaign->id,
                    'step_order' => $i + 1,
                    'delay_minutes' => $stepData['delay_minutes'] ?? 0,
                    'channel' => $stepData['channel'],
                    'template_type' => $stepData['template_type'] ?? 'text',
                    'body' => $stepData['body'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('settings.helpdesk.drip-campaigns.index')
            ->with('success', 'Campaña drip actualizada exitosamente.');
    }

    public function destroy(DripCampaign $dripCampaign): RedirectResponse
    {
        DB::connection('helpdesk')->transaction(function () use ($dripCampaign) {
            $dripCampaign->steps()->delete();
            $dripCampaign->delete();
        });

        return redirect()
            ->route('settings.helpdesk.drip-campaigns.index')
            ->with('success', 'Campaña drip eliminada exitosamente.');
    }

    public function toggle(DripCampaign $dripCampaign): RedirectResponse
    {
        $dripCampaign->update(['is_active' => ! $dripCampaign->is_active]);

        $label = $dripCampaign->is_active ? 'activada' : 'desactivada';

        return redirect()
            ->route('settings.helpdesk.drip-campaigns.index')
            ->with('success', "Campaña drip {$label} exitosamente.");
    }

    public function enroll(Request $request, DripCampaign $dripCampaign): JsonResponse
    {
        $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('helpdesk.helpdesk_customers', 'id'),
            ],
        ]);

        $execution = DripExecution::updateOrCreate(
            ['campaign_id' => $dripCampaign->id, 'customer_id' => $request->integer('customer_id')],
            ['status' => 'active', 'current_step' => 0, 'started_at' => now()],
        );

        ProcessDripStepJob::dispatch($execution);

        return response()->json([
            'success' => true,
            'message' => 'Cliente enrolado en la campaña drip correctamente.',
            'data' => ['execution_id' => $execution->id],
        ]);
    }
}
