<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Traits\SoftDeletesActions;
use Modules\Helpdesk\Models\Inbox;

class SegmentController extends Controller
{
    use RecordsAudit;
    use SoftDeletesActions;

    public function __construct()
    {
        $this->middleware('can:engagement.segments.view')->only('page', 'index');
        $this->middleware('can:engagement.segments.create')->only('store');
        $this->middleware('can:engagement.segments.update')->only('update');
        $this->middleware('can:engagement.segments.delete')->only('destroy');
        $this->middleware('can:engagement.segments.view')->only('trashed');
        $this->middleware('can:engagement.segments.update')->only('restore');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.segments', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $rows = Segment::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->forInbox((int) $id))
            ->active()
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'conditions' => ['required', 'array'],
            'conditions.operator' => ['required', 'in:AND,OR'],
            'conditions.rules' => ['required', 'array', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $segment = Segment::query()->create($validated);
        $this->audit('created', 'segment', $segment->id, $validated);

        return response()->json(['success' => true, 'data' => $segment], 201);
    }

    public function update(Request $request, Segment $segment): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'conditions' => ['sometimes', 'array'],
            'conditions.operator' => ['required_with:conditions', 'in:AND,OR'],
            'conditions.rules' => ['required_with:conditions', 'array', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $segment->update($validated);
        $this->audit('updated', 'segment', $segment->id, $validated);

        return response()->json(['success' => true, 'data' => $segment->fresh()]);
    }

    public function destroy(Segment $segment): JsonResponse
    {
        $id = $segment->id;
        $segment->delete();
        $this->audit('deleted', 'segment', $id);

        return response()->json(['success' => true]);
    }

    protected function getModelClass(): string
    {
        return Segment::class;
    }
}
