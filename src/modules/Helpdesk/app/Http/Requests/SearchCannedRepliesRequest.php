<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchCannedRepliesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.cannedreplies.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'La búsqueda no puede superar los 50 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'búsqueda',
        ];
    }
}
