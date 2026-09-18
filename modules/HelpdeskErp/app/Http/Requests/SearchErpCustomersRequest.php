<?php

namespace Modules\HelpdeskErp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchErpCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskerp.prospect.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Defensive default: an unrecognized type must not silently fall into
        // the manager's fuzzy-match branch, which lacks the anti-enumeration
        // guarantees ('email' requires an exact match) the rest of this
        // prospect-search endpoint relies on.
        if (! in_array($this->input('type'), ['email', 'phone', 'nif'], true)) {
            $this->merge(['type' => 'email']);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['email', 'phone', 'nif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'q.string' => 'El término de búsqueda debe ser texto.',
            'type.in' => 'El tipo de búsqueda debe ser email, phone o nif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'término de búsqueda',
            'type' => 'tipo de búsqueda',
        ];
    }
}
