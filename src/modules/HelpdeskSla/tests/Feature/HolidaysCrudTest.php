<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskSla\Models\Holiday;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * CRUD de festivos del calendario de negocio (panel/helpdesksla/holidays).
 */
class HolidaysCrudTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Setting::set('sla.integration_enabled', '1', 'integrations');

        $this->seedHelpdeskRoles();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-settings');
    }

    protected function tearDown(): void
    {
        Setting::set('sla.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_admin_can_add_a_holiday(): void
    {
        $this->actingAs($this->admin)
            ->post(route('helpdesksla.holidays.store'), [
                'date' => '2026-12-25',
                'name' => 'Navidad',
                'is_recurring' => '1',
            ])
            ->assertRedirect(route('helpdesksla.holidays.index'));

        $this->assertDatabaseHas('helpdesk_holidays', [
            'date' => '2026-12-25',
            'name' => 'Navidad',
            'is_recurring' => 1,
        ], 'helpdesk');
    }

    public function test_name_and_date_are_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('helpdesksla.holidays.store'), ['name' => ''])
            ->assertSessionHasErrors(['date', 'name']);
    }

    public function test_admin_can_delete_a_holiday(): void
    {
        $holiday = Holiday::create(['date' => '2026-01-06', 'name' => 'Reyes', 'is_recurring' => true]);

        $this->actingAs($this->admin)
            ->delete(route('helpdesksla.holidays.destroy', $holiday))
            ->assertRedirect(route('helpdesksla.holidays.index'));

        $this->assertDatabaseMissing('helpdesk_holidays', ['id' => $holiday->id], 'helpdesk');
    }

    public function test_index_lists_configured_holidays(): void
    {
        Holiday::create(['date' => '2026-05-01', 'name' => 'Día del trabajo', 'is_recurring' => true]);

        $this->actingAs($this->admin)
            ->get(route('helpdesksla.holidays.index'))
            ->assertOk()
            ->assertSee('Día del trabajo');
    }
}
