<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Models\BulkImport;

class StoreBulkImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BulkImport::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:chat_accounts,id'],
            'user_id' => ['required', 'exists:users,id'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // 5MB max
            'status' => ['required', 'string', 'in:pending,processing,completed,failed'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo CSV',
            'file.mimes' => 'El archivo debe ser de tipo CSV',
            'file.max' => 'El archivo no puede superar los 5MB',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);
    }
}
