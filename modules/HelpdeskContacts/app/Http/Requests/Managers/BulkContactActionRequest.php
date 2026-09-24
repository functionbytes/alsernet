<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskContacts\Services\ContactOwnerCatalog;

class BulkContactActionRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:ban,unban,delete,tag,assign'],
            'ids' => ['required', 'array', 'min:1'],
            // customers live on the 'helpdesk' connection (see ExecuteMergeRequest).
            'ids.*' => ['integer', 'exists:helpdesk.helpdesk_customers,id'],
            // action=tag: nombre de la etiqueta que se AÑADE (find-or-create; no
            // quita las que el contacto ya tenga).
            'tag' => ['required_if:action,tag', 'nullable', 'string', 'max:60'],
            // action=assign: id del agente responsable. Tiene que venir presente
            // (null = dejar sin responsable) y ser un agente del catálogo.
            'owner_id' => [
                'present_if:action,assign',
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->input('action') !== 'assign') {
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
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción debe ser ban, unban, delete, tag o assign.',
            'ids.required' => 'Debes seleccionar al menos un contacto.',
            'ids.min' => 'Debes seleccionar al menos un contacto.',
            'ids.*.exists' => 'Uno o más contactos seleccionados no existen.',
            'tag.required_if' => 'Indica la etiqueta que quieres añadir.',
            'tag.max' => 'La etiqueta no puede superar los 60 caracteres.',
            'owner_id.present_if' => 'Indica el responsable (o déjalo vacío para quitarlo).',
            'owner_id.integer' => 'El responsable seleccionado no es válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'contactos',
            'tag' => 'etiqueta',
            'owner_id' => 'responsable',
        ];
    }
}
