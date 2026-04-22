<?php

namespace Modules\HelpdeskCampaigns\Http\Requests;

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
            'status' => ['nullable', 'string', 'in:draft,scheduled,active,ended,paused'],
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
            'status' => 'estado',
            'published_at' => 'fecha de publicación',
            'ends_at' => 'fecha de fin',
        ];
    }
}
