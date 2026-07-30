<?php

namespace Modules\HelpdeskCampaigns\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.campaigns.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['sometimes', 'in:popup,banner,slide-in,full-screen'],
            'content' => ['nullable', 'array'],
            'appearance' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
