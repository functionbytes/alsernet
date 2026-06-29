<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Maillists;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSegment;
use Modules\Campaign\Models\CampaignSegmentCondition;

/**
 * Slim SegmentController. Anidado bajo lista:
 *   /panel/campaign/maillists/{listUid}/segments/...
 */
class SegmentController extends Controller
{
    public function index(string $listUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        return view('campaign::manager.segments.index', [
            'list' => $list,
            'segments' => $list->segments()->withCount('conditions')->get(),
        ]);
    }

    public function create(string $listUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        return view('campaign::manager.segments.create', compact('list'));
    }

    public function store(Request $request, string $listUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'matching' => ['nullable', 'in:and,or'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string'],
            'conditions.*.value' => ['nullable', 'string'],
        ]);

        $segment = CampaignSegment::create([
            'mail_list_id' => $list->id,
            'name' => $data['name'],
            'matching' => $data['matching'] ?? 'and',
        ]);

        foreach ($data['conditions'] ?? [] as $i => $cond) {
            CampaignSegmentCondition::create([
                'segment_id' => $segment->id,
                'field' => $cond['field'],
                'operator' => $cond['operator'],
                'value' => $cond['value'] ?? null,
                'order' => $i,
            ]);
        }

        return redirect()
            ->route('manager.campaigns.maillists.segments.index', $list->uid)
            ->with('success', 'Segmento creado.');
    }

    public function edit(string $listUid, string $segmentUid): View
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $segment = CampaignSegment::where('uid', $segmentUid)->firstOrFail();

        return view('campaign::manager.segments.edit', [
            'list' => $list,
            'segment' => $segment,
            'conditions' => $segment->conditions()->orderBy('order')->get(),
        ]);
    }

    public function update(Request $request, string $listUid, string $segmentUid): RedirectResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $segment = CampaignSegment::where('uid', $segmentUid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'matching' => ['nullable', 'in:and,or'],
            'conditions' => ['nullable', 'array'],
        ]);

        $segment->update($data);

        if (isset($data['conditions'])) {
            $segment->conditions()->delete();
            foreach ($data['conditions'] as $i => $cond) {
                CampaignSegmentCondition::create([
                    'segment_id' => $segment->id,
                    'field' => $cond['field'],
                    'operator' => $cond['operator'],
                    'value' => $cond['value'] ?? null,
                    'order' => $i,
                ]);
            }
        }

        return back()->with('success', 'Segmento actualizado.');
    }

    public function destroy(string $listUid, string $segmentUid): RedirectResponse
    {
        CampaignSegment::where('uid', $segmentUid)->firstOrFail()->delete();

        return redirect()
            ->route('manager.campaigns.maillists.segments.index', $listUid)
            ->with('success', 'Segmento eliminado.');
    }
}
