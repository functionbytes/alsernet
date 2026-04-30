<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\CustomerSegment\AddContactsToSegmentRequest;
use Modules\Chat\Http\Requests\CustomerSegment\RemoveContactsFromSegmentRequest;
use Modules\Chat\Http\Requests\CustomerSegment\StoreCustomerSegmentRequest;
use Modules\Chat\Http\Requests\CustomerSegment\UpdateCustomerSegmentRequest;
use Modules\Chat\Jobs\UpdateCustomerSegmentMembership;
use Modules\Chat\Models\Customers\CustomerSegment;
use Modules\Chat\Services\Customers\CustomerSegmentService;

class CustomerSegmentController extends Controller
{
    public function __construct(
        protected CustomerSegmentService $segmentService
    ) {}

    /**
     * Display a listing of segments.
     */
    public function index()
    {
        $segments = CustomerSegment::forAccount(auth()->user()->account_id)
            ->with('user')
            ->orderBy('name')
            ->get();

        if (request()->wantsJson()) {
            return response()->json(['segments' => $segments]);
        }

        return view('Chat::chats.customers.segments.index', compact('segments'));
    }

    /**
     * Show the form for creating a new segment.
     */
    public function create()
    {
        return view('Chat::chats.customers.segments.create');
    }

    /**
     * Store a newly created segment.
     */
    public function store(StoreCustomerSegmentRequest $request)
    {
        $validated = $request->validated();

        $segment = CustomerSegment::create([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'filter_criteria' => $validated['filter_criteria'] ?? null,
            'is_dynamic' => $validated['is_dynamic'] ?? true,
        ]);

        // Queue job to update membership for dynamic segments
        if ($segment->is_dynamic && $segment->filter_criteria) {
            UpdateCustomerSegmentMembership::dispatch(segmentId: $segment->id);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Segment created successfully',
                'segment' => $segment,
            ], 201);
        }

        return redirect()->route('chat.customers.segments.index')
            ->with('success', 'Segment created successfully');
    }

    /**
     * Display the specified segment.
     */
    public function show(CustomerSegment $segment)
    {
        $this->authorize('view', $segment);

        $customers = $segment->customers()->orderBy('name')->paginate(20);

        if (request()->wantsJson()) {
            return response()->json([
                'segment' => $segment,
                'customers' => $customers,
            ]);
        }

        return view('Chat::chats.customers.segments.show', compact('segment', 'customers'));
    }

    /**
     * Show the form for editing the specified segment.
     */
    public function edit(CustomerSegment $segment)
    {
        $this->authorize('update', $segment);

        return view('Chat::chats.customers.segments.edit', compact('segment'));
    }

    /**
     * Update the specified segment.
     */
    public function update(UpdateCustomerSegmentRequest $request, CustomerSegment $segment)
    {
        $this->authorize('update', $segment);

        $validated = $request->validated();

        $segment->update($validated);

        // Queue job to update membership if dynamic segment criteria changed
        if ($segment->is_dynamic && $segment->wasChanged('filter_criteria')) {
            UpdateCustomerSegmentMembership::dispatch(segmentId: $segment->id);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Segment updated successfully',
                'segment' => $segment,
            ]);
        }

        return redirect()->route('chat.customers.segments.index')
            ->with('success', 'Segment updated successfully');
    }

    /**
     * Remove the specified segment.
     */
    public function destroy(CustomerSegment $segment)
    {
        $this->authorize('delete', $segment);

        $segment->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Segment deleted successfully']);
        }

        return redirect()->route('chat.customers.segments.index')
            ->with('success', 'Segment deleted successfully');
    }

    /**
     * Restore a soft-deleted segment.
     */
    public function restore($id)
    {
        $segment = CustomerSegment::withTrashed()->findOrFail($id);
        $this->authorize('restore', $segment);

        $segment->restore();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Segment restored successfully']);
        }

        return redirect()->route('chat.customers.segments.index')
            ->with('success', 'Segment restored successfully');
    }

    /**
     * Permanently delete a segment.
     */
    public function forceDelete($id)
    {
        $segment = CustomerSegment::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $segment);

        $name = $segment->name;
        $segment->forceDelete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Segment permanently deleted']);
        }

        return redirect()->route('chat.customers.segments.index')
            ->with('success', "Segment '{$name}' permanently deleted");
    }

    /**
     * Manually trigger segment membership update.
     */
    public function refresh(CustomerSegment $segment)
    {
        $this->authorize('update', $segment);

        if (! $segment->is_dynamic) {
            return response()->json([
                'error' => 'Only dynamic segments can be refreshed',
            ], 400);
        }

        UpdateCustomerSegmentMembership::dispatch(segmentId: $segment->id);

        return response()->json([
            'message' => 'Segment refresh queued',
        ]);
    }

    /**
     * Add contacts to a static segment.
     */
    public function addContacts(AddContactsToSegmentRequest $request, CustomerSegment $segment)
    {
        $validated = $request->validated();

        $this->segmentService->addContactsToSegment($segment, $validated['customer_ids']);

        return response()->json([
            'message' => 'Contacts added to segment',
            'count' => count($validated['customer_ids']),
        ]);
    }

    /**
     * Remove contacts from a segment.
     */
    public function removeContacts(RemoveContactsFromSegmentRequest $request, CustomerSegment $segment)
    {
        $validated = $request->validated();

        $this->segmentService->removeContactsFromSegment($segment, $validated['customer_ids']);

        return response()->json([
            'message' => 'Contacts removed from segment',
            'count' => count($validated['customer_ids']),
        ]);
    }
}
