<?php

namespace Modules\Supplier\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.content.manage');
    }

    public function rules(): array
    {
        // Cap at 200 items per call. `regenerate` invokes paid OpenAI/Anthropic
        // calls per ID; without a ceiling a single POST could trigger thousands
        // of paid API calls and starve PHP-FPM workers.
        return [
            'action' => ['required', 'string', 'in:approve,reject,regenerate,assign,unassign,publish'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string'],
            'user_id'  => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'publicar' => ['sometimes', 'nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => 'Debe seleccionar al menos un contenido.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos un contenido.',
            'ids.max' => 'Maximo 200 contenidos por operacion bulk.',
            'ids.*.required' => 'Cada identificador es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'accion',
            'ids' => 'identificadores',
            'ids.*' => 'identificador',
        ];
    }
}
