<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSubmissionFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.submissions.edit')
            || $this->user()->can('Forms.submissions.index');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
            'file.mimes' => 'El tipo de archivo no está permitido.',
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'archivo'];
    }
}
