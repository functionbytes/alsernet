<?php

namespace Modules\HelpdeskCampaigns\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.campaigns.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:popup,banner,slide-in,full-screen'],
            'content' => ['nullable', 'array'],
            'appearance' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'type.required' => 'El tipo de campaña es obligatorio.',
            'type.in' => 'El tipo debe ser popup, banner, slide-in o full-screen.',
            'ends_at.after' => 'La fecha de fin debe ser posterior a la fecha de publicación.',
        ];
    }
}
