<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\MailList;

/**
 * MailListObserver
 *
 * Handles automatic tracking and lifecycle management for MailList models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class MailListObserver
{
    /**
     * Handle the MailList "creating" event.
     */
    public function creating(MailList $mailList): void
    {
        // Generate unique identifier if not set
        if (empty($mailList->uid)) {
            $mailList->uid = $this->generateUid();
        }

        // Set default subscriber count
        if (is_null($mailList->subscriber_count)) {
            $mailList->subscriber_count = 0;
        }

        // Set default metadata
        if (empty($mailList->metadata)) {
            $mailList->metadata = [];
        }

        // Set default verification status
        if (empty($mailList->verification_status)) {
            $mailList->verification_status = 'unverified';
        }
    }

    /**
     * Handle the MailList "created" event.
     */
    public function created(MailList $mailList): void
    {
        // Create default fields for the list
        $this->createDefaultFields($mailList);

        // Log list creation
        activity()
            ->performedOn($mailList)
            ->withProperties([
                'list_name' => $mailList->name,
                'list_uid' => $mailList->uid,
            ])
            ->log('Mail list created');
    }

    /**
     * Handle the MailList "updating" event.
     */
    public function updating(MailList $mailList): void
    {
        // Track name changes
        if ($mailList->isDirty('name')) {
            activity()
                ->performedOn($mailList)
                ->withProperties([
                    'old_name' => $mailList->getOriginal('name'),
                    'new_name' => $mailList->name,
                ])
                ->log('Mail list renamed');
        }

        // Recalculate subscriber count if needed
        if ($mailList->isDirty('subscriber_count')) {
            // Update related segments
            $this->updateSegmentCounts($mailList);
        }
    }

    /**
     * Handle the MailList "updated" event.
     */
    public function updated(MailList $mailList): void
    {
        // Clear list cache
        $this->clearListCache($mailList);

        // Log significant updates
        if ($mailList->wasChanged(['name', 'description', 'from_name', 'from_email'])) {
            activity()
                ->performedOn($mailList)
                ->withProperties([
                    'changed_fields' => array_keys($mailList->getChanges()),
                ])
                ->log('Mail list updated');
        }
    }

    /**
     * Handle the MailList "deleting" event.
     */
    public function deleting(MailList $mailList): void
    {
        // Log list deletion
        activity()
            ->performedOn($mailList)
            ->withProperties([
                'list_name' => $mailList->name,
                'subscriber_count' => $mailList->subscriber_count,
            ])
            ->log('Mail list deleted');
    }

    /**
     * Handle the MailList "deleted" event.
     */
    public function deleted(MailList $mailList): void
    {
        // Clear all list-related cache
        $this->clearListCache($mailList);

        // Delete related data if not using cascade
        // Fields, segments, and subscribers should be handled by DB cascade
    }

    /**
     * Handle the MailList "forceDeleted" event.
     */
    public function forceDeleted(MailList $mailList): void
    {
        $this->clearListCache($mailList);
    }

    /**
     * Generate a unique identifier for the mail list
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (MailList::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Create default fields for a new mail list
     */
    protected function createDefaultFields(MailList $mailList): void
    {
        // Check if Field model exists
        if (! class_exists('Modules\Mailing\Models\Field')) {
            return;
        }

        $defaultFields = [
            [
                'label' => 'Email',
                'tag' => 'EMAIL',
                'type' => 'email',
                'required' => true,
                'visible' => true,
            ],
            [
                'label' => 'First Name',
                'tag' => 'FIRST_NAME',
                'type' => 'text',
                'required' => false,
                'visible' => true,
            ],
            [
                'label' => 'Last Name',
                'tag' => 'LAST_NAME',
                'type' => 'text',
                'required' => false,
                'visible' => true,
            ],
        ];

        foreach ($defaultFields as $field) {
            $mailList->fields()->create($field);
        }
    }

    /**
     * Update segment counts when list subscriber count changes
     */
    protected function updateSegmentCounts(MailList $mailList): void
    {
        // Check if Segment model exists
        if (! class_exists('Modules\Mailing\Models\Segment')) {
            return;
        }

        // Queue job to update segment counts
        if ($mailList->segments()->exists()) {
            dispatch(function () use ($mailList) {
                foreach ($mailList->segments as $segment) {
                    $segment->updateSubscriberCount();
                }
            })->afterResponse();
        }
    }

    /**
     * Clear mail list-related cache
     */
    protected function clearListCache(MailList $mailList): void
    {
        // Clear cache for this specific list
        cache()->forget("maillist.{$mailList->id}");
        cache()->forget("maillist.uid.{$mailList->uid}");

        // Clear list cache
        cache()->forget('maillists.list');
        cache()->forget('maillists.count');

        // Clear user-specific list cache if available
        if ($mailList->user_id) {
            cache()->forget("user.{$mailList->user_id}.maillists");
        }

        // Clear subscriber count cache
        cache()->forget("maillist.{$mailList->id}.subscribers.count");
    }
}
