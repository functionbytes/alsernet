<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ImportChatDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Alineado con el middleware de la ruta: importar archivos MUTA el
        // expediente, así que exige el permiso de gestión (antes chequeaba
        // solo conversations.view, más débil que la propia ruta).
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            // Categoría por archivo, mapeada por URL (fallback a `category`
            // cuando un archivo no tiene entrada propia) — permite que una
            // subida con varios archivos rellene varios tipos de documento
            // requeridos distintos en una sola acción.
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:120'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file_ids.required' => 'Selecciona al menos un archivo.',
            'file_ids.array' => 'El formato de los archivos seleccionados no es válido.',
            'file_ids.min' => 'Selecciona al menos un archivo.',
            'file_ids.*.required' => 'Cada archivo seleccionado debe ser válido.',
            'file_ids.*.string' => 'Cada archivo seleccionado debe ser válido.',
            'category.max' => 'La categoría no puede superar los 120 caracteres.',
            'categories.*.max' => 'La categoría no puede superar los 120 caracteres.',
            'document_id.exists' => 'El expediente seleccionado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file_ids' => 'archivos',
            'category' => 'categoría',
            'categories' => 'categorías por archivo',
            'document_id' => 'expediente',
        ];
    }
}
