<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * Publishing must be gated by the flow's regression test cases: a flow with a
 * failing scenario cannot go live unless the caller explicitly skips them.
 */
class ChatFlowPublishGateTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $user;

    private bool $helpdeskDbAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChatFlowPermissionsSeeder::class);

        // routes/managers.php gatea todo el grupo con role:super-admin|super-settings
        // (aparte de la policy por permiso); sin un rol real Spatie::role() deniega
        // con 403 antes de llegar al controller, igual que en ChatFlowsControllerTest.
        $superSettings = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole($superSettings);
        $this->user->givePermissionTo(['chatflow.view', 'chatflow.update']);

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
            $this->markTestSkipped('helpdesk_chat_flows table not available in test DB (system_test_pristine).');
        }
    }

    /**
     * Start → quick_replies(Ventas/Soporte) → message, mirrors the fixture used
     * by ChatFlowTestRunnerTest so "1" routes to the Ventas reply.
     */
    private function flowWithBranchingNodes(): ChatFlow
    {
        return ChatFlow::factory()->draft()->create([
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'parentId' => null, 'label' => 'Inicio', 'data' => []],
                ['id' => 'qr', 'type' => 'quick_replies', 'parentId' => 'start', 'label' => 'Menú',
                    'data' => ['text' => 'Elige', 'options' => ['Ventas', 'Soporte']]],
                ['id' => 'v', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Ventas', 'data' => ['text' => 'Bienvenido al área de ventas']],
                ['id' => 's', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Soporte', 'data' => ['text' => 'Te ayuda soporte']],
            ],
        ]);
    }

    public function test_publish_succeeds_when_flow_has_no_test_cases(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->draft()->create();

        $this->actingAs($this->user)
            ->post(route('chatflow.publish', $flow))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', ['id' => $flow->id, 'status' => 'active'], 'helpdesk');
    }

    public function test_publish_succeeds_when_all_test_cases_pass(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $flow->testCases()->create([
            'name' => 'Va a ventas',
            'steps' => [['input' => '1', 'expect_contains' => 'ventas']],
        ]);

        $this->actingAs($this->user)
            ->post(route('chatflow.publish', $flow))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', ['id' => $flow->id, 'status' => 'active'], 'helpdesk');
    }

    public function test_publish_is_blocked_when_a_test_case_fails(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $testCase = $flow->testCases()->create([
            'name' => 'Escenario roto',
            'steps' => [['input' => '2', 'expect_contains' => 'ventas']], // option 2 routes to soporte
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('chatflow.publish', $flow));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Escenario roto', session('error'));

        $this->assertDatabaseHas('helpdesk_chat_flows', ['id' => $flow->id, 'status' => 'draft'], 'helpdesk');
        $this->assertSame('failed', $testCase->fresh()->last_result);
    }

    public function test_publish_json_returns_422_when_a_test_case_fails(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $flow->testCases()->create([
            'name' => 'Escenario roto',
            'steps' => [['input' => '2', 'expect_contains' => 'ventas']],
        ]);

        $this->actingAs($this->user)
            ->postJson(route('chatflow.publish', $flow))
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonCount(1, 'failing_tests');

        $this->assertDatabaseHas('helpdesk_chat_flows', ['id' => $flow->id, 'status' => 'draft'], 'helpdesk');
    }

    public function test_skip_tests_bypasses_failing_test_cases(): void
    {
        $this->requireHelpdeskDb();
        Log::spy();

        $flow = $this->flowWithBranchingNodes();
        $flow->testCases()->create([
            'name' => 'Escenario roto',
            'steps' => [['input' => '2', 'expect_contains' => 'ventas']],
        ]);

        $this->actingAs($this->user)
            ->post(route('chatflow.publish', $flow), ['skip_tests' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_chat_flows', ['id' => $flow->id, 'status' => 'active'], 'helpdesk');

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context) => str_contains($message, 'saltando')
                && $context['chat_flow_id'] === $flow->id
                && $context['user_id'] === $this->user->id)
            ->once();
    }

    public function test_publish_still_requires_update_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo('chatflow.view');

        $this->actingAs($viewOnly)
            ->post(route('chatflow.publish', $flow))
            ->assertForbidden();
    }
}
