<?php

namespace Modules\Helpdesk\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class V1ScopingTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    /**
     * @return array{0: User, 1: Conversation, 2: Conversation}
     */
    private function scenario(): array
    {
        // forAgent() consulta estos permisos (bypass de scoping); deben existir
        // o Spatie lanza PermissionDoesNotExist. En prod están sembrados.
        Permission::findOrCreate('helpdesk.manage', 'web');
        Permission::findOrCreate('helpdesk.customers.manage', 'web');
        Permission::findOrCreate('helpdesk.conversations.view', 'web');
        Permission::findOrCreate('helpdesk.customers.view', 'web');

        $inboxA = Inbox::create(['name' => 'Inbox A', 'channel_type' => 'web', 'is_active' => true]);
        $inboxB = Inbox::create(['name' => 'Inbox B', 'channel_type' => 'web', 'is_active' => true]);

        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.conversations.view', 'helpdesk.customers.view']);
        AgentInboxCapacity::create([
            'user_id' => $agent->id,
            'inbox_id' => $inboxA->id,
            'max_concurrent' => 10,
            'accepts_new' => true,
        ]);

        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );

        $convA = Conversation::factory()->create(['inbox_id' => $inboxA->id, 'status_id' => $status->id]);
        $convB = Conversation::factory()->create(['inbox_id' => $inboxB->id, 'status_id' => $status->id]);

        return [$agent, $convA, $convB];
    }

    private function ids(array $data): array
    {
        return collect($data['data'] ?? $data)->pluck('id')->all();
    }

    public function test_conversations_index_solo_devuelve_las_del_inbox_del_agente(): void
    {
        [$agent, $convA, $convB] = $this->scenario();

        Sanctum::actingAs($agent);
        $response = $this->getJson('/api/v1/helpdesk/conversations?per_page=100')->assertOk();

        $ids = $this->ids($response->json('data'));
        $this->assertContains($convA->id, $ids);
        $this->assertNotContains($convB->id, $ids);
    }

    public function test_customers_index_solo_devuelve_los_del_inbox_del_agente(): void
    {
        [$agent, $convA, $convB] = $this->scenario();

        Sanctum::actingAs($agent);
        $response = $this->getJson('/api/v1/helpdesk/customers?per_page=100')->assertOk();

        $ids = $this->ids($response->json('data'));
        $this->assertContains($convA->customer_id, $ids);
        $this->assertNotContains($convB->customer_id, $ids);
    }
}
