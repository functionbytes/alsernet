<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactOwnerCatalog;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                // Sin withoutTrashed(): el indice unico de helpdesk_customers.email
                // tambien choca contra filas soft-deleted, asi que la validacion
                // debe considerarlas tambien (antes un email duplicado con un
                // contacto borrado producia un 500 por UniqueConstraintViolationException).
                Rule::unique('helpdesk.helpdesk_customers', 'email')->ignore($this->route('customer')),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:60'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            // Columna is_vip de helpdesk_customers, NOT NULL (ver
            // Customer::isVip()/scopeVip()). Sin 'nullable': el JS del modal
            // Editar envía siempre 0 o 1 (un checkbox sin marcar no viaja en
            // un submit normal), y un null explícito debe rechazarse aquí con
            // 422 en vez de reventar el UPDATE con 500 por la constraint.
            'is_vip' => ['boolean'],
            // Responsable del contacto. Ausente = no se toca; null = quitar el
            // responsable. Solo agentes del catálogo (ContactOwnerCatalog),
            // salvo que sea el que ya tiene: si un agente pasa a "no disponible"
            // el modal Editar reenvía su id sin cambios y eso no debe impedir
            // guardar el resto de la ficha.
            'owner_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $customer = $this->route('customer');
                    $current = $customer instanceof Customer ? $customer->owner_id : null;

                    if ($current !== null && (int) $value === (int) $current) {
                        return;
                    }

                    if (! app(ContactOwnerCatalog::class)->isAssignable((int) $value)) {
                        $fail('El responsable seleccionado no es un agente asignable.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Ya existe otro contacto con este correo electrónico. Si son la misma persona, fusiona los contactos duplicados en vez de editar el email.',
            'phone.max' => 'El teléfono no puede superar los 50 caracteres.',
            'language.max' => 'El idioma no puede superar los 10 caracteres.',
            'timezone.max' => 'La zona horaria no puede superar los 60 caracteres.',
            'notes.max' => 'Las notas no pueden superar los 2000 caracteres.',
            'owner_id.integer' => 'El responsable seleccionado no es válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'language' => 'idioma',
            'timezone' => 'zona horaria',
            'internal_notes' => 'notas',
            'owner_id' => 'responsable',
        ];
    }
}
