<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'response_time_minutes' => 'sometimes|integer|min:1',
            'resolution_time_minutes' => 'nullable|integer|min:1',
            'platform' => 'nullable|string|in:facebook,instagram,whatsapp,tiktok,x,linkedin',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'is_active' => 'boolean',
            'conditions' => 'nullable|array',
        ];
    }
}
