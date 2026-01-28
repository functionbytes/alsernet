<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCronjobSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_name' => [
                'required',
                'string',
                'max:255',
                'unique:mailing_cronjob_settings,job_name',
            ],
            'schedule' => [
                'required',
                'string',
                'max:255',
                $this->validateCronExpression(),
            ],
            'command' => [
                'required',
                'string',
                'max:500',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'paused']),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_name.required' => 'El nombre del trabajo es obligatorio.',
            'job_name.string' => 'El nombre del trabajo debe ser una cadena de texto.',
            'job_name.max' => 'El nombre del trabajo no debe exceder 255 caracteres.',
            'job_name.unique' => 'Este nombre de trabajo ya existe.',
            'schedule.required' => 'El programa de cron es obligatorio.',
            'schedule.string' => 'El programa debe ser una cadena de texto.',
            'schedule.max' => 'El programa no debe exceder 255 caracteres.',
            'schedule.invalid_cron' => 'El formato de cron es inválido. Debe ser una expresión cron válida (ej: "0 * * * *").',
            'command.required' => 'El comando es obligatorio.',
            'command.string' => 'El comando debe ser una cadena de texto.',
            'command.max' => 'El comando no debe exceder 500 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser "activo", "inactivo" o "pausado".',
        ];
    }

    /**
     * Validate cron expression format.
     */
    protected function validateCronExpression(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! $this->isCronExpressionValid($value)) {
                $fail('schedule')->translate('schedule.invalid_cron');
            }
        };
    }

    /**
     * Check if cron expression is valid.
     */
    protected function isCronExpressionValid(string $expression): bool
    {
        $parts = explode(' ', trim($expression));

        // A valid cron expression has exactly 5 parts
        if (count($parts) !== 5) {
            return false;
        }

        // Validate each part
        $patterns = [
            '(0|[1-9][0-9]?|\\*|\\*/[0-9]+|[0-9]+(-[0-9]+)?)',  // minute (0-59)
            '(0|[1-9][0-9]?|\\*|\\*/[0-9]+|[0-9]+(-[0-9]+)?)',  // hour (0-23)
            '(0|[1-9][0-9]?|\\*|\\*/[0-9]+|[0-9]+(-[0-9]+)?)',  // day of month (1-31)
            '([1-9]|1[0-2]|\\*|\\*/[0-9]+|[0-9]+(-[0-9]+)?)',   // month (1-12)
            '([0-6]|\\*|\\*/[0-9]+|[0-9]+(-[0-9]+)?)',          // day of week (0-6)
        ];

        foreach ($parts as $index => $part) {
            if (! preg_match('/^'.$patterns[$index].'$/', $part)) {
                return false;
            }
        }

        return true;
    }
}
