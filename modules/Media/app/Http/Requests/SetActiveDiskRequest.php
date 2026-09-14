<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetActiveDiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.view');
    }

    public function rules(): array
    {
        return [
            'disk' => ['required', 'string', Rule::in(['media', 'public', 's3'])],
        ];
    }

    public function messages(): array
    {
        return [
            'disk.required' => 'El disco es obligatorio.',
            'disk.in' => 'El disco seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'disk' => 'disco',
        ];
    }
}
