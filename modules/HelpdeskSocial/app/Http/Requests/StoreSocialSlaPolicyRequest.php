<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.manage-rules') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'nullable|integer|min:1',
            'platform' => 'nullable|string|in:facebook,instagram,whatsapp,tiktok,x,linkedin',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'response_time_minutes.required' => 'El tiempo de respuesta es obligatorio.',
        ];
    }
}
