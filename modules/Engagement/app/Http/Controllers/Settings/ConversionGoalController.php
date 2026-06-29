<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\ConversionGoal;
use Modules\Engagement\Services\ConversionMatcher;
use Modules\Helpdesk\Models\Inbox;

class ConversionGoalController extends Controller
{
    use RecordsAudit;

    public function __construct(
        private readonly ConversionMatcher $matcher,
    ) {
        $this->middleware('can:helpdesk.livechat.goals.view')->only('page', 'index', 'funnel');
        $this->middleware('can:helpdesk.livechat.goals.create')->only('store');
        $this->middleware('can:helpdesk.livechat.goals.update')->only('update');
        $this->middleware('can:helpdesk.livechat.goals.delete')->only('destroy');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.goals', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $rows = ConversionGoal::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->forInbox((int) $id))
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateGoal($request);
        $goal = ConversionGoal::query()->create($validated);
        $this->audit('created', 'conversion_goal', $goal->id, ['name' => $goal->name, 'type' => $goal->type]);

        return response()->json(['success' => true, 'data' => $goal], 201);
    }

    public function update(Request $request, ConversionGoal $conversionGoal): JsonResponse
    {
        $validated = $this->validateGoal($request, true);
        $conversionGoal->update($validated);
        $this->audit('updated', 'conversion_goal', $conversionGoal->id);

        return response()->json(['success' => true, 'data' => $conversionGoal->fresh()]);
    }

    public function destroy(ConversionGoal $conversionGoal): JsonResponse
    {
        $id = $conversionGoal->id;
        $conversionGoal->delete();
        $this->audit('deleted', 'conversion_goal', $id);

        return response()->json(['success' => true]);
    }

    public function funnel(ConversionGoal $conversionGoal, Request $request): JsonResponse
    {
        $days = max(1, min(365, (int) $request->input('days', 30)));
        $stats = $this->matcher->funnelStats($conversionGoal, $days);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    private function validateGoal(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'inbox_id' => [$required, 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => [$required, 'string', 'max:150'],
            'type' => [$required, 'in:event,url_visit,segment_change'],
            'event_name' => ['nullable', 'string', 'max:100'],
            'url_pattern' => ['nullable', 'string', 'max:255'],
            'target_segment' => ['nullable', 'in:cold,warm,hot'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'funnel_steps' => ['nullable', 'array'],
            'funnel_steps.*' => ['string', 'max:100'],
            'is_active' => ['boolean'],
        ]);
    }
}
