<?php

namespace Modules\Supplier\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Supplier\Rules\PublicUrl;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.view.automation') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'workflow_type' => 'required|in:extraction,scraping,ftp_sync,content_generation,validation,publication,monitoring',
            'external_workflow_id' => 'nullable|string|max:255',
            'webhook_url' => ['nullable', 'url:http,https', 'max:500', new PublicUrl],
            'callback_url' => ['nullable', 'url:http,https', 'max:500', new PublicUrl],
            'timeout_seconds' => 'nullable|integer|min:0',
            'max_retries' => 'nullable|integer|min:0|max:10',
            'priority' => 'nullable|integer|min:1|max:10',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del workflow es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'workflow_type.required' => 'El tipo de workflow es obligatorio.',
            'workflow_type.in' => 'El tipo de workflow seleccionado no es válido.',
            'webhook_url.url' => 'La URL del webhook no es válida.',
            'callback_url.url' => 'La URL de callback no es válida.',
            'max_retries.max' => 'El número máximo de reintentos no puede ser mayor a 10.',
            'priority.min' => 'La prioridad mínima es 1.',
            'priority.max' => 'La prioridad máxima es 10.',
        ];
    }
}
