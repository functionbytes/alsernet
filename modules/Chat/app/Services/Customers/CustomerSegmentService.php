<?php

namespace Modules\Chat\Services\Customers;

use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Customers\CustomerSegment;

class CustomerSegmentService
{
    public function __construct(
        protected CustomerFilterService $filterService
    ) {}

    /**
     * Update membership for a dynamic segment.
     */
    public function updateSegmentMembership(CustomerSegment $segment): void
    {
        if (! $segment->is_dynamic) {
            return;
        }

        if (empty($segment->filter_criteria)) {
            return;
        }

        // Get customers matching the filter criteria
        $query = $this->filterService->apply($segment->account_id, $segment->filter_criteria);
        $matchingCustomerIds = $query->pluck('id')->toArray();

        // Sync customers to segment
        $segment->customers()->sync($matchingCustomerIds);

        // Update customer count cache
        $segment->updateCustomerCount();

        Log::info("Updated segment membership: {$segment->name}", [
            'segment_id' => $segment->id,
            'customer_count' => count($matchingCustomerIds),
        ]);
    }

    /**
     * Update membership for all dynamic segments in an account.
     */
    public function updateAllDynamicSegments(int $accountId): void
    {
        $segments = CustomerSegment::forAccount($accountId)
            ->dynamic()
            ->get();

        foreach ($segments as $segment) {
            $this->updateSegmentMembership($segment);
        }

        Log::info("Updated all dynamic segments for account {$accountId}", [
            'segments_updated' => $segments->count(),
        ]);
    }

    /**
     * Add customers to a static segment.
     */
    public function addCustomersToSegment(CustomerSegment $segment, array $customerIds): void
    {
        // Attach customers without detaching existing ones
        $segment->customers()->syncWithoutDetaching($customerIds);
        $segment->updateCustomerCount();

        Log::info("Added customers to segment: {$segment->name}", [
            'segment_id' => $segment->id,
            'customers_added' => count($customerIds),
        ]);
    }

    /**
     * Remove customers from a segment.
     */
    public function removeCustomersFromSegment(CustomerSegment $segment, array $customerIds): void
    {
        $segment->customers()->detach($customerIds);
        $segment->updateCustomerCount();

        Log::info("Removed customers from segment: {$segment->name}", [
            'segment_id' => $segment->id,
            'customers_removed' => count($customerIds),
        ]);
    }
}
