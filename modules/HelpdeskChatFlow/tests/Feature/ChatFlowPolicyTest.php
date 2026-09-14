<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Policies\ChatFlowPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ChatFlowPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }

    private function policy(): ChatFlowPolicy
    {
        return new ChatFlowPolicy;
    }

    private function makeChatFlow(): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->setRawAttributes(['id' => 1, 'name' => 'Test', 'status' => 'active']);

        return $flow;
    }

    // ─── viewAny ───────────────────────────────────────────────────────────────

    public function test_user_with_view_permission_can_view_any(): void
    {
        $user = $this->userWith('chatflow.view');

        $this->assertTrue($this->policy()->viewAny($user));
    }

    public function test_user_without_view_permission_cannot_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy()->viewAny($user));
    }

    // ─── view ──────────────────────────────────────────────────────────────────

    public function test_user_with_view_permission_can_view_single_flow(): void
    {
        $user = $this->userWith('chatflow.view');
        $flow = $this->makeChatFlow();

        $this->assertTrue($this->policy()->view($user, $flow));
    }

    public function test_user_without_view_permission_cannot_view_single_flow(): void
    {
        $user = User::factory()->create();
        $flow = $this->makeChatFlow();

        $this->assertFalse($this->policy()->view($user, $flow));
    }

    // ─── create ────────────────────────────────────────────────────────────────

    public function test_user_with_create_permission_can_create(): void
    {
        $user = $this->userWith('chatflow.create');

        $this->assertTrue($this->policy()->create($user));
    }

    public function test_user_without_create_permission_cannot_create(): void
    {
        $user = $this->userWith('chatflow.view');

        $this->assertFalse($this->policy()->create($user));
    }

    // ─── update ────────────────────────────────────────────────────────────────

    public function test_user_with_update_permission_can_update(): void
    {
        $user = $this->userWith('chatflow.update');
        $flow = $this->makeChatFlow();

        $this->assertTrue($this->policy()->update($user, $flow));
    }

    public function test_user_without_update_permission_cannot_update(): void
    {
        $user = $this->userWith('chatflow.view');
        $flow = $this->makeChatFlow();

        $this->assertFalse($this->policy()->update($user, $flow));
    }

    // ─── delete ────────────────────────────────────────────────────────────────

    public function test_user_with_delete_permission_can_delete(): void
    {
        $user = $this->userWith('chatflow.delete');
        $flow = $this->makeChatFlow();

        $this->assertTrue($this->policy()->delete($user, $flow));
    }

    public function test_user_without_delete_permission_cannot_delete(): void
    {
        $user = $this->userWith('chatflow.view');
        $flow = $this->makeChatFlow();

        $this->assertFalse($this->policy()->delete($user, $flow));
    }

    // ─── manage ────────────────────────────────────────────────────────────────

    public function test_user_with_manage_permission_can_manage(): void
    {
        $user = $this->userWith('chatflow.manage');

        $this->assertTrue($this->policy()->manage($user));
    }

    public function test_user_without_manage_permission_cannot_manage(): void
    {
        $user = $this->userWith('chatflow.view', 'chatflow.create', 'chatflow.update', 'chatflow.delete');

        $this->assertFalse($this->policy()->manage($user));
    }

    // ─── gate integration ──────────────────────────────────────────────────────

    public function test_user_can_gate_check_reflects_policy(): void
    {
        $user = $this->userWith('chatflow.view');

        $this->actingAs($user);

        $this->assertTrue($user->can('viewAny', ChatFlow::class));
    }

    public function test_user_without_permission_fails_gate_check(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', ChatFlow::class));
    }
}
