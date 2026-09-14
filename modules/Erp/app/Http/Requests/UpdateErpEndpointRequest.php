<?php

namespace Modules\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Erp\Support\ErpEndpointUrlGuard;

class UpdateErpEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('erp.endpoints.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:erp_endpoints,slug,'.$this->endpoint->id,
            'url' => ['required', 'url', 'max:500', function ($attribute, $value, $fail) {
                if (! ErpEndpointUrlGuard::isAllowed($value)) {
                    $fail('La URL del endpoint no está permitida (localhost, metadata de nube, o esquema no soportado).');
                }
            }],
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'credential_id' => 'nullable|exists:erp_credentials,id',
            'description' => 'nullable|string|max:1000',
            'timeout' => 'nullable|integer|min:1|max:300',
            'retry_attempts' => 'nullable|integer|min:0|max:10',
            'content_type' => 'nullable|string|max:100',
            'rate_limit' => 'nullable|integer|min:1',
            'headers' => 'nullable|array',
            'headers.*' => 'string|max:500',
            'query_params' => 'nullable|array',
            'query_params.*' => 'string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del endpoint es requerido',
            'url.required' => 'La URL del endpoint es requerida',
            'url.url' => 'La URL debe ser válida',
            'method.required' => 'El método HTTP es requerido',
        ];
    }
}
