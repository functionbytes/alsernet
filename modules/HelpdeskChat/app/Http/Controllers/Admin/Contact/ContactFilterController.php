<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Contact;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\ContactFilter;
use Modules\HelpdeskChat\Services\Contacts\ContactFilterService;

class ContactFilterController extends Controller
{
    public function __construct(protected ContactFilterService $filterService) {}

    /**
     * Get all saved filters for the current user.
     */
    public function index()
    {
        $filters = ContactFilter::forUser(
            auth()->id(),
            auth()->user()->account_id
        )->orderBy('name')->get();

        return response()->json($filters);
    }

    /**
     * Save a new filter.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'filters.*.value' => 'nullable',
            'is_public' => 'boolean',
        ]);

        $filter = ContactFilter::create([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'filters' => $validated['filters'],
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json([
            'message' => 'Filter saved successfully',
            'filter' => $filter,
        ]);
    }

    /**
     * Apply a saved filter and return filtered contacts.
     */
    public function apply(ContactFilter $filter)
    {
        $this->authorize('view', $filter);

        $query = $this->filterService->apply(
            auth()->user()->account_id,
            $filter->filters
        );

        $contacts = $query->with(['contactInboxes.inbox'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'contacts' => $contacts,
            'filter' => $filter,
        ]);
    }

    /**
     * Delete a saved filter.
     */
    public function destroy(ContactFilter $filter)
    {
        $this->authorize('delete', $filter);

        $filter->delete();

        return response()->json([
            'message' => 'Filter deleted successfully',
        ]);
    }

    /**
     * Get available fields and operators for building filters.
     */
    public function metadata()
    {
        $fields = $this->filterService->getAvailableFields();

        return response()->json([
            'fields' => $fields,
            'operators' => [
                'text' => $this->filterService->getOperatorsForType('text'),
                'number' => $this->filterService->getOperatorsForType('number'),
                'date' => $this->filterService->getOperatorsForType('date'),
                'list' => $this->filterService->getOperatorsForType('list'),
            ],
        ]);
    }
}
