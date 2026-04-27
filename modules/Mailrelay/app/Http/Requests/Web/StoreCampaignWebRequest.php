<?php

namespace Modules\Mailrelay\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.campaigns.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'html_content' => ['required', 'string'],
            'text_content' => ['nullable', 'string'],
            'list_id' => ['nullable', 'exists:mails_lists,id'],
            'sender_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'html_content.required' => 'El contenido HTML es obligatorio.',
            'list_id.exists' => 'La lista seleccionada no existe.',
            'scheduled_at.date' => 'La fecha de envío no es válida.',
            'scheduled_at.after' => 'La fecha de envío debe ser posterior a la fecha actual.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'html_content' => 'contenido HTML',
            'text_content' => 'contenido de texto',
            'list_id' => 'lista',
            'sender_id' => 'remitente',
            'scheduled_at' => 'fecha de envío programado',
        ];
    }
}
