<?php

namespace Modules\HelpdeskChat\Services\Contacts;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Contacts\ContactSegment;

class ContactSegmentService
{
    public function __construct(
        protected ContactFilterService $filterService
    ) {}

    /**
     * Update membership for a dynamic segment.
     */
    public function updateSegmentMembership(ContactSegment $segment): void
    {
        if (! $segment->is_dynamic) {
            return;
        }

        if (empty($segment->filter_criteria)) {
            return;
        }

        // Get contacts matching the filter criteria
        $query = $this->filterService->apply($segment->account_id, $segment->filter_criteria);
        $matchingContactIds = $query->pluck('id')->toArray();

        // Sync contacts to segment
        $segment->contacts()->sync($matchingContactIds);

        // Update contact count cache
        $segment->updateContactCount();

        Log::info("Updated segment membership: {$segment->name}", [
            'segment_id' => $segment->id,
            'contact_count' => count($matchingContactIds),
        ]);
    }

    /**
     * Update membership for all dynamic segments in an account.
     */
    public function updateAllDynamicSegments(int $accountId): void
    {
        $segments = ContactSegment::forAccount($accountId)
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
     * Add contacts to a static segment.
     */
    public function addContactsToSegment(ContactSegment $segment, array $contactIds): void
    {
        // Attach contacts without detaching existing ones
        $segment->contacts()->syncWithoutDetaching($contactIds);
        $segment->updateContactCount();

        Log::info("Added contacts to segment: {$segment->name}", [
            'segment_id' => $segment->id,
            'contacts_added' => count($contactIds),
        ]);
    }

    /**
     * Remove contacts from a segment.
     */
    public function removeContactsFromSegment(ContactSegment $segment, array $contactIds): void
    {
        $segment->contacts()->detach($contactIds);
        $segment->updateContactCount();

        Log::info("Removed contacts from segment: {$segment->name}", [
            'segment_id' => $segment->id,
            'contacts_removed' => count($contactIds),
        ]);
    }
}
