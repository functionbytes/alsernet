<?php

namespace Modules\Engagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Engagement\Models\AutomationFlow;
use Modules\Engagement\Models\AutomationRun;
use Modules\Engagement\Services\AutomationEngine;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    private AutomationEngine $engine;

    private Inbox $inbox;

    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(AutomationEngine::class);

        $web = Web::create([
            'website_url' => 'https://x.com',
            'widget_color' => '#b10100',
            'widget_position' => 'right',
            'welcome_title' => 't',
            'welcome_tagline' => 'l',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'T',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);
    }

    public function test_starts_run_with_message_node_and_completes_when_no_edge(): void
    {
        $flow = AutomationFlow::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Greeting',
            'trigger_type' => 'manual',
            'nodes' => [['id' => 'n1', 'type' => 'message', 'config' => ['text' => 'Hola']]],
            'edges' => [],
            'is_active' => true,
        ]);

        $run = $this->engine->start($flow, 'session-1', null);

        $this->assertNotNull($run);
        $this->assertSame(AutomationRun::STATUS_COMPLETED, $run->fresh()->status);
    }

    public function test_advances_through_message_chain(): void
    {
        $flow = AutomationFlow::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Chain',
            'trigger_type' => 'manual',
            'nodes' => [
                ['id' => 'a', 'type' => 'message', 'config' => ['text' => '1']],
                ['id' => 'b', 'type' => 'message', 'config' => ['text' => '2']],
            ],
            'edges' => [['from' => 'a', 'to' => 'b']],
            'is_active' => true,
        ]);

        $run = $this->engine->start($flow, 'session-2', null);

        $this->assertSame(AutomationRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertGreaterThanOrEqual(2, count($run->fresh()->context['_outbox'] ?? []));
    }

    public function test_question_node_pauses_for_input(): void
    {
        $flow = AutomationFlow::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'Q',
            'trigger_type' => 'manual',
            'nodes' => [
                ['id' => 'q1', 'type' => 'question', 'config' => ['text' => '¿OK?']],
                ['id' => 'yes', 'type' => 'message', 'config' => ['text' => 'Bien']],
            ],
            'edges' => [['from' => 'q1', 'to' => 'yes', 'branch' => 'sí']],
            'is_active' => true,
        ]);

        $run = $this->engine->start($flow, 'session-3', null);

        $this->assertSame(AutomationRun::STATUS_RUNNING, $run->fresh()->status);
        $this->assertSame('q1', $run->fresh()->current_node_id);
    }

    public function test_starts_flow_for_event_when_active(): void
    {
        AutomationFlow::query()->create([
            'inbox_id' => $this->inbox->id,
            'name' => 'OnPurchase',
            'trigger_type' => 'event',
            'trigger_event' => 'purchase',
            'nodes' => [['id' => 'thanks', 'type' => 'message', 'config' => ['text' => 'Gracias']]],
            'edges' => [],
            'is_active' => true,
        ]);

        $this->engine->startFlowsForEvent('purchase', 'session-4', $this->inbox->id, null);

        $this->assertDatabaseHas('engagement_automation_runs', [
            'session_token' => 'session-4',
        ]);
    }
}
