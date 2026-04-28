<?php

declare(strict_types=1);

namespace Carbon\Doctrine;

class DateTimeDefaultPrecision
{
    private static $precision = 6;

    /**
     * Change the default Doctrine datetime and datetime_immutable precision.
     */
    public static function set(int $precision): void
    {
        self::$precision = $precision;
    }

    /**
     * Get the default Doctrine datetime and datetime_immutable precision.
     */
    public static function get(): int
    {
        return self::$precision;
    }
}
