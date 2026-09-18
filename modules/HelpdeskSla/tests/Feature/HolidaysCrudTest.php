<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskSla\Models\Holiday;
use Modules\HelpdeskSla\Services\ACorunaHolidayCalendar;
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
        Carbon::setTestNow();

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

    public function test_admin_can_import_the_a_coruna_official_calendar_for_a_year(): void
    {
        $this->actingAs($this->admin)
            ->post(route('helpdesksla.holidays.import'), ['year' => 2026])
            ->assertRedirect(route('helpdesksla.holidays.index'));

        $this->assertDatabaseHas('helpdesk_holidays', [
            'date' => '2026-02-17',
            'name' => 'Martes de Carnaval (festivo local A Coruña)',
        ], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_holidays', [
            'date' => '2026-10-07',
            'name' => 'Virgen del Rosario (festivo local A Coruña)',
        ], 'helpdesk');
    }

    public function test_import_does_not_duplicate_a_day_already_covered_by_a_recurring_holiday(): void
    {
        // La BD compartida ya trae un 24-jun puntual real (sembrado por el propio
        // comando de sincronización) — lo quitamos dentro de la transacción del test
        // para partir de un estado conocido, sin tocar el dato real fuera de aquí.
        Holiday::where('date', '2026-06-24')->delete();

        // Fecha de año distinto a propósito: is_recurring compara solo mes-día, así
        // que esto ya "cubre" cualquier 24-jun futuro sin que import() cree un
        // segundo registro puntual para 2026-06-24.
        Holiday::create(['date' => '2020-06-24', 'name' => 'San Xoán (recurrente ya configurado)', 'is_recurring' => true]);

        $this->actingAs($this->admin)->post(route('helpdesksla.holidays.import'), ['year' => 2026]);

        // Exacta a 2026-06-24: no confundir con el 24-jun de OTRO año real ya
        // sembrado en la BD compartida (mismo mes-día, fecha distinta, irrelevante
        // para lo que se está comprobando aquí).
        $this->assertDatabaseMissing('helpdesk_holidays', ['date' => '2026-06-24'], 'helpdesk');
    }

    public function test_import_rejects_a_year_with_no_verified_calendar(): void
    {
        $this->actingAs($this->admin)
            ->post(route('helpdesksla.holidays.import'), ['year' => 2030])
            ->assertSessionHasErrors('year');

        $this->assertDatabaseMissing('helpdesk_holidays', ['date' => '2030-01-01'], 'helpdesk');
    }

    public function test_import_is_idempotent_when_run_twice(): void
    {
        $this->actingAs($this->admin)->post(route('helpdesksla.holidays.import'), ['year' => 2027]);
        $this->actingAs($this->admin)->post(route('helpdesksla.holidays.import'), ['year' => 2027]);

        $this->assertSame(1, Holiday::query()->where('date', '2027-06-24')->count());
    }

    public function test_index_warns_when_next_verified_year_is_not_synced_yet(): void
    {
        [$currentYear, $nextYear] = [now()->year, now()->year + 1];

        if (! in_array($nextYear, ACorunaHolidayCalendar::availableYears(), true) || $currentYear >= max(ACorunaHolidayCalendar::availableYears())) {
            $this->markTestSkipped('Necesita que el año siguiente al actual esté verificado en ACorunaHolidayCalendar sin ser el último disponible.');
        }

        // Puntual a propósito: no toca los recurrentes (Año Nuevo, etc.), que ya
        // "cubren" su mes-día en cualquier año y no deben contar como pendiente.
        Holiday::where('date', 'like', "{$nextYear}-%")->where('is_recurring', false)->delete();

        $this->actingAs($this->admin)
            ->get(route('helpdesksla.holidays.index'))
            ->assertOk()
            ->assertSee((string) $nextYear)
            ->assertSee('sincronízalo');
    }

    public function test_index_warns_to_research_the_following_year_once_in_the_last_verified_year(): void
    {
        $maxYear = max(ACorunaHolidayCalendar::availableYears());
        Carbon::setTestNow(Carbon::parse("{$maxYear}-06-01"));

        $this->actingAs($this->admin)
            ->get(route('helpdesksla.holidays.index'))
            ->assertOk()
            ->assertSee((string) ($maxYear + 1))
            ->assertSee('ACorunaHolidayCalendar');
    }
}
