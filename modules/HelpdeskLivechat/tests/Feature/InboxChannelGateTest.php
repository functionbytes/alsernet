<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

/**
 * Verifies that the "web" channel option for new Inboxes is gated by the
 * presence of the HelpdeskLivechat module. While the module is installed
 * (which it is during these tests), the option must be available.
 */
class InboxChannelGateTest extends TestCase
{
    public function test_web_channel_is_available_when_module_enabled(): void
    {
        $available = Inbox::availableChannelTypes();

        $this->assertContains('web', $available);
    }

    public function test_available_labels_include_web_channel_when_module_enabled(): void
    {
        $labels = Inbox::availableChannelLabels();

        $this->assertArrayHasKey('web', $labels);
        $this->assertSame('Website (chat widget)', $labels['web']);
    }

    public function test_channel_type_map_resolves_web_to_livechat_module(): void
    {
        $this->assertSame(
            Web::class,
            Inbox::CHANNEL_TYPE_MAP['web']
        );
    }

    public function test_widget_alias_was_removed_from_channel_type_map(): void
    {
        $this->assertArrayNotHasKey('widget', Inbox::CHANNEL_TYPE_MAP);
    }
}
