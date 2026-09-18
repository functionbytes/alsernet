<?php

namespace Modules\HelpdeskLivechat\Tests\Unit;

use Carbon\Carbon;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use PHPUnit\Framework\TestCase;

class BusinessHoursTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_returns_true_when_business_hours_disabled(): void
    {
        $web = new Web;
        $web->business_hours = ['enabled' => false];

        $this->assertTrue($web->isWithinBusinessHours());
    }

    public function test_returns_true_when_business_hours_empty(): void
    {
        $web = new Web;
        $web->business_hours = [];

        $this->assertTrue($web->isWithinBusinessHours());
    }

    public function test_returns_true_when_within_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-05 10:00:00', 'UTC'));

        $web = new Web;
        $web->business_hours = [
            'enabled' => true,
            'timezone' => 'UTC',
            'schedule' => [
                'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
            ],
        ];

        $this->assertTrue($web->isWithinBusinessHours());
    }

    public function test_returns_false_when_day_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-09 10:00:00', 'UTC'));

        $web = new Web;
        $web->business_hours = [
            'enabled' => true,
            'timezone' => 'UTC',
            'schedule' => [
                'saturday' => ['enabled' => false, 'start' => '09:00', 'end' => '18:00'],
            ],
        ];

        $this->assertFalse($web->isWithinBusinessHours());
    }

    public function test_returns_false_when_before_start_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-05 08:00:00', 'UTC'));

        $web = new Web;
        $web->business_hours = [
            'enabled' => true,
            'timezone' => 'UTC',
            'schedule' => [
                'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
            ],
        ];

        $this->assertFalse($web->isWithinBusinessHours());
    }

    public function test_returns_false_when_after_end_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-05 19:00:00', 'UTC'));

        $web = new Web;
        $web->business_hours = [
            'enabled' => true,
            'timezone' => 'UTC',
            'schedule' => [
                'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
            ],
        ];

        $this->assertFalse($web->isWithinBusinessHours());
    }

    public function test_respects_configured_timezone(): void
    {
        // 15:00 UTC === 09:00 America/Mexico_City (CST, UTC-6 — Mexico abolished DST in 2022)
        Carbon::setTestNow(Carbon::parse('2026-05-05 15:00:00', 'UTC'));

        $web = new Web;
        $web->business_hours = [
            'enabled' => true,
            'timezone' => 'America/Mexico_City',
            'schedule' => [
                'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
            ],
        ];

        $this->assertTrue($web->isWithinBusinessHours());
    }
}
