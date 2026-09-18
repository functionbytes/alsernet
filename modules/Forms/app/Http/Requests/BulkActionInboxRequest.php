<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.submissions.edit')
            || $this->user()->can('Forms.submissions.index');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:mark_read,mark_unread,mark_spam,unmark_spam,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'exists:form_submissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'La acción seleccionada no es válida.',
            'ids.required' => 'Debes seleccionar al menos un envío.',
            'ids.max' => 'No puedes procesar más de 1000 envíos a la vez.',
        ];
    }
}
