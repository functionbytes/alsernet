<?php

namespace Modules\Analytics\Exceptions;

use DateTimeInterface;
use Exception;

class InvalidPeriod extends Exception
{
    public static function startDateCannotBeAfterEndDate(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): self {
        return new self(
            sprintf(
                'Start date (%s) cannot be after end date (%s).',
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            )
        );
    }
}
