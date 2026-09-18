<?php

namespace Modules\Helpdesk\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\Helpdesk\Services\BusinessHoursService;
use Modules\HelpdeskSla\Models\Holiday;
use Tests\TestCase;

/**
 * BusinessHoursService::isOpenNow() delega los festivos en el calendario de
 * HelpdeskSla (dependencia blanda, ver docblock del servicio). El horario del
 * día evaluado se fija a mano para no depender de lo que haya configurado en
 * la tabla real compartida.
 */
class BusinessHoursServiceHolidaysTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('helpdesk:business_hours_open');

        // Miércoles 12:00 UTC: dentro del horario habitual 09:00-18:00.
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

        BusinessHour::query()->updateOrCreate(
            ['day_of_week' => Carbon::now()->dayOfWeek],
            [
                'is_open' => true,
                'opens_at' => '09:00:00',
                'closes_at' => '18:00:00',
                'timezone' => 'UTC',
            ]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_open_within_hours_when_no_holidays_configured(): void
    {
        $this->assertTrue(app(BusinessHoursService::class)->isOpenNow());
    }

    public function test_closed_on_a_holiday_even_within_normal_hours(): void
    {
        Holiday::create(['date' => '2026-09-16', 'name' => 'Festivo de prueba', 'is_recurring' => false]);

        $this->assertFalse(app(BusinessHoursService::class)->isOpenNow());
    }
}
