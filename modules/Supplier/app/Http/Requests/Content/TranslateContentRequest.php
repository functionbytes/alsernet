<?php

namespace Modules\Supplier\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class TranslateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.content.manage');
    }

    public function rules(): array
    {
        return [
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['required', 'string', 'in:EN,FR,DE,IT,PT,NL'],
        ];
    }

    public function messages(): array
    {
        return [
            'languages.required' => 'Debe seleccionar al menos un idioma.',
            'languages.array' => 'Los idiomas deben ser un arreglo.',
            'languages.min' => 'Debe seleccionar al menos un idioma.',
            'languages.*.in' => 'El idioma seleccionado no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'languages' => 'idiomas',
            'languages.*' => 'idioma',
        ];
    }
}
