<?php

namespace Modules\Analytics;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Period
{
    public function __construct(public CarbonInterface $startDate, public CarbonInterface $endDate)
    {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }
    }

    public static function create(CarbonInterface $startDate, CarbonInterface $endDate): self
    {
        return new self($startDate, $endDate);
    }

    public static function days(int $numberOfDays): self
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($numberOfDays)->startOfDay();

        return new self($startDate, $endDate);
    }

    public static function months(int $numberOfMonths): self
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subMonths($numberOfMonths)->startOfDay();

        return new self($startDate, $endDate);
    }

    public static function years(int $numberOfYears): self
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subYears($numberOfYears)->startOfDay();

        return new self($startDate, $endDate);
    }

    public static function last7Days(): self
    {
        return self::days(7);
    }

    public static function last30Days(): self
    {
        return self::days(30);
    }

    public static function thisMonth(): self
    {
        $startDate = Carbon::today()->startOfMonth();
        $endDate = Carbon::today();

        return new self($startDate, $endDate);
    }

    public static function lastMonth(): self
    {
        $startDate = Carbon::today()->subMonth()->startOfMonth();
        $endDate = Carbon::today()->subMonth()->endOfMonth();

        return new self($startDate, $endDate);
    }

    public static function thisYear(): self
    {
        $startDate = Carbon::today()->startOfYear();
        $endDate = Carbon::today();

        return new self($startDate, $endDate);
    }
}
