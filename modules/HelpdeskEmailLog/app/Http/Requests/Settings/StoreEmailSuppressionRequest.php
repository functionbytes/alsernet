<?php

namespace Modules\HelpdeskEmailLog\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskEmailLog\Enums\SuppressionReason;

class StoreEmailSuppressionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemaillog.settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'module' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'in:'.implode(',', array_column(SuppressionReason::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
