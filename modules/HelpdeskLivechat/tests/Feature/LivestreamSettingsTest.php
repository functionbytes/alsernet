<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\InboxesController;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use ReflectionMethod;
use Tests\TestCase;

class LivestreamSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_web_model_persists_enable_live_view_and_enable_screen_share(): void
    {
        $web = WebFactory::new()->create([
            'enable_live_view' => true,
            'enable_screen_share' => true,
        ]);

        $fresh = $web->fresh();
        $this->assertTrue($fresh->enable_live_view);
        $this->assertTrue($fresh->enable_screen_share);
    }

    public function test_widget_config_exposes_assistance_flags(): void
    {
        $web = WebFactory::new()->create([
            'enable_live_view' => true,
            'enable_screen_share' => false,
        ]);

        $config = $web->getWidgetConfig();

        $this->assertSame(true, $config['enable_live_view']);
        $this->assertSame(false, $config['enable_screen_share']);
    }

    public function test_sync_web_channel_persists_assistance_flags_via_widget_payload(): void
    {
        $web = WebFactory::new()->create([
            'enable_live_view' => false,
            'enable_screen_share' => false,
        ]);
        $inbox = Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Test', 'is_active' => true]
        );

        $request = Request::create('/dummy', 'POST', [
            'credentials' => [],
            'widget' => [
                'enable_live_view' => true,
                'enable_screen_share' => true,
            ],
        ]);

        $controller = app(InboxesController::class);
        $method = new ReflectionMethod($controller, 'syncChannelRecord');
        $method->setAccessible(true);
        $method->invoke($controller, $inbox, $request);

        $fresh = $web->fresh();
        $this->assertTrue($fresh->enable_live_view);
        $this->assertTrue($fresh->enable_screen_share);
    }

    public function test_default_assistance_flags_are_false(): void
    {
        $web = WebFactory::new()->create();

        $this->assertFalse((bool) $web->fresh()->enable_live_view);
        $this->assertFalse((bool) $web->fresh()->enable_screen_share);
    }
}
