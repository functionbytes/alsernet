<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $note = $this->route('note');

        // Only the note author or admins can edit notes
        return $this->user()->id === $note->user_id ||
               $this->user()->hasPermissionTo('edit all ticket notes');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'body' => 'sometimes|string|max:5000',
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
            'title' => 'título',
            'body' => 'contenido de nota',
            'color' => 'color',
            'is_pinned' => 'estado de fijación',
        ];
    }
}
