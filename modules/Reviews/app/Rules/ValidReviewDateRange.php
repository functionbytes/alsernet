<?php

namespace Modules\Reviews\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

/**
 * Validates that a date range is reasonable and properly formatted.
 *
 * Rules:
 * - from_date must be before to_date
 * - Maximum range is 2 years (730 days)
 * - Both dates must be valid dates
 *
 * Used in export/report forms to prevent excessive data queries.
 */
class ValidReviewDateRange implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param  string|null  $fromDateField  The field name containing the start date
     * @param  int  $maxYears  Maximum years allowed in range (default: 2)
     */
    public function __construct(
        private readonly ?string $fromDateField = 'from_date',
        private readonly int $maxYears = 2
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute name (to_date)
     * @param  mixed  $value  The to_date value
     */
    public function passes($attribute, $value): bool
    {
        // Get from_date from request
        $fromDate = request($this->fromDateField);

        if (! $fromDate || ! $value) {
            return true; // Let required rule handle missing values
        }

        try {
            $from = Carbon::parse($fromDate);
            $to = Carbon::parse($value);

            // Check if from is before to
            if ($from->greaterThanOrEqualTo($to)) {
                return false;
            }

            // Check if range exceeds max years
            $maxDays = $this->maxYears * 365;
            if ($from->diffInDays($to) > $maxDays) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false; // Invalid date format
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "El rango de fechas debe ser válido (fecha inicial antes de fecha final) y no exceder {$this->maxYears} años";
    }
}
