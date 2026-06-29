<?php

namespace Modules\Attention\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionDepartment;
use Tests\TestCase;

class AttentionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected User $assignedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-settings');

        $this->regularUser = User::factory()->create();
        $this->assignedUser = User::factory()->create();
    }

    /** @test */
    public function super_admin_can_view_any_attention()
    {
        $this->assertTrue($this->adminUser->can('viewAny', Attention::class));
    }

    /** @test */
    public function assigned_user_can_view_their_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
        ]);

        $this->assertTrue($this->assignedUser->can('view', $attention));
    }

    /** @test */
    public function regular_user_cannot_view_others_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
        ]);

        $this->assertFalse($this->regularUser->can('view', $attention));
    }

    /** @test */
    public function assigned_user_can_update_their_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        $this->assertTrue($this->assignedUser->can('update', $attention));
    }

    /** @test */
    public function user_cannot_update_closed_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
            'status' => AttentionStatus::CLOSED,
        ]);

        $this->assertFalse($this->assignedUser->can('update', $attention));
    }

    /** @test */
    public function super_admin_can_delete_attention()
    {
        $attention = Attention::factory()->create();

        $this->assertTrue($this->adminUser->can('delete', $attention));
    }

    /** @test */
    public function regular_user_cannot_delete_attention()
    {
        $attention = Attention::factory()->create();

        $this->assertFalse($this->regularUser->can('delete', $attention));
    }

    /** @test */
    public function assigned_user_can_manage_their_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        $this->assertTrue($this->assignedUser->can('manage', $attention));
    }

    /** @test */
    public function department_user_can_view_department_attention()
    {
        $department = AttentionDepartment::factory()->create();
        $department->users()->attach($this->regularUser->id);

        $attention = Attention::factory()->create([
            'department_id' => $department->id,
        ]);

        $this->assertTrue($this->regularUser->can('view', $attention));
    }

    /** @test */
    public function user_can_resolve_assigned_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        $this->assertTrue($this->assignedUser->can('resolve', $attention));
    }

    /** @test */
    public function user_cannot_resolve_unassigned_attention()
    {
        $attention = Attention::factory()->create([
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        $this->assertFalse($this->regularUser->can('resolve', $attention));
    }

    /** @test */
    public function user_can_close_resolved_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
            'status' => AttentionStatus::RESOLVED,
        ]);

        $this->assertTrue($this->assignedUser->can('close', $attention));
    }

    /** @test */
    public function user_cannot_close_non_resolved_attention()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->assignedUser->id,
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        $this->assertFalse($this->assignedUser->can('close', $attention));
    }
}
