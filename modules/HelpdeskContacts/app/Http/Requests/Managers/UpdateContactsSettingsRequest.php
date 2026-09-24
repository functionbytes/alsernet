<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskContacts\Support\ContactLayouts;

class UpdateContactsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.settings.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'detail_layout' => ['required', 'string', Rule::in(ContactLayouts::keys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'detail_layout.required' => 'Elige un estilo para la ficha.',
            'detail_layout.in' => 'El estilo elegido no existe.',
        ];
    }
}
