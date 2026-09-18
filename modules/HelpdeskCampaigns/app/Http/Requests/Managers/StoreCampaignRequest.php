<?php

namespace Modules\HelpdeskCampaigns\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.campaigns.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:popup,banner,slide-in,full-screen'],
            'template_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_campaign_templates,id'],
            'content' => ['nullable', 'array'],
            'appearance' => ['nullable', 'array'],
            'conditions' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:published_at'],
            'max_impressions_per_user' => ['nullable', 'integer', 'min:0'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:0'],
            'goal_type' => ['nullable', 'in:impressions,clicks'],
            'goal_value' => ['nullable', 'integer', 'min:0'],
            'approval_required' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'approval_required' => $this->boolean('approval_required'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'type.required' => 'El tipo de campaña es obligatorio.',
            'type.in' => 'El tipo debe ser: popup, banner, slide-in o pantalla completa.',
            'ends_at.after' => 'La fecha de fin debe ser posterior a la fecha de publicación.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'type' => 'tipo',
            'template_id' => 'plantilla',
            'published_at' => 'fecha de publicación',
            'ends_at' => 'fecha de fin',
        ];
    }
}
