<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkTicketMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real es por email en el controlador (igual que
        // BulkTicketsController): los que el usuario no puede actuar se omiten.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'mail_ids' => ['required', 'array', 'min:1', 'max:100'],
            'mail_ids.*' => ['integer'],
            'action' => ['required', 'string', 'in:resend,cancel_scheduled'],
        ];
    }

    public function messages(): array
    {
        return [
            'mail_ids.required' => 'Los emails son obligatorios.',
            'mail_ids.min' => 'Debe seleccionar al menos un email.',
            'mail_ids.max' => 'No puede seleccionar más de 100 emails.',
            'action.in' => 'La acción seleccionada no es válida.',
        ];
    }
}
