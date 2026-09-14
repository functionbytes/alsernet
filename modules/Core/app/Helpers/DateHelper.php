<?php

use Carbon\Carbon;

/**
 * Date and Time Helpers
 *
 * Provides utility functions for date formatting and manipulation.
 */
if (! function_exists('month')) {
    /**
     * Get the month and year from a date string
     *
     * @param  string  $dates  The date string to parse
     * @return string The formatted month and year (e.g., "2025")
     */
    function month($dates): string
    {
        $date = Carbon::parse($dates);

        return ucwords($date->format('Y'));
    }
}
