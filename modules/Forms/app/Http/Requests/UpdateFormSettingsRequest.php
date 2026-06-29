<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'success_message' => ['nullable', 'string'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'submit_button_text' => ['nullable', 'string', 'max:100'],
            'allow_multiple' => ['boolean'],
            'max_submissions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_multi_step' => ['boolean'],
            'floating_label' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'success_message' => 'mensaje de éxito',
            'redirect_url' => 'URL de redirección',
            'submit_button_text' => 'texto del botón',
            'max_submissions' => 'máximo de envíos',
            'expires_at' => 'fecha de expiración',
        ];
    }
}
