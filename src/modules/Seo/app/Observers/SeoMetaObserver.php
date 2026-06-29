<?php

namespace Modules\Seo\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Seo\Jobs\SubmitUrlsToIndexNowJob;
use Modules\Seo\Mail\SeoScoreDropMail;
use Modules\Seo\Models\SeoAlert;
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

        // Auto-submit URL to IndexNow on meaningful changes
        $this->maybeSubmitIndexNow($seoMeta);

        // Notify on significant SEO score drops
        if ($seoMeta->wasChanged('seo_score')) {
            $previousScore = (int) $seoMeta->getOriginal('seo_score');
            $currentScore = (int) $seoMeta->seo_score;
            $threshold = (int) seo_setting('notifications.score_drop_threshold', config('Seo.notifications.score_drop_threshold', 10));

            if ($currentScore < $previousScore && ($previousScore - $currentScore) >= $threshold) {
                $email = (string) seo_setting('notifications.email', config('Seo.notifications.email', config('mail.from.address')));

                Mail::to($email)->queue(new SeoScoreDropMail($seoMeta, $previousScore, $currentScore));

                if (seo_setting('webhooks.notify_score_drop', config('Seo.webhooks.notify_score_drop', true))) {
                    (new WebhookNotificationService)->sendScoreDrop(
                        $seoMeta->title ?? 'Sin título',
                        $seoMeta->canonical_url ?? '',
                        $previousScore,
                        $currentScore
                    );
                }

                SeoAlert::raise(
                    SeoAlert::TYPE_SCORE_DROP,
                    SeoAlert::SEVERITY_WARNING,
                    'Caída de SEO score',
                    "El score bajó de {$previousScore} a {$currentScore} en \"".($seoMeta->title ?? 'sin título').'".',
                    $seoMeta->canonical_url,
                    ['meta_id' => $seoMeta->id, 'previous' => $previousScore, 'current' => $currentScore]
                );
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

        $this->maybeSubmitIndexNow($seoMeta);
    }

    /**
     * Enviar la URL canónica a IndexNow cuando cambian campos visibles
     * (title/description/canonical/robots) y auto-submit está activo.
     */
    private function maybeSubmitIndexNow(SeoMeta $seoMeta): void
    {
        if (! seo_setting('indexnow.enabled', config('seohelper.indexnow.enabled')) || ! seo_setting('indexnow.auto_submit', config('seohelper.indexnow.auto_submit', true))) {
            return;
        }

        $url = $seoMeta->canonical_url;
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        // En updated() solo disparamos si cambió algo relevante para el bot
        if ($seoMeta->exists && ! $seoMeta->wasRecentlyCreated) {
            $meaningful = ['title', 'description', 'canonical_url', 'robots', 'og_title', 'og_description'];
            if (empty(array_intersect($meaningful, array_keys($seoMeta->getChanges())))) {
                return;
            }
        }

        // robots=noindex → no tiene sentido reclamar indexación
        if ($seoMeta->robots && str_contains(strtolower((string) $seoMeta->robots), 'noindex')) {
            return;
        }

        SubmitUrlsToIndexNowJob::dispatch([$url]);
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
