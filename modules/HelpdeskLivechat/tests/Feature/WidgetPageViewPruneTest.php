<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Jobs\PruneWidgetTrackingJob;
use Modules\HelpdeskLivechat\Models\WidgetPageView;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Tests\Concerns\SeedsOpenConversationStatus;
use Tests\TestCase;

class WidgetPageViewPruneTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsOpenConversationStatus;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOpenConversationStatus();
        Cache::flush();
    }

    private function heartbeat(string $websiteToken, string $session, string $url): void
    {
        // Sin el atajo de caché, para que cada latido llegue a updateSession().
        Cache::flush();

        $this->withHeaders(['X-Website-Token' => $websiteToken])
            ->postJson(route('helpdesk-livechat.widget.session.heartbeat'), [
                'session_token' => $session,
                'url' => $url,
            ])->assertOk();
    }

    public function test_page_view_is_created_only_when_url_changes(): void
    {
        $web = WebFactory::new()->create();
        $token = 'sess_pv_'.uniqid();

        $this->heartbeat($web->website_token, $token, 'https://shop.example/a');

        $session = WidgetSession::on('helpdesk')->where('session_token', $token)->firstOrFail();
        $afterFirst = WidgetPageView::query()->where('session_id', $session->id)->count();

        $this->travel(10)->seconds();
        $this->heartbeat($web->website_token, $token, 'https://shop.example/a');
        $this->travel(10)->seconds();
        $this->heartbeat($web->website_token, $token, 'https://shop.example/a');

        $this->assertSame($afterFirst, WidgetPageView::query()->where('session_id', $session->id)->count());

        $this->heartbeat($web->website_token, $token, 'https://shop.example/b');

        $this->assertSame($afterFirst + 1, WidgetPageView::query()->where('session_id', $session->id)->count());
    }

    public function test_prune_job_deletes_old_sessions_and_page_views_only(): void
    {
        config(['helpdesklivechat.retention.widget_sessions_days' => 90]);

        $old = WidgetSession::on('helpdesk')->create([
            'session_token' => 'old_'.uniqid(),
            'current_url' => 'https://shop.example/',
            'started_at' => now()->subDays(200),
            'last_activity_at' => now()->subDays(100),
        ]);
        $recent = WidgetSession::on('helpdesk')->create([
            'session_token' => 'recent_'.uniqid(),
            'current_url' => 'https://shop.example/',
            'started_at' => now()->subDays(200),
            'last_activity_at' => now()->subDays(1),
        ]);

        WidgetPageView::create(['session_id' => $old->id, 'url' => 'https://shop.example/', 'viewed_at' => now()->subDays(100)]);
        WidgetPageView::create(['session_id' => $recent->id, 'url' => 'https://shop.example/old', 'viewed_at' => now()->subDays(100)]);
        WidgetPageView::create(['session_id' => $recent->id, 'url' => 'https://shop.example/new', 'viewed_at' => now()->subDay()]);

        (new PruneWidgetTrackingJob)->handle();

        $this->assertNull(WidgetSession::on('helpdesk')->find($old->id));
        $this->assertNotNull(WidgetSession::on('helpdesk')->find($recent->id));
        $this->assertSame(1, WidgetPageView::query()->where('session_id', $recent->id)->count());
        $this->assertSame(0, WidgetPageView::query()->where('session_id', $old->id)->count());
    }
}
