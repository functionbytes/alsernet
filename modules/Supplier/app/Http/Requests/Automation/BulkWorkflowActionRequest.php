<?php

namespace Modules\Supplier\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

class BulkWorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.view.automation') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:delete,enable,disable',
            'uids' => 'required|array|min:1',
            'uids.*' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'uids.required' => 'Debe seleccionar al menos un workflow.',
            'uids.min' => 'Debe seleccionar al menos un workflow.',
        ];
    }
}
