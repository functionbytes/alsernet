<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteSubmissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.submissions.delete');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'exists:form_submissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debes seleccionar al menos un envío.',
            'ids.max' => 'No puedes eliminar más de 1000 envíos a la vez.',
            'ids.*.exists' => 'Uno o más envíos seleccionados ya no existen.',
        ];
    }
}
