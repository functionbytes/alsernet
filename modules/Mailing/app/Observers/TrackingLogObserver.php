<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\TrackingLog;

/**
 * TrackingLogObserver
 *
 * Handles automatic tracking and statistics management for TrackingLog models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class TrackingLogObserver
{
    /**
     * Handle the TrackingLog "creating" event.
     */
    public function creating(TrackingLog $log): void
    {
        // Generate unique identifier if not set
        if (empty($log->uid)) {
            $log->uid = $this->generateUid();
        }

        // Set timestamp if not set
        if (empty($log->created_at)) {
            $log->created_at = now();
        }

        // Store user agent and IP if available
        if (empty($log->user_agent) && request()->userAgent()) {
            $log->user_agent = request()->userAgent();
        }

        if (empty($log->ip_address) && request()->ip()) {
            $log->ip_address = request()->ip();
        }

        // Parse user agent for device/browser info
        if (! empty($log->user_agent) && empty($log->device_type)) {
            $this->parseUserAgent($log);
        }

        // Get geolocation data if IP is available
        if (! empty($log->ip_address) && empty($log->country)) {
            $this->getGeolocation($log);
        }
    }

    /**
     * Handle the TrackingLog "created" event.
     */
    public function created(TrackingLog $log): void
    {
        // Update campaign analytics
        $this->updateCampaignAnalytics($log);

        // Update subscriber statistics
        $this->updateSubscriberStats($log);

        // Clear related cache
        $this->clearTrackingCache($log);

        // Log tracking event
        if ($log->status === 'delivered') {
            activity()
                ->performedOn($log)
                ->withProperties([
                    'campaign_id' => $log->campaign_id,
                    'subscriber_id' => $log->subscriber_id,
                    'status' => $log->status,
                ])
                ->log('Email delivered');
        } elseif ($log->status === 'bounced') {
            activity()
                ->performedOn($log)
                ->withProperties([
                    'campaign_id' => $log->campaign_id,
                    'subscriber_id' => $log->subscriber_id,
                    'bounce_type' => $log->bounce_type,
                    'error' => $log->error_message,
                ])
                ->log('Email bounced');
        }
    }

    /**
     * Handle the TrackingLog "updating" event.
     */
    public function updating(TrackingLog $log): void
    {
        // Track status changes
        if ($log->isDirty('status')) {
            $oldStatus = $log->getOriginal('status');
            $newStatus = $log->status;

            // Update analytics when status changes
            $this->updateCampaignAnalytics($log, $oldStatus);

            activity()
                ->performedOn($log)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ])
                ->log('Tracking log status changed');
        }

        // Update opened_at if status changed to opened
        if ($log->status === 'opened' && empty($log->opened_at)) {
            $log->opened_at = now();
        }

        // Update clicked_at if status changed to clicked
        if ($log->status === 'clicked' && empty($log->clicked_at)) {
            $log->clicked_at = now();
        }

        // Update bounced_at if status changed to bounced
        if ($log->status === 'bounced' && empty($log->bounced_at)) {
            $log->bounced_at = now();
        }
    }

    /**
     * Handle the TrackingLog "updated" event.
     */
    public function updated(TrackingLog $log): void
    {
        // Clear tracking cache
        $this->clearTrackingCache($log);

        // Update campaign analytics if status changed
        if ($log->wasChanged('status')) {
            $this->updateCampaignAnalytics($log);
        }
    }

    /**
     * Handle the TrackingLog "deleted" event.
     */
    public function deleted(TrackingLog $log): void
    {
        // Clear tracking cache
        $this->clearTrackingCache($log);

        // Update campaign analytics (decrement counts)
        $this->updateCampaignAnalytics($log, null, true);
    }

    /**
     * Generate a unique identifier for the tracking log
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (TrackingLog::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Parse user agent to extract device and browser information
     */
    protected function parseUserAgent(TrackingLog $log): void
    {
        try {
            // Simple user agent parsing
            $userAgent = $log->user_agent;

            // Detect device type
            if (preg_match('/mobile|android|iphone|ipad|tablet/i', $userAgent)) {
                $log->device_type = 'mobile';
            } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
                $log->device_type = 'tablet';
            } else {
                $log->device_type = 'desktop';
            }

            // Detect browser
            if (preg_match('/firefox/i', $userAgent)) {
                $log->browser = 'Firefox';
            } elseif (preg_match('/chrome/i', $userAgent)) {
                $log->browser = 'Chrome';
            } elseif (preg_match('/safari/i', $userAgent)) {
                $log->browser = 'Safari';
            } elseif (preg_match('/msie|trident/i', $userAgent)) {
                $log->browser = 'Internet Explorer';
            } elseif (preg_match('/edge/i', $userAgent)) {
                $log->browser = 'Edge';
            } else {
                $log->browser = 'Unknown';
            }

            // Detect OS
            if (preg_match('/windows/i', $userAgent)) {
                $log->operating_system = 'Windows';
            } elseif (preg_match('/mac|darwin/i', $userAgent)) {
                $log->operating_system = 'Mac OS';
            } elseif (preg_match('/linux/i', $userAgent)) {
                $log->operating_system = 'Linux';
            } elseif (preg_match('/android/i', $userAgent)) {
                $log->operating_system = 'Android';
            } elseif (preg_match('/iphone|ipad|ios/i', $userAgent)) {
                $log->operating_system = 'iOS';
            } else {
                $log->operating_system = 'Unknown';
            }
        } catch (\Exception $e) {
            logger()->error('Failed to parse user agent', [
                'tracking_log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get geolocation data from IP address
     */
    protected function getGeolocation(TrackingLog $log): void
    {
        // Queue geolocation lookup asynchronously
        dispatch(function () use ($log) {
            try {
                // Use GeoIP service if available
                if (class_exists('Modules\Mailing\Services\GeoIpService')) {
                    $service = app('Modules\Mailing\Services\GeoIpService');
                    $location = $service->lookup($log->ip_address);

                    if ($location) {
                        $log->update([
                            'country' => $location['country'] ?? null,
                            'region' => $location['region'] ?? null,
                            'city' => $location['city'] ?? null,
                            'latitude' => $location['latitude'] ?? null,
                            'longitude' => $location['longitude'] ?? null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                logger()->error('Failed to get geolocation', [
                    'tracking_log_id' => $log->id,
                    'ip_address' => $log->ip_address,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    /**
     * Update campaign analytics based on tracking log
     */
    protected function updateCampaignAnalytics(TrackingLog $log, ?string $oldStatus = null, bool $decrement = false): void
    {
        if (! $log->campaign_id) {
            return;
        }

        // Check if Campaign model exists
        if (! class_exists('Modules\Mailing\Models\Campaign')) {
            return;
        }

        // Queue analytics update asynchronously
        dispatch(function () use ($log, $oldStatus, $decrement) {
            $campaign = $log->campaign;
            if (! $campaign || ! $campaign->analytics) {
                return;
            }

            $analytics = $campaign->analytics;
            $increment = 1;
            $amount = $decrement ? -$increment : $increment;

            // Update counts based on status
            switch ($log->status) {
                case 'delivered':
                    $analytics->increment('delivered_count', $amount);
                    break;
                case 'opened':
                    $analytics->increment('opened_count', $amount);
                    // Also count as delivered if not already
                    if ($oldStatus !== 'delivered') {
                        $analytics->increment('delivered_count', $amount);
                    }
                    break;
                case 'clicked':
                    $analytics->increment('clicked_count', $amount);
                    // Also count as opened and delivered if not already
                    if (! in_array($oldStatus, ['opened', 'clicked'])) {
                        $analytics->increment('opened_count', $amount);
                    }
                    if (! in_array($oldStatus, ['delivered', 'opened', 'clicked'])) {
                        $analytics->increment('delivered_count', $amount);
                    }
                    break;
                case 'bounced':
                    $analytics->increment('bounced_count', $amount);
                    if ($log->bounce_type === 'hard') {
                        $analytics->increment('hard_bounced_count', $amount);
                    } else {
                        $analytics->increment('soft_bounced_count', $amount);
                    }
                    break;
                case 'failed':
                    $analytics->increment('failed_count', $amount);
                    break;
                case 'unsubscribed':
                    $analytics->increment('unsubscribed_count', $amount);
                    break;
                case 'complained':
                    $analytics->increment('complained_count', $amount);
                    break;
            }

            // Clear campaign cache
            cache()->forget("campaign.{$campaign->id}.analytics");
        })->afterResponse();
    }

    /**
     * Update subscriber statistics
     */
    protected function updateSubscriberStats(TrackingLog $log): void
    {
        if (! $log->subscriber_id) {
            return;
        }

        // Check if Subscriber model exists
        if (! class_exists('Modules\Mailing\Models\Subscriber')) {
            return;
        }

        // Update subscriber's last activity
        dispatch(function () use ($log) {
            $subscriber = $log->subscriber;
            if ($subscriber) {
                $subscriber->update([
                    'last_activity_at' => now(),
                ]);

                // Update subscriber engagement score
                if (method_exists($subscriber, 'updateEngagementScore')) {
                    $subscriber->updateEngagementScore();
                }
            }
        })->afterResponse();
    }

    /**
     * Clear tracking-related cache
     */
    protected function clearTrackingCache(TrackingLog $log): void
    {
        // Clear cache for this specific log
        cache()->forget("trackinglog.{$log->id}");
        cache()->forget("trackinglog.uid.{$log->uid}");

        // Clear campaign-specific cache
        if ($log->campaign_id) {
            cache()->forget("campaign.{$log->campaign_id}.tracking");
            cache()->forget("campaign.{$log->campaign_id}.analytics");
            cache()->forget("campaign.{$log->campaign_id}.stats");
        }

        // Clear subscriber-specific cache
        if ($log->subscriber_id) {
            cache()->forget("subscriber.{$log->subscriber_id}.tracking");
            cache()->forget("subscriber.{$log->subscriber_id}.stats");
        }

        // Clear tracking stats cache
        cache()->forget('tracking.stats.total');
        cache()->forget('tracking.stats.today');
    }
}
