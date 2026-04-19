<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Modules\Seo\Jobs\SubmitUrlsToIndexNowJob;
use Modules\Seo\Mail\SeoScoreDropMail;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Tests\TestCase;

class SeoMetaObserverTest extends TestCase
{
    public function test_score_drop_above_threshold_queues_email(): void
    {
        Mail::fake();
        Config::set('Seo.notifications.score_drop_threshold', 10);
        Config::set('Seo.webhooks.notify_score_drop', false);

        $meta = SeoMeta::factory()->create(['seo_score' => 85]);

        $meta->update(['seo_score' => 60]); // drop of 25

        Mail::assertQueued(SeoScoreDropMail::class, function ($mail) use ($meta) {
            return $mail->meta->is($meta);
        });
    }

    public function test_score_drop_below_threshold_does_not_notify(): void
    {
        Mail::fake();
        Config::set('Seo.notifications.score_drop_threshold', 10);

        $meta = SeoMeta::factory()->create(['seo_score' => 85]);

        $meta->update(['seo_score' => 82]); // drop of 3

        Mail::assertNotQueued(SeoScoreDropMail::class);
    }

    public function test_score_increase_does_not_notify(): void
    {
        Mail::fake();
        Config::set('Seo.notifications.score_drop_threshold', 10);

        $meta = SeoMeta::factory()->create(['seo_score' => 60]);

        $meta->update(['seo_score' => 90]); // increase

        Mail::assertNotQueued(SeoScoreDropMail::class);
    }

    public function test_indexnow_job_dispatches_on_meaningful_change(): void
    {
        Queue::fake();
        Config::set('seohelper.indexnow.enabled', true);
        Config::set('seohelper.indexnow.auto_submit', true);
        Config::set('seohelper.indexnow.key', 'validkey123456');
        Config::set('app.url', 'https://example.com');

        $meta = SeoMeta::factory()->create([
            'canonical_url' => 'https://example.com/page',
            'title' => 'Original Title',
        ]);

        $meta->update(['title' => 'New Title']);

        Queue::assertPushed(SubmitUrlsToIndexNowJob::class);
    }

    public function test_indexnow_job_does_not_dispatch_when_disabled(): void
    {
        Queue::fake();
        Config::set('seohelper.indexnow.auto_submit', false);

        $meta = SeoMeta::factory()->create(['canonical_url' => 'https://example.com/page']);

        $meta->update(['title' => 'Changed']);

        Queue::assertNotPushed(SubmitUrlsToIndexNowJob::class);
    }
}
