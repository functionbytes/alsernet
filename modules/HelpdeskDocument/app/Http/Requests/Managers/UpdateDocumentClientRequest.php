<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * El endpoint delegado (DocumentsController::update) aplica `data` como
     * mass-assignment sobre cualquier columna fillable del expediente; aquí
     * se restringe a los únicos campos que edita el panel del inbox (datos
     * de contacto del cliente) para que un payload manipulado no pueda tocar
     * status_id, order_reference u otras columnas internas.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array:customer_firstname,customer_lastname,customer_dni,customer_email,customer_cellphone,customer_company'],
            'data.customer_firstname' => ['nullable', 'string', 'max:255'],
            'data.customer_lastname' => ['nullable', 'string', 'max:255'],
            'data.customer_dni' => ['nullable', 'string', 'max:32'],
            'data.customer_email' => ['nullable', 'email', 'max:255'],
            'data.customer_cellphone' => ['nullable', 'string', 'max:32'],
            'data.customer_company' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data.required' => 'No hay datos que guardar.',
            'data.array' => 'Solo se pueden actualizar los datos de contacto del cliente.',
            'data.customer_email.email' => 'El correo electrónico no es válido.',
            'data.customer_firstname.max' => 'El nombre no puede superar los 255 caracteres.',
            'data.customer_lastname.max' => 'Los apellidos no pueden superar los 255 caracteres.',
            'data.customer_dni.max' => 'El DNI/NIE/CIF no puede superar los 32 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'data.customer_firstname' => 'nombre',
            'data.customer_lastname' => 'apellidos',
            'data.customer_dni' => 'DNI/NIE/CIF',
            'data.customer_email' => 'correo electrónico',
            'data.customer_cellphone' => 'teléfono',
            'data.customer_company' => 'empresa',
        ];
    }
}
