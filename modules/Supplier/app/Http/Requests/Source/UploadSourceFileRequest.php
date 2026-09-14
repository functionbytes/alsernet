<?php

namespace Modules\Supplier\Http\Requests\Source;

use Illuminate\Foundation\Http\FormRequest;

class UploadSourceFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sources.manage');
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimes:pdf,xlsx,xls,docx,doc,csv,txt,jpg,jpeg,png,webp',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debe seleccionar un archivo.',
            'file.file' => 'El archivo no es valido.',
            'file.max' => 'El archivo no puede superar los 50 MB.',
            'file.mimes' => 'Tipo de archivo no permitido. Formatos aceptados: pdf, xlsx, xls, docx, doc, csv, txt, jpg, jpeg, png, webp.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
        ];
    }
}
