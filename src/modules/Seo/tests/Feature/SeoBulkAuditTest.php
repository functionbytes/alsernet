<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\Seo\Jobs\BulkSeoAuditJob;
use Modules\Seo\Jobs\CheckBrokenLinksJob;
use Modules\Seo\Tests\TestCase;

class SeoBulkAuditTest extends TestCase
{
    public function test_bulk_audit_dispatches_job(): void
    {
        Queue::fake();

        $user = $this->createUser(['Seo.audit.index']);

        $this->actingAs($user)
            ->postJson(route('settings.seo.audit.bulk-start'))
            ->assertOk();

        Queue::assertPushed(BulkSeoAuditJob::class);
    }

    public function test_bulk_audit_returns_409_when_already_running(): void
    {
        Cache::put('seo.bulk_audit.progress', ['status' => 'running', 'processed' => 5, 'total' => 100], 3600);

        $user = $this->createUser(['Seo.audit.index']);

        $this->actingAs($user)
            ->postJson(route('settings.seo.audit.bulk-start'))
            ->assertStatus(409);
    }

    public function test_bulk_audit_progress_returns_idle_when_no_job(): void
    {
        Cache::forget('seo.bulk_audit.progress');

        $user = $this->createUser(['Seo.audit.index']);

        $this->actingAs($user)
            ->getJson(route('settings.seo.audit.bulk-progress'))
            ->assertOk()
            ->assertJsonFragment(['status' => 'idle']);
    }

    public function test_broken_links_check_dispatches_job(): void
    {
        Queue::fake();

        $user = $this->createUser(['Seo.audit.index']);

        $this->actingAs($user)
            ->postJson(route('settings.seo.audit.broken-links-start'))
            ->assertOk();

        Queue::assertPushed(CheckBrokenLinksJob::class);
    }
}
