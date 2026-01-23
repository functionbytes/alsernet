<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Contact;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Jobs\UpdateContactSegmentMembership;
use Modules\HelpdeskChat\Models\Contacts\ContactSegment;
use Modules\HelpdeskChat\Services\Contacts\ContactSegmentService;

class ContactSegmentController extends Controller
{
    public function __construct(
        protected ContactSegmentService $segmentService
    ) {}

    /**
     * Display a listing of segments.
     */
    public function index()
    {
        $segments = ContactSegment::forAccount(auth()->user()->account_id)
            ->with('user')
            ->orderBy('name')
            ->get();

        if (request()->wantsJson()) {
            return response()->json(['segments' => $segments]);
        }

        return view('helpdeskchat::admin.contacts.segments.index', compact('segments'));
    }

    /**
     * Show the form for creating a new segment.
     */
    public function create()
    {
        return view('helpdeskchat::admin.contacts.segments.create');
    }

    /**
     * Store a newly created segment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'filter_criteria' => 'nullable|array',
            'filter_criteria.*.field' => 'required_with:filter_criteria|string',
            'filter_criteria.*.operator' => 'required_with:filter_criteria|string',
            'filter_criteria.*.value' => 'nullable',
            'is_dynamic' => 'boolean',
        ]);

        $segment = ContactSegment::create([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'filter_criteria' => $validated['filter_criteria'] ?? null,
            'is_dynamic' => $validated['is_dynamic'] ?? true,
        ]);

        // Queue job to update membership for dynamic segments
        if ($segment->is_dynamic && $segment->filter_criteria) {
            UpdateContactSegmentMembership::dispatch(segmentId: $segment->id);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Segment created successfully',
                'segment' => $segment,
            ], 201);
        }

        return redirect()->route('admin.helpdesk.contacts.segments.index')
            ->with('success', 'Segment created successfully');
    }

    /**
     * Display the specified segment.
     */
    public function show(ContactSegment $segment)
    {
        $this->authorize('view', $segment);

        $segment->load(['contacts' => function ($query) {
            $query->orderBy('name')->paginate(20);
        }]);

        if (request()->wantsJson()) {
            return response()->json(['segment' => $segment]);
        }

        return view('helpdeskchat::admin.contacts.segments.show', compact('segment'));
    }

    /**
     * Show the form for editing the specified segment.
     */
    public function edit(ContactSegment $segment)
    {
        $this->authorize('update', $segment);

        return view('helpdeskchat::admin.contacts.segments.edit', compact('segment'));
    }

    /**
     * Update the specified segment.
     */
    public function update(Request $request, ContactSegment $segment)
    {
        $this->authorize('update', $segment);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'filter_criteria' => 'nullable|array',
            'filter_criteria.*.field' => 'required_with:filter_criteria|string',
            'filter_criteria.*.operator' => 'required_with:filter_criteria|string',
            'filter_criteria.*.value' => 'nullable',
            'is_dynamic' => 'boolean',
        ]);

        $segment->update($validated);

        // Queue job to update membership if dynamic segment criteria changed
        if ($segment->is_dynamic && $segment->wasChanged('filter_criteria')) {
            UpdateContactSegmentMembership::dispatch(segmentId: $segment->id);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Segment updated successfully',
                'segment' => $segment,
            ]);
        }

        return redirect()->route('admin.helpdesk.contacts.segments.index')
            ->with('success', 'Segment updated successfully');
    }

    /**
     * Remove the specified segment.
     */
    public function destroy(ContactSegment $segment)
    {
        $this->authorize('delete', $segment);

        $segment->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Segment deleted successfully']);
        }

        return redirect()->route('admin.helpdesk.contacts.segments.index')
            ->with('success', 'Segment deleted successfully');
    }

    /**
     * Manually trigger segment membership update.
     */
    public function refresh(ContactSegment $segment)
    {
        $this->authorize('update', $segment);

        if (! $segment->is_dynamic) {
            return response()->json([
                'error' => 'Only dynamic segments can be refreshed',
            ], 400);
        }

        UpdateContactSegmentMembership::dispatch(segmentId: $segment->id);

        return response()->json([
            'message' => 'Segment refresh queued',
        ]);
    }

    /**
     * Add contacts to a static segment.
     */
    public function addContacts(Request $request, ContactSegment $segment)
    {
        $this->authorize('update', $segment);

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $this->segmentService->addContactsToSegment($segment, $validated['contact_ids']);

        return response()->json([
            'message' => 'Contacts added to segment',
            'count' => count($validated['contact_ids']),
        ]);
    }

    /**
     * Remove contacts from a segment.
     */
    public function removeContacts(Request $request, ContactSegment $segment)
    {
        $this->authorize('update', $segment);

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $this->segmentService->removeContactsFromSegment($segment, $validated['contact_ids']);

        return response()->json([
            'message' => 'Contacts removed from segment',
            'count' => count($validated['contact_ids']),
        ]);
    }
}
