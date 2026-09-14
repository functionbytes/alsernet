<?php

namespace Modules\HelpdeskTickets\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWidgetTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'website_token' => ['required', 'string', Rule::exists('helpdesk.helpdesk_channel_webs', 'website_token')],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'customer_email.required' => 'El correo electrónico es obligatorio.',
            'customer_email.email' => 'Introduce un correo electrónico válido.',
            'website_token.required' => 'Token de widget requerido.',
            'website_token.exists' => 'Token de widget no válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripción',
            'category_id' => 'categoría',
            'priority' => 'prioridad',
            'customer_email' => 'correo electrónico',
            'customer_name' => 'nombre',
            'attachments' => 'archivos adjuntos',
        ];
    }
}
