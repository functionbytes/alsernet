<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Tests\TestCase;

class RunChatFlowTestCasesCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private bool $helpdeskDbAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function flowWithBranchingNodes(array $overrides = []): ChatFlow
    {
        return ChatFlow::factory()->create([
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'parentId' => null, 'label' => 'Inicio', 'data' => []],
                ['id' => 'qr', 'type' => 'quick_replies', 'parentId' => 'start', 'label' => 'Menú',
                    'data' => ['text' => 'Elige', 'options' => ['Ventas', 'Soporte']]],
                ['id' => 'v', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Ventas', 'data' => ['text' => 'Bienvenido al área de ventas']],
                ['id' => 's', 'type' => 'message', 'parentId' => 'qr', 'label' => 'Soporte', 'data' => ['text' => 'Te ayuda soporte']],
            ],
            ...$overrides,
        ]);
    }

    public function test_exits_successfully_when_all_test_cases_pass(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $flow->testCases()->create([
            'name' => 'Va a ventas',
            'steps' => [['input' => '1', 'expect_contains' => 'ventas']],
        ]);

        $this->artisan('chatflow:test-cases', ['flow' => (string) $flow->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_chat_flow_test_cases', [
            'chat_flow_id' => $flow->id,
            'last_result' => 'passed',
        ], 'helpdesk');
    }

    public function test_exits_with_failure_when_a_test_case_fails(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $flow->testCases()->create([
            'name' => 'Escenario roto',
            'steps' => [['input' => '2', 'expect_contains' => 'ventas']],
        ]);

        $this->artisan('chatflow:test-cases', ['flow' => (string) $flow->id])
            ->assertFailed();

        $this->assertDatabaseHas('helpdesk_chat_flow_test_cases', [
            'chat_flow_id' => $flow->id,
            'last_result' => 'failed',
        ], 'helpdesk');
    }

    public function test_resolves_flow_by_uid(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();
        $flow->testCases()->create([
            'name' => 'Va a ventas',
            'steps' => [['input' => '1', 'expect_contains' => 'ventas']],
        ]);

        $this->artisan('chatflow:test-cases', ['flow' => $flow->uid])
            ->assertSuccessful();
    }

    public function test_errors_for_unknown_flow(): void
    {
        $this->requireHelpdeskDb();

        $this->artisan('chatflow:test-cases', ['flow' => 'no-such-flow'])
            ->assertFailed();
    }

    public function test_requires_flow_argument_or_all_option(): void
    {
        $this->requireHelpdeskDb();

        $this->artisan('chatflow:test-cases')
            ->assertFailed();
    }

    public function test_all_option_runs_every_active_flow(): void
    {
        $this->requireHelpdeskDb();
        $active = $this->flowWithBranchingNodes(['status' => 'active']);
        $active->testCases()->create([
            'name' => 'Va a ventas',
            'steps' => [['input' => '1', 'expect_contains' => 'ventas']],
        ]);
        $draft = ChatFlow::factory()->draft()->create();
        $draft->testCases()->create([
            'name' => 'No debería correr',
            'steps' => [['input' => 'x', 'expect_contains' => 'inalcanzable']],
        ]);

        $this->artisan('chatflow:test-cases', ['--all' => true])
            ->assertSuccessful();

        $this->assertSame('passed', $active->testCases()->first()->fresh()->last_result);
        $this->assertNull($draft->testCases()->first()->fresh()->last_result);
    }

    public function test_succeeds_with_no_output_when_flow_has_no_test_cases(): void
    {
        $this->requireHelpdeskDb();
        $flow = $this->flowWithBranchingNodes();

        $this->artisan('chatflow:test-cases', ['flow' => (string) $flow->id])
            ->assertSuccessful();
    }
}
