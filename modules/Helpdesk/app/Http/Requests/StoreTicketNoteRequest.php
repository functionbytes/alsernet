<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        // User must be able to update the ticket
        return $this->user()->can('update', $ticket);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => 'required|exists:helpdesk_tickets,id',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
            'color' => 'nullable|in:yellow,blue,green,red,purple,orange',
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ticket_id.required' => 'El ticket es obligatorio',
            'ticket_id.exists' => 'El ticket especificado no existe',
            'body.required' => 'La nota no puede estar vacía',
            'body.max' => 'La nota no puede exceder 5,000 caracteres',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'color.in' => 'El color debe ser uno de: amarillo, azul, verde, rojo, púrpura, naranja',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'ticket_id' => 'ticket',
            'title' => 'título',
            'body' => 'contenido de nota',
            'color' => 'color',
            'is_pinned' => 'estado de fijación',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default to unpinned if not specified
        if (! $this->has('is_pinned')) {
            $this->merge(['is_pinned' => false]);
        }

        // Default to yellow if color not specified
        if (! $this->has('color')) {
            $this->merge(['color' => 'yellow']);
        }
    }
}
