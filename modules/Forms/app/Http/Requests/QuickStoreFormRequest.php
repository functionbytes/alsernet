<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickStoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.create');
    }

    public function rules(): array
    {
        $templates = array_keys((array) config('forms.template_library', []));

        return [
            'name' => ['required', 'string', 'max:150'],
            'template' => ['required', 'string', 'in:'.implode(',', $templates)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del formulario es obligatorio.',
            'template.in' => 'La plantilla seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'template' => 'plantilla',
        ];
    }
}
