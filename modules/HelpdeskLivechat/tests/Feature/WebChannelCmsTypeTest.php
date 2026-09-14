<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Engagement\Database\Factories\PlatformIntegrationFactory;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\InboxesController;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Verifies that the new CMS-integration columns (`cms_type`,
 * `platform_integration_id`) on Web channel inboxes:
 *  - persist via the model when set explicitly,
 *  - are filtered properly by the controller's syncWebChannel guards,
 *  - reset to null when the admin switches back to `custom`.
 *
 * The controller method is private; we invoke it via reflection so we can
 * exercise its reset/guard logic without setting up the full HTTP/auth chain.
 */
class WebChannelCmsTypeTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_web_model_persists_cms_type_and_platform_integration_id(): void
    {
        $web = WebFactory::new()->create([
            'cms_type' => 'prestashop',
            'platform_integration_id' => null,
        ]);

        $this->assertSame('prestashop', $web->fresh()->cms_type);
        $this->assertNull($web->fresh()->platform_integration_id);
    }

    public function test_web_model_defaults_cms_type_to_custom(): void
    {
        $web = WebFactory::new()->create();

        $this->assertSame('custom', $web->fresh()->cms_type);
    }

    public function test_sync_web_channel_persists_cms_type_when_provided(): void
    {
        $web = WebFactory::new()->create(['cms_type' => 'custom']);
        $inbox = $this->createInboxFor($web);

        $request = Request::create('/dummy', 'POST', [
            'credentials' => [],
            'widget' => [
                'cms_type' => 'shopify',
            ],
        ]);

        $this->invokeSync($inbox, $request);

        $this->assertSame('shopify', $web->fresh()->cms_type);
    }

    public function test_sync_web_channel_resets_platform_integration_id_when_cms_type_is_custom(): void
    {
        $integration = PlatformIntegrationFactory::new()->prestashop()->create();

        $web = WebFactory::new()->create([
            'cms_type' => 'prestashop',
            'platform_integration_id' => $integration->id,
        ]);
        $inbox = $this->createInboxFor($web);

        $request = Request::create('/dummy', 'POST', [
            'credentials' => [],
            'widget' => [
                'cms_type' => 'custom',
                'platform_integration_id' => $integration->id,
            ],
        ]);

        $this->invokeSync($inbox, $request);

        $fresh = $web->fresh();
        $this->assertSame('custom', $fresh->cms_type);
        $this->assertNull($fresh->platform_integration_id);
    }

    private function createInboxFor(Web $web): Inbox
    {
        return Inbox::firstOrCreate(
            ['channel_type' => 'web', 'channel_id' => $web->id],
            ['uid' => (string) Str::uuid(), 'name' => 'Test CMS Inbox', 'is_active' => true]
        );
    }

    private function invokeSync(Inbox $inbox, Request $request): void
    {
        $controller = app(InboxesController::class);
        $method = new ReflectionMethod($controller, 'syncChannelRecord');
        $method->setAccessible(true);
        $method->invoke($controller, $inbox, $request);
    }
}
