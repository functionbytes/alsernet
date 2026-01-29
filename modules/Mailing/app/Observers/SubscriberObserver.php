<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\Subscriber;

/**
 * SubscriberObserver
 *
 * Handles automatic tracking and lifecycle management for Subscriber models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class SubscriberObserver
{
    /**
     * Handle the Subscriber "creating" event.
     */
    public function creating(Subscriber $subscriber): void
    {
        // Generate unique identifier if not set
        if (empty($subscriber->uid)) {
            $subscriber->uid = $this->generateUid();
        }

        // Set default status if not set
        if (empty($subscriber->status)) {
            $subscriber->status = 'subscribed';
        }

        // Set subscription date if not set
        if (empty($subscriber->subscribed_at)) {
            $subscriber->subscribed_at = now();
        }

        // Normalize email
        if (! empty($subscriber->email)) {
            $subscriber->email = strtolower(trim($subscriber->email));
        }

        // Set default metadata
        if (empty($subscriber->metadata)) {
            $subscriber->metadata = [];
        }

        // Set IP and user agent if available
        if (empty($subscriber->ip_address) && request()->ip()) {
            $subscriber->ip_address = request()->ip();
        }

        if (empty($subscriber->user_agent) && request()->userAgent()) {
            $subscriber->user_agent = request()->userAgent();
        }
    }

    /**
     * Handle the Subscriber "created" event.
     */
    public function created(Subscriber $subscriber): void
    {
        // Update mail list subscriber count
        $this->updateMailListCount($subscriber);

        // Update segment counts if applicable
        $this->updateSegmentCounts($subscriber);

        // Log subscriber creation
        activity()
            ->performedOn($subscriber)
            ->withProperties([
                'email' => $subscriber->email,
                'list_id' => $subscriber->mail_list_id,
                'status' => $subscriber->status,
            ])
            ->log('Subscriber added');

        // Dispatch welcome email if configured
        $this->dispatchWelcomeEmail($subscriber);
    }

    /**
     * Handle the Subscriber "updating" event.
     */
    public function updating(Subscriber $subscriber): void
    {
        // Track status changes
        if ($subscriber->isDirty('status')) {
            $oldStatus = $subscriber->getOriginal('status');
            $newStatus = $subscriber->status;

            // Update timestamps based on status change
            if ($newStatus === 'unsubscribed' && empty($subscriber->unsubscribed_at)) {
                $subscriber->unsubscribed_at = now();
            }

            if ($newStatus === 'subscribed') {
                $subscriber->unsubscribed_at = null;
                if (empty($subscriber->subscribed_at)) {
                    $subscriber->subscribed_at = now();
                }
            }

            // Log status change
            activity()
                ->performedOn($subscriber)
                ->withProperties([
                    'email' => $subscriber->email,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ])
                ->log('Subscriber status changed');
        }

        // Normalize email if changed
        if ($subscriber->isDirty('email')) {
            $subscriber->email = strtolower(trim($subscriber->email));
        }
    }

    /**
     * Handle the Subscriber "updated" event.
     */
    public function updated(Subscriber $subscriber): void
    {
        // Clear subscriber cache
        $this->clearSubscriberCache($subscriber);

        // Update mail list count if status changed
        if ($subscriber->wasChanged('status')) {
            $this->updateMailListCount($subscriber);
            $this->updateSegmentCounts($subscriber);
        }

        // Log significant updates
        if ($subscriber->wasChanged(['email', 'first_name', 'last_name', 'status'])) {
            activity()
                ->performedOn($subscriber)
                ->withProperties([
                    'email' => $subscriber->email,
                    'changed_fields' => array_keys($subscriber->getChanges()),
                ])
                ->log('Subscriber updated');
        }
    }

    /**
     * Handle the Subscriber "deleting" event.
     */
    public function deleting(Subscriber $subscriber): void
    {
        // Log subscriber deletion
        activity()
            ->performedOn($subscriber)
            ->withProperties([
                'email' => $subscriber->email,
                'list_id' => $subscriber->mail_list_id,
                'status' => $subscriber->status,
            ])
            ->log('Subscriber deleted');
    }

    /**
     * Handle the Subscriber "deleted" event.
     */
    public function deleted(Subscriber $subscriber): void
    {
        // Clear all subscriber-related cache
        $this->clearSubscriberCache($subscriber);

        // Update mail list count
        $this->updateMailListCount($subscriber, true);

        // Update segment counts
        $this->updateSegmentCounts($subscriber, true);

        // Delete related data if not using cascade
        // Subscriber fields should be handled by DB cascade
    }

    /**
     * Handle the Subscriber "forceDeleted" event.
     */
    public function forceDeleted(Subscriber $subscriber): void
    {
        $this->clearSubscriberCache($subscriber);
    }

    /**
     * Generate a unique identifier for the subscriber
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (Subscriber::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Update mail list subscriber count
     */
    protected function updateMailListCount(Subscriber $subscriber, bool $decrement = false): void
    {
        if (! $subscriber->mail_list_id) {
            return;
        }

        // Check if MailList model exists
        if (! class_exists('Modules\Mailing\Models\MailList')) {
            return;
        }

        // Update count asynchronously
        dispatch(function () use ($subscriber, $decrement) {
            $mailList = $subscriber->mailList;
            if ($mailList) {
                if ($decrement) {
                    $mailList->decrement('subscriber_count');
                } else {
                    // Recalculate actual count
                    $count = $mailList->subscribers()->where('status', 'subscribed')->count();
                    $mailList->update(['subscriber_count' => $count]);
                }
            }
        })->afterResponse();
    }

    /**
     * Update segment subscriber counts
     */
    protected function updateSegmentCounts(Subscriber $subscriber, bool $deleted = false): void
    {
        if (! $subscriber->mail_list_id) {
            return;
        }

        // Check if Segment model exists
        if (! class_exists('Modules\Mailing\Models\Segment')) {
            return;
        }

        // Queue job to update segment counts
        dispatch(function () use ($subscriber) {
            $mailList = $subscriber->mailList;
            if ($mailList && $mailList->segments()->exists()) {
                foreach ($mailList->segments as $segment) {
                    $segment->updateSubscriberCount();
                }
            }
        })->afterResponse();
    }

    /**
     * Dispatch welcome email if configured
     */
    protected function dispatchWelcomeEmail(Subscriber $subscriber): void
    {
        // Only send welcome email for new subscribed subscribers
        if ($subscriber->status !== 'subscribed') {
            return;
        }

        // Check if mail list has welcome email enabled
        $mailList = $subscriber->mailList;
        if (! $mailList || ! $mailList->send_welcome_email) {
            return;
        }

        // Dispatch welcome email job
        if (class_exists('Modules\Mailing\Jobs\SendWelcomeEmail')) {
            dispatch(new \Modules\Mailing\Jobs\SendWelcomeEmail($subscriber));
        }
    }

    /**
     * Clear subscriber-related cache
     */
    protected function clearSubscriberCache(Subscriber $subscriber): void
    {
        // Clear cache for this specific subscriber
        cache()->forget("subscriber.{$subscriber->id}");
        cache()->forget("subscriber.uid.{$subscriber->uid}");
        cache()->forget("subscriber.email.{$subscriber->email}");

        // Clear list-specific cache
        if ($subscriber->mail_list_id) {
            cache()->forget("maillist.{$subscriber->mail_list_id}.subscribers");
            cache()->forget("maillist.{$subscriber->mail_list_id}.subscribers.count");
        }

        // Clear segment cache
        cache()->tags(['subscribers', "maillist.{$subscriber->mail_list_id}"])->flush();
    }
}
