<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('customer')) ?? false;
    }

    /**
     * Sin esto un email vacío se validaba (y guardaba) como '' en vez de
     * NULL: el índice único de helpdesk_customers.email no distingue
     * cadenas vacías repetidas como sí hace con NULL, así que el segundo
     * contacto sin email chocaba con "ya existe un cliente con ese correo".
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? $this->string('email')->trim()->toString() : null,
        ]);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            // 22-sep-2026: muchos contactos son solo-WhatsApp y nunca dieron
            // email — 'required' bloqueaba guardar la ficha aunque el resto
            // de los datos fueran correctos. helpdesk_customers.email ya es
            // nullable desde 2026_07_28_142647_make_email_nullable... (se
            // relajó para permitir crear estos contactos desde "Nueva
            // conversación"), pero esta validación se había quedado atrás.
            'email' => ['nullable', 'email', Rule::unique('helpdesk.helpdesk_customers', 'email')->ignore($customer?->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\+\d][\d\s\-\(\)\.]{3,}$/'],
            'country' => ['nullable', 'string', 'max:2'],
            'language' => ['nullable', 'string', 'max:5'],
            'timezone' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe un cliente con ese correo electrónico.',
            'phone.max' => 'El teléfono no puede superar los 30 caracteres.',
            'phone.regex' => 'El teléfono solo puede contener dígitos, espacios, +, -, ( ) y puntos.',
            'country.max' => 'El código de país debe tener máximo 2 caracteres.',
            'language.max' => 'El código de idioma debe tener máximo 5 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'country' => 'país',
            'language' => 'idioma',
            'timezone' => 'zona horaria',
            'internal_notes' => 'notas internas',
        ];
    }
}
