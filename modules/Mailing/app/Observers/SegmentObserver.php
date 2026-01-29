<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\Segment;

/**
 * SegmentObserver
 *
 * Handles automatic tracking and lifecycle management for Segment models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class SegmentObserver
{
    /**
     * Handle the Segment "creating" event.
     */
    public function creating(Segment $segment): void
    {
        // Generate unique identifier if not set
        if (empty($segment->uid)) {
            $segment->uid = $this->generateUid();
        }

        // Set default subscriber count
        if (is_null($segment->subscriber_count)) {
            $segment->subscriber_count = 0;
        }

        // Set default metadata
        if (empty($segment->metadata)) {
            $segment->metadata = [];
        }
    }

    /**
     * Handle the Segment "created" event.
     */
    public function created(Segment $segment): void
    {
        // Queue job to calculate segment subscribers
        $this->calculateSubscriberCount($segment);

        // Log segment creation
        activity()
            ->performedOn($segment)
            ->withProperties([
                'segment_name' => $segment->name,
                'list_id' => $segment->mail_list_id,
            ])
            ->log('Segment created');
    }

    /**
     * Handle the Segment "updating" event.
     */
    public function updating(Segment $segment): void
    {
        // Recalculate if conditions changed
        if ($segment->isDirty('conditions')) {
            $this->calculateSubscriberCount($segment);
        }
    }

    /**
     * Handle the Segment "updated" event.
     */
    public function updated(Segment $segment): void
    {
        // Clear segment cache
        cache()->forget("segment.{$segment->id}");
        cache()->forget("segment.{$segment->id}.subscribers");
    }

    /**
     * Handle the Segment "deleted" event.
     */
    public function deleted(Segment $segment): void
    {
        cache()->forget("segment.{$segment->id}");
        cache()->forget("maillist.{$segment->mail_list_id}.segments");
    }

    /**
     * Generate a unique identifier for the segment
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (Segment::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Calculate subscriber count for segment
     */
    protected function calculateSubscriberCount(Segment $segment): void
    {
        dispatch(function () use ($segment) {
            if (method_exists($segment, 'updateSubscriberCount')) {
                $segment->updateSubscriberCount();
            }
        })->afterResponse();
    }
}
