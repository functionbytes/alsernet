<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Listeners\AutoAssignNewConversation;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\RoutingRule;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\Helpdesk\Services\RoutingRuleService;
use Modules\Helpdesk\Services\SkillsRoutingService;
use Tests\TestCase;

class AutoAssignNewConversationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    private function makeConversationWithItem(string $body, array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create(array_merge([
            'status_id' => $this->openStatus->id,
            'channel' => 'whatsapp',
            'assignee_id' => null,
            'group_id' => null,
        ], $overrides));

        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => $body,
        ]);

        $conversation->load('items');

        return $conversation;
    }

    private function listener(): AutoAssignNewConversation
    {
        return new AutoAssignNewConversation(
            app(RoutingRuleService::class),
            app(SkillsRoutingService::class),
            app(AutoAssignmentService::class),
        );
    }

    public function test_assigns_conversation_to_user_matching_routing_rule_when_enabled(): void
    {
        config()->set('helpdesk.auto_assignment.enabled', true);

        $agent = User::factory()->create();

        RoutingRule::create([
            'keyword' => 'factura',
            'match_type' => 'contains',
            'assign_to_user_id' => $agent->id,
            'assign_to_team_id' => null,
            'priority' => 1,
            'is_active' => true,
        ]);

        $conversation = $this->makeConversationWithItem('Necesito mi factura urgente');

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertSame($agent->id, $conversation->fresh()->assignee_id);
    }

    public function test_does_not_assign_when_auto_assignment_disabled(): void
    {
        config()->set('helpdesk.auto_assignment.enabled', false);

        $agent = User::factory()->create();

        RoutingRule::create([
            'keyword' => 'factura',
            'match_type' => 'contains',
            'assign_to_user_id' => $agent->id,
            'assign_to_team_id' => null,
            'priority' => 1,
            'is_active' => true,
        ]);

        $conversation = $this->makeConversationWithItem('Necesito mi factura urgente');

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertNull($conversation->fresh()->assignee_id);
    }

    public function test_does_not_reassign_conversation_already_assigned(): void
    {
        config()->set('helpdesk.auto_assignment.enabled', true);

        $existingAgent = User::factory()->create();
        $ruleAgent = User::factory()->create();

        RoutingRule::create([
            'keyword' => 'factura',
            'match_type' => 'contains',
            'assign_to_user_id' => $ruleAgent->id,
            'assign_to_team_id' => null,
            'priority' => 1,
            'is_active' => true,
        ]);

        $conversation = $this->makeConversationWithItem('Necesito mi factura urgente', [
            'assignee_id' => $existingAgent->id,
            'assigned_at' => now(),
        ]);

        $this->listener()->handle(new ConversationCreated($conversation));

        $this->assertSame($existingAgent->id, $conversation->fresh()->assignee_id);
    }
}
