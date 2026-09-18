<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Tests\TestCase;

/**
 * Regression coverage for the Settings → Integraciones kill switch
 * (`livechat.integration_enabled`) on the widget's operational status.
 * Web::isOpen() is the single entry point the widget config (getWidgetConfig
 * → 'is_open') and every widget consumer relies on to know whether the chat
 * is available: it must report closed instead of erroring out while the
 * integration is disabled, regardless of business hours or agent presence.
 */
class LivechatIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk'];

    public function test_widget_reports_closed_when_integration_disabled(): void
    {
        Setting::set('livechat.integration_enabled', '0', 'integrations');

        // Business hours disabled (always within hours) and no inbox attached,
        // so without the toggle this would resolve to open.
        $web = WebFactory::new()->create(['business_hours' => ['enabled' => false]]);

        $this->assertFalse($web->isOpen());
    }

    public function test_widget_reports_open_when_integration_enabled_by_default(): void
    {
        $web = WebFactory::new()->create(['business_hours' => ['enabled' => false]]);

        $this->assertTrue($web->isOpen());
    }
}
