<?php

namespace Modules\HelpdeskEmailActivity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vincula manualmente un email sin entidad relacionada a un registro de otro
 * módulo (ver EmailLogController::linkEntity()/searchTickets()).
 */
class LinkEmailLogEntityRequest extends FormRequest
{
    /**
     * FQCN de entidades que este módulo sabe vincular desde el sidebar del
     * detalle. Lista corta y explícita a propósito: vincular a un tipo de
     * entidad nuevo requiere también un buscador propio en el backend (hoy
     * solo existe EmailLogController::searchTickets()), así que no tiene
     * sentido aceptar aquí un FQCN para el que no hay ningún buscador.
     * Mismo FQCN que ya usa config('helpdeskemailactivity.entity_routes').
     *
     * @var list<string>
     */
    private const ALLOWED_ENTITY_TYPES = [
        'Modules\\HelpdeskTickets\\Models\\Ticket',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemailactivity.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(self::ALLOWED_ENTITY_TYPES)],
            'entity_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
