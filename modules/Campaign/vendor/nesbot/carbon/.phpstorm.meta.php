<?php

namespace PHPSTORM_META {
    use Carbon\CarbonInterface;
    use Carbon\Traits\Units;

    registerArgumentsSet('date_units', 'millenania', 'millennium', 'century', 'centuries', 'decade', 'decades', 'year', 'years', 'y', 'yr', 'yrs', 'quarter', 'quarters', 'month', 'months', 'mo', 'mos', 'week', 'weeks', 'w', 'day', 'days', 'd', 'hour', 'hours', 'h', 'minute', 'minutes', 'm', 'second', 'seconds', 's', 'millisecond', 'milliseconds', 'milli', 'ms', 'microsecond', 'microseconds', 'micro', 'µs');
    expectedArguments(Units::add(), 0, argumentsSet('date_units'));
    expectedArguments(Units::add(), 1, argumentsSet('date_units'));
    expectedArguments(CarbonInterface::add(), 0, argumentsSet('date_units'));
    expectedArguments(CarbonInterface::add(), 1, argumentsSet('date_units'));

    expectedArguments(CarbonInterface::getTimeFormatByPrecision(), 0, 'minute', 'second', 'm', 'millisecond', 'µ', 'microsecond', 'minutes', 'seconds', 'ms', 'milliseconds', 'µs', 'microseconds');
}
