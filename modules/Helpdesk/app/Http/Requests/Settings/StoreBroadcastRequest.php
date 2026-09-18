<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreBroadcastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.broadcasts.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:whatsapp,facebook,instagram,email,web'],
            'template_type' => ['required', 'in:text,hsm'],
            'body' => ['required_if:template_type,text', 'nullable', 'string', 'max:4096'],
            'template_id' => ['required_if:template_type,hsm', 'nullable', 'string'],
            'filters' => ['nullable', 'array'],
            'filters.tag' => ['nullable', 'string'],
            'filters.channel' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del broadcast es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'channel.required' => 'El canal es obligatorio.',
            'channel.in' => 'El canal seleccionado no es valido.',
            'template_type.required' => 'El tipo de contenido es obligatorio.',
            'template_type.in' => 'El tipo de contenido no es valido.',
            'body.required_if' => 'El cuerpo del mensaje es obligatorio cuando el tipo es texto libre.',
            'body.max' => 'El mensaje no puede superar los 4096 caracteres.',
            'template_id.required_if' => 'El ID del template HSM es obligatorio.',
            'scheduled_at.date' => 'La fecha programada no tiene un formato valido.',
            'scheduled_at.after' => 'La fecha programada debe ser posterior a la fecha y hora actual.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'channel' => 'canal',
            'template_type' => 'tipo de contenido',
            'body' => 'cuerpo del mensaje',
            'template_id' => 'ID del template',
            'filters' => 'filtros',
            'filters.tag' => 'etiqueta',
            'filters.channel' => 'canal de filtro',
            'scheduled_at' => 'fecha programada',
        ];
    }
}
