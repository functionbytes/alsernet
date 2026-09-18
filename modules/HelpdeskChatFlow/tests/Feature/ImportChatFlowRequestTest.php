<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Structural validation of flow imports, moved from inline controller checks
 * to ImportChatFlowRequest — including trigger_conditions shape validation.
 */
class ImportChatFlowRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $user;

    private bool $helpdeskDbAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChatFlowPermissionsSeeder::class);

        $superAdmin = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole($superAdmin);
        $this->user->givePermissionTo(['chatflow.view', 'chatflow.create']);

        try {
            \DB::connection('helpdesk')->statement('SELECT 1 FROM helpdesk_chat_flows LIMIT 1');
            $this->helpdeskDbAvailable = true;
        } catch (\Throwable) {
            $this->helpdeskDbAvailable = false;
        }
    }

    private function requireHelpdeskDb(): void
    {
        if (! $this->helpdeskDbAvailable) {
            $this->markTestSkipped('helpdesk_chat_flows table not available in test DB.');
        }
    }

    private function import(array $payload)
    {
        return $this->actingAs($this->user)
            ->from(route('chatflow.index'))
            ->post(route('chatflow.import'), [
                'json' => json_encode($payload),
            ]);
    }

    private function validNodes(): array
    {
        return [
            ['id' => 'n1', 'type' => 'start', 'config' => []],
            ['id' => 'n2', 'type' => 'end', 'config' => []],
        ];
    }

    public function test_valid_flow_is_imported_as_draft(): void
    {
        $this->requireHelpdeskDb();

        $response = $this->import([
            'name' => 'Flujo de prueba',
            'trigger_type' => 'keyword',
            'trigger_conditions' => ['keywords' => ['hola'], 'timeout_minutes' => 30],
            'nodes' => $this->validNodes(),
        ]);

        $flow = ChatFlow::query()->where('name', 'Flujo de prueba (importado)')->first();

        $this->assertNotNull($flow);
        $this->assertSame('draft', $flow->status);
        $this->assertSame('keyword', $flow->trigger_type);
        $this->assertSame(['hola'], $flow->trigger_conditions['keywords']);
        $response->assertRedirect(route('chatflow.edit', $flow));
    }

    public function test_malformed_json_is_rejected(): void
    {
        $this->requireHelpdeskDb();

        $this->import([])->assertSessionHas('error');

        $this->actingAs($this->user)
            ->from(route('chatflow.index'))
            ->post(route('chatflow.import'), ['json' => 'not json {'])
            ->assertSessionHas('error');

        $this->assertSame(0, ChatFlow::query()->where('name', 'like', '%(importado)')->count());
    }

    public function test_missing_start_node_is_rejected(): void
    {
        $this->requireHelpdeskDb();

        $this->import([
            'name' => 'Sin inicio',
            'nodes' => [['id' => 'n1', 'type' => 'end', 'config' => []]],
        ])->assertSessionHas('error');

        $this->assertSame(0, ChatFlow::query()->where('name', 'Sin inicio (importado)')->count());
    }

    public function test_unknown_node_type_is_rejected(): void
    {
        $this->requireHelpdeskDb();

        $this->import([
            'name' => 'Nodo raro',
            'nodes' => [
                ['id' => 'n1', 'type' => 'start'],
                ['id' => 'n2', 'type' => 'definitely_not_a_node_type'],
            ],
        ])->assertSessionHas('error');

        $this->assertSame(0, ChatFlow::query()->where('name', 'Nodo raro (importado)')->count());
    }

    public function test_invalid_trigger_conditions_shape_is_rejected(): void
    {
        $this->requireHelpdeskDb();

        // keywords must be an array of strings, timeout_minutes an int in range.
        $this->import([
            'name' => 'Condiciones rotas',
            'trigger_type' => 'keyword',
            'trigger_conditions' => [
                'keywords' => 'hola',
                'timeout_minutes' => 'muchos',
                'ab_split' => 400,
            ],
            'nodes' => $this->validNodes(),
        ])->assertSessionHas('error');

        $this->assertSame(0, ChatFlow::query()->where('name', 'Condiciones rotas (importado)')->count());
    }

    public function test_node_cap_is_enforced(): void
    {
        $this->requireHelpdeskDb();

        config(['helpdeskchatflow.import.max_nodes' => 3]);

        $nodes = [['id' => 'n1', 'type' => 'start']];
        foreach (range(2, 5) as $i) {
            $nodes[] = ['id' => "n{$i}", 'type' => 'end'];
        }

        $this->import([
            'name' => 'Demasiados nodos',
            'nodes' => $nodes,
        ])->assertSessionHas('error');

        $this->assertSame(0, ChatFlow::query()->where('name', 'Demasiados nodos (importado)')->count());
    }
}
