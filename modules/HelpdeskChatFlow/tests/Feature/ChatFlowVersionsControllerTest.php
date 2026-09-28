<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Spatie\Permission\Models\Role;

class ChatFlowVersionsControllerTest extends TestCase
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

    // ─── index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_ok_for_authorized_user(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();

        $this->actingAs($this->user)
            ->get(route('chatflow.versions', $flow))
            ->assertOk();
    }

    public function test_index_requires_view_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('chatflow.versions', $flow))
            ->assertForbidden();
    }

    // ─── restore ───────────────────────────────────────────────────────────────

    public function test_restore_overwrites_draft_nodes_with_snapshot(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create(['nodes' => [['id' => 'current', 'type' => 'start']]]);
        $version = $flow->versions()->create([
            'name' => $flow->name,
            'nodes' => [['id' => 'old', 'type' => 'start']],
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('chatflow.versions.restore', [$flow, $version->id]))
            ->assertRedirect(route('chatflow.edit', $flow))
            ->assertSessionHas('success');

        $this->assertSame([['id' => 'old', 'type' => 'start']], $flow->fresh()->nodes);
    }

    public function test_restore_404s_for_version_of_another_flow(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $otherFlow = ChatFlow::factory()->create();
        $foreignVersion = $otherFlow->versions()->create([
            'name' => $otherFlow->name,
            'nodes' => [],
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('chatflow.versions.restore', [$flow, $foreignVersion->id]))
            ->assertNotFound();
    }

    // ─── diff ──────────────────────────────────────────────────────────────────

    public function test_diff_compares_version_against_current_draft_by_default(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create([
            'nodes' => [
                ['id' => 'n1', 'type' => 'start', 'label' => 'Inicio nuevo', 'data' => []],
                ['id' => 'n2', 'type' => 'end', 'label' => 'Fin', 'data' => []],
            ],
        ]);
        $version = $flow->versions()->create([
            'name' => $flow->name,
            'nodes' => [['id' => 'n1', 'type' => 'start', 'label' => 'Inicio viejo', 'data' => []]],
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chatflow.versions.diff', [$flow, $version->id]))
            ->assertOk();

        $diff = $response->viewData('diff');
        $this->assertCount(1, $diff['added']);
        $this->assertSame('n2', $diff['added'][0]['id']);
        $this->assertCount(1, $diff['changed']);
        $this->assertSame('n1', $diff['changed'][0]['id']);
        $this->assertNull($response->viewData('against'));
    }

    public function test_diff_compares_against_another_version_when_given(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $versionA = $flow->versions()->create([
            'name' => 'A',
            'nodes' => [['id' => 'n1', 'type' => 'start', 'label' => 'A', 'data' => []]],
            'created_by' => $this->user->id,
        ]);
        $versionB = $flow->versions()->create([
            'name' => 'B',
            'nodes' => [['id' => 'n1', 'type' => 'start', 'label' => 'B', 'data' => []]],
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chatflow.versions.diff', [$flow, $versionA->id]).'?against='.$versionB->id)
            ->assertOk();

        $this->assertSame($versionB->id, $response->viewData('against')->id);
        $diff = $response->viewData('diff');
        $this->assertSame(['old' => 'A', 'new' => 'B'], $diff['changed'][0]['fields']['label']);
    }

    public function test_diff_404s_for_version_of_another_flow(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $otherFlow = ChatFlow::factory()->create();
        $foreignVersion = $otherFlow->versions()->create([
            'name' => $otherFlow->name,
            'nodes' => [],
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('chatflow.versions.diff', [$flow, $foreignVersion->id]))
            ->assertNotFound();
    }

    public function test_diff_requires_view_permission(): void
    {
        $this->requireHelpdeskDb();
        $flow = ChatFlow::factory()->create();
        $version = $flow->versions()->create([
            'name' => $flow->name,
            'nodes' => [],
            'created_by' => $this->user->id,
        ]);
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('chatflow.versions.diff', [$flow, $version->id]))
            ->assertForbidden();
    }
}
