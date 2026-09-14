<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.update');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['delete', 'restore', 'move', 'favorite', 'unfavorite'])],
            'type' => ['required', 'string', Rule::in(['file', 'folder'])],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'type.required' => 'El tipo de elemento es obligatorio.',
            'type.in' => 'El tipo de elemento no es válido.',
            'ids.required' => 'Debe seleccionar al menos un elemento.',
            'ids.min' => 'Debe seleccionar al menos un elemento.',
            'ids.max' => 'No puede seleccionar más de 500 elementos a la vez.',
            'ids.*.integer' => 'Los identificadores deben ser números enteros.',
            'folder_id.exists' => 'La carpeta de destino no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'type' => 'tipo',
            'ids' => 'elementos',
            'folder_id' => 'carpeta destino',
        ];
    }
}
