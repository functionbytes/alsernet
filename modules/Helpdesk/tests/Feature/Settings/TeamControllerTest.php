<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateTeamMemberRequest;
use Modules\Helpdesk\Models\AgentSettings;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * B2-team regression:
 *   T0.20 — assignment_limit↔max_concurrent_conversations mismatch;
 *            working_hours not persisted at top-level;
 *            role field allowed privilege escalation to non-team roles.
 */
class TeamControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $superRole = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);

        // Actor with super-settings role bypasses all permission checks via Gate::before
        $this->admin = User::factory()->create();
        $this->admin->assignRole($superRole);

        // Target member to be updated
        $this->member = User::factory()->create();
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    /**
     * T0.20-fix: max_concurrent_conversations must be persisted on the
     * helpdesk_agent_settings row (not a missing "assignment_limit" column).
     */
    public function test_update_persists_max_concurrent_conversations(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 7,
            ])
            ->assertRedirect(route('settings.helpdesk.team.members'));

        $this->assertDatabaseHas('helpdesk_agent_settings', [
            'user_id' => $this->member->id,
            'max_concurrent_conversations' => 7,
        ], 'helpdesk');
    }

    /**
     * T0.20-fix: working_hours must be persisted as JSON at the top-level
     * helpdesk_agent_settings row (not lost or nested under another key).
     */
    public function test_update_persists_working_hours(): void
    {
        $workingHours = [
            'monday' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            'saturday' => ['enabled' => false, 'start' => '09:00', 'end' => '13:00'],
        ];

        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'working_hours',
                'max_concurrent_conversations' => 0,
                'working_hours' => $workingHours,
            ])
            ->assertRedirect(route('settings.helpdesk.team.members'));

        $settings = AgentSettings::where('user_id', $this->member->id)->first();
        $this->assertNotNull($settings, 'AgentSettings row was not created');
        $this->assertSame('working_hours', $settings->accepts_conversations);

        $stored = $settings->working_hours;
        $this->assertIsArray($stored);
        $this->assertTrue($stored['monday']['enabled'] ?? false);
        $this->assertSame('09:00', $stored['monday']['start'] ?? null);
        $this->assertSame('17:00', $stored['monday']['end'] ?? null);
        $this->assertFalse($stored['saturday']['enabled'] ?? true);
    }

    /**
     * Updating both limit and working_hours in a single request.
     */
    public function test_update_persists_limit_and_working_hours_together(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => 'Ana',
                'lastname' => 'Garcia',
                'email' => $this->member->email,
                'accepts_conversations' => 'working_hours',
                'max_concurrent_conversations' => 5,
                'working_hours' => [
                    'friday' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'],
                ],
            ])
            ->assertRedirect();

        $settings = AgentSettings::where('user_id', $this->member->id)->first();
        $this->assertSame(5, $settings->max_concurrent_conversations);
        $this->assertNotNull($settings->working_hours);
    }

    // ── Authorization ──────────────────────────────────────────────────────────

    /**
     * A user without helpdesk.settings.update must receive 403.
     */
    public function test_update_returns_403_without_permission(): void
    {
        $unprivileged = User::factory()->create();

        $this->actingAs($unprivileged)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
            ])
            ->assertForbidden();
    }

    /**
     * An unauthenticated request must be redirected to login.
     */
    public function test_update_redirects_guests_to_login(): void
    {
        $this->put(route('settings.helpdesk.team.member.update', $this->member->id), [])
            ->assertRedirect(route('auth.login'));
    }

    // ── Security: role privilege escalation ────────────────────────────────────

    /**
     * T0.20-fix (security): The role field must be restricted to team roles only.
     * Attempting to assign a non-team role (e.g. 'super-admin') must be rejected
     * with a validation error (422), not persisted.
     *
     * Without the fix (Rule::in(ASSIGNABLE_ROLES) absent), any role name would be
     * accepted and syncRoles() would grant arbitrary privileges.
     */
    public function test_assigning_non_team_role_is_rejected_with_422(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
                'role' => 'super-admin', // not in ASSIGNABLE_ROLES
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('role');

        // Member must NOT have had their role changed to super-admin
        $this->assertFalse(
            $this->member->fresh()->hasRole('super-admin'),
            'super-admin role was improperly assigned through the team member update form'
        );
    }

    /**
     * The 'root' role (another privileged non-team role) is also blocked.
     */
    public function test_assigning_root_role_is_rejected(): void
    {
        Role::firstOrCreate(['name' => 'root', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
                'role' => 'root',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('role');

        $this->assertFalse($this->member->fresh()->hasRole('root'));
    }

    /**
     * A valid team role (e.g. 'manager') IS accepted and persisted.
     */
    public function test_assigning_valid_team_role_is_accepted(): void
    {
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
                'role' => 'manager',
            ])
            ->assertRedirect(route('settings.helpdesk.team.members'));

        $this->assertTrue($this->member->fresh()->hasRole('manager'));
    }

    // ── ASSIGNABLE_ROLES constant ──────────────────────────────────────────────

    /**
     * Verify the whitelist constant itself only contains the expected team roles,
     * ensuring no privileged roles slipped in during future edits.
     */
    public function test_assignable_roles_constant_contains_only_team_roles(): void
    {
        $expected = ['admin', 'manager', 'support', 'callcenter'];
        $actual = UpdateTeamMemberRequest::ASSIGNABLE_ROLES;

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);

        // Privileged roles must not be present
        foreach (['super-admin', 'root', 'owner', 'god'] as $privileged) {
            $this->assertNotContains(
                $privileged,
                $actual,
                "Privileged role '{$privileged}' must not appear in ASSIGNABLE_ROLES"
            );
        }
    }

    // ── Validation edge cases ──────────────────────────────────────────────────

    /**
     * max_concurrent_conversations = 0 (unlimited) must be accepted.
     */
    public function test_zero_limit_means_unlimited_and_is_persisted(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_agent_settings', [
            'user_id' => $this->member->id,
            'max_concurrent_conversations' => 0,
        ], 'helpdesk');
    }

    /**
     * A negative limit must be rejected as invalid.
     */
    public function test_negative_limit_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'yes',
                'max_concurrent_conversations' => -1,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('max_concurrent_conversations');
    }

    /**
     * accepts_conversations must be one of the allowed values.
     */
    public function test_invalid_accepts_conversations_value_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.helpdesk.team.member.update', $this->member->id), [
                'firstname' => $this->member->firstname,
                'lastname' => $this->member->lastname,
                'email' => $this->member->email,
                'accepts_conversations' => 'maybe',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('accepts_conversations');
    }
}
