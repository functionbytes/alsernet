<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\SendingDomain;

/**
 * SendingDomainObserver
 *
 * Handles automatic tracking and lifecycle management for SendingDomain models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class SendingDomainObserver
{
    /**
     * Handle the SendingDomain "creating" event.
     */
    public function creating(SendingDomain $domain): void
    {
        // Generate unique identifier if not set
        if (empty($domain->uid)) {
            $domain->uid = $this->generateUid();
        }

        // Set default verification status
        if (empty($domain->verification_status)) {
            $domain->verification_status = 'unverified';
        }

        // Set default metadata
        if (empty($domain->metadata)) {
            $domain->metadata = [];
        }
    }

    /**
     * Handle the SendingDomain "created" event.
     */
    public function created(SendingDomain $domain): void
    {
        // Queue domain verification
        $this->queueDomainVerification($domain);

        // Log domain creation
        activity()
            ->performedOn($domain)
            ->withProperties([
                'domain_name' => $domain->name,
            ])
            ->log('Sending domain created');
    }

    /**
     * Handle the SendingDomain "updating" event.
     */
    public function updating(SendingDomain $domain): void
    {
        // Re-verify if domain name changed
        if ($domain->isDirty('name')) {
            $domain->verification_status = 'unverified';
            $this->queueDomainVerification($domain);
        }
    }

    /**
     * Handle the SendingDomain "updated" event.
     */
    public function updated(SendingDomain $domain): void
    {
        cache()->forget("sendingdomain.{$domain->id}");
        cache()->forget('sendingdomains.list');
    }

    /**
     * Handle the SendingDomain "deleted" event.
     */
    public function deleted(SendingDomain $domain): void
    {
        cache()->forget("sendingdomain.{$domain->id}");
        cache()->forget('sendingdomains.list');
    }

    /**
     * Generate a unique identifier
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (SendingDomain::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Queue domain verification job
     */
    protected function queueDomainVerification(SendingDomain $domain): void
    {
        if (class_exists('Modules\Mailing\Jobs\VerifySendingDomain')) {
            dispatch(new \Modules\Mailing\Jobs\VerifySendingDomain($domain));
        }
    }
}
