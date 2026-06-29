<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'segment' => ['required', 'string', 'in:all,vip,inactive,new,recent_buyers'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'subject.required' => 'El asunto del correo es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 500 caracteres.',
            'content.required' => 'El contenido del correo es obligatorio.',
            'segment.required' => 'El segmento es obligatorio.',
            'segment.in' => 'El segmento seleccionado no es válido.',
            'scheduled_at.after' => 'La fecha de envío debe ser posterior a la fecha actual.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'content' => 'contenido',
            'segment' => 'segmento',
            'scheduled_at' => 'fecha de envío',
        ];
    }
}
