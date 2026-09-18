<?php

namespace Modules\Supplier\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class BulkApproveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.content.manage');
    }

    public function rules(): array
    {
        return [
            'content_uids' => ['required', 'array'],
            'content_uids.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'content_uids.required' => 'Debe seleccionar al menos un contenido.',
            'content_uids.array' => 'Los identificadores deben ser un arreglo.',
            'content_uids.*.required' => 'Cada identificador es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'content_uids' => 'identificadores de contenido',
            'content_uids.*' => 'identificador',
        ];
    }
}
