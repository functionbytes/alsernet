<?php

namespace Modules\Seo\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Seo\Mail\SeoScoreDropMail;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\WebhookNotificationService;
use Spatie\Activitylog\Models\Activity;

class SeoMetaObserver
{
    /**
     * Handle the SeoMeta "updated" event.
     */
    public function updated(SeoMeta $seoMeta): void
    {
        // Invalidate render cache if caching is enabled
        if (config('Seo.cache.enabled', false)) {
            $cacheKey = 'seo_render_'.md5(get_class($seoMeta).$seoMeta->seoable_id);
            Cache::forget($cacheKey);
        }

        // Notify on significant SEO score drops
        if ($seoMeta->wasChanged('seo_score')) {
            $previousScore = (int) $seoMeta->getOriginal('seo_score');
            $currentScore = (int) $seoMeta->seo_score;
            $threshold = config('Seo.notifications.score_drop_threshold', 10);

            if ($currentScore < $previousScore && ($previousScore - $currentScore) >= $threshold) {
                $email = config('Seo.notifications.email', config('mail.from.address'));

                Mail::to($email)->queue(new SeoScoreDropMail($seoMeta, $previousScore, $currentScore));

                if (config('Seo.webhooks.notify_score_drop', true)) {
                    (new WebhookNotificationService)->sendScoreDrop(
                        $seoMeta->title ?? 'Sin título',
                        $seoMeta->canonical_url ?? '',
                        $previousScore,
                        $currentScore
                    );
                }
            }
        }

        // Log activity using Spatie ActivityLog if available
        if (class_exists(Activity::class)) {
            $changes = $seoMeta->getChanges();
            // Remove non-meaningful fields from log
            unset($changes['updated_at'], $changes['seo_audited_at']);

            if (! empty($changes)) {
                activity()
                    ->performedOn($seoMeta)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'changed_fields' => array_keys($changes),
                        'seoable_type' => class_basename($seoMeta->seoable_type ?? ''),
                        'seoable_id' => $seoMeta->seoable_id,
                    ])
                    ->log('seo_meta_updated');
            }
        }
    }

    /**
     * Handle the SeoMeta "created" event.
     */
    public function created(SeoMeta $seoMeta): void
    {
        if (class_exists(Activity::class)) {
            activity()
                ->performedOn($seoMeta)
                ->causedBy(auth()->user())
                ->withProperties([
                    'seoable_type' => class_basename($seoMeta->seoable_type ?? ''),
                    'seoable_id' => $seoMeta->seoable_id,
                ])
                ->log('seo_meta_created');
        }
    }

    /**
     * Handle the SeoMeta "deleted" event.
     */
    public function deleted(SeoMeta $seoMeta): void
    {
        if (class_exists(Activity::class)) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'seo_meta_id' => $seoMeta->id,
                    'seoable_type' => class_basename($seoMeta->seoable_type ?? ''),
                    'seoable_id' => $seoMeta->seoable_id,
                    'title' => $seoMeta->title,
                ])
                ->log('seo_meta_deleted');
        }
    }
}
