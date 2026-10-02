<?php

namespace Modules\HelpdeskSla\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskSla\Services\ACorunaHolidayCalendar;

class ImportHolidaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesksla.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'in:'.implode(',', ACorunaHolidayCalendar::availableYears())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.in' => 'Solo hay calendario verificado para: '.implode(', ', ACorunaHolidayCalendar::availableYears()).'.',
        ];
    }
}
