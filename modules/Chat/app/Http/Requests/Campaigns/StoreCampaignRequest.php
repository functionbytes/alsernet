<?php

namespace Modules\Chat\Http\Requests\Campaigns;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:popup,banner,slide-in,full-screen',
            'status' => 'nullable|in:draft,scheduled,active,ended,paused',
            'content' => 'nullable|array',
            'appearance' => 'nullable|array',
            'conditions' => 'nullable|array',
            'published_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:published_at',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'type.required' => 'El tipo de campaña es requerido.',
            'type.in' => 'El tipo de campaña debe ser: popup, banner, slide-in o full-screen.',
            'status.in' => 'El estado debe ser: draft, scheduled, active, ended o paused.',
            'content.array' => 'El contenido debe ser un array.',
            'appearance.array' => 'La apariencia debe ser un array.',
            'conditions.array' => 'Las condiciones deben ser un array.',
            'published_at.date' => 'La fecha de publicación debe ser una fecha válida.',
            'ends_at.date' => 'La fecha de finalización debe ser una fecha válida.',
            'ends_at.after' => 'La fecha de finalización debe ser posterior a la fecha de publicación.',
        ];
    }
}
