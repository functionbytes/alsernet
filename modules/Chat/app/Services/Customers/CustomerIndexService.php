<?php

namespace Modules\Chat\Services\Customers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;

/**
 * Builds the data needed by the customer index view:
 * stats summary, tab counts, filtered+paginated results.
 */
class CustomerIndexService
{
    /**
     * Return all data arrays needed by the customer index view.
     *
     * @return array{
     *     customers: LengthAwarePaginator,
     *     stats: array<string, int>,
     *     tabs: array<string, int>,
     * }
     */
    public function buildIndexData(int $accountId, Request $request): array
    {
        $base = Customer::forAccount($accountId);

        $totalCustomers = (clone $base)->count();
        $activeCustomers = (clone $base)->active()->count();
        $trashedCustomers = Customer::forAccount($accountId)->onlyTrashed()->count();

        $stats = [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'new_this_month' => (clone $base)->createdThisMonth()->count(),
            'total_conversations' => Conversation::forAccount($accountId)->count(),
        ];

        $tabs = [
            'all' => $totalCustomers,
            'active' => $activeCustomers,
            'trashed' => $trashedCustomers,
        ];

        $customers = $this->paginatedQuery($accountId, $request);

        return compact('customers', 'stats', 'tabs');
    }

    /**
     * Build a filtered, sorted, paginated customer query.
     */
    private function paginatedQuery(int $accountId, Request $request): LengthAwarePaginator
    {
        $query = Customer::forAccount($accountId);

        if ($request->input('tab') === 'trashed') {
            $query->onlyTrashed();
        } elseif ($request->input('tab') === 'active') {
            $query->active();
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $query->orderBy(
            $request->input('sort_by', 'created_at'),
            $request->input('sort_order', 'desc')
        );

        return $query->paginate(50);
    }
}
