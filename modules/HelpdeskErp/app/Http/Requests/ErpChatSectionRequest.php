<?php

namespace Modules\HelpdeskErp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Paginación y filtros de una sección de Gestión en el chat. La autorización
 * (permiso + alcance del cliente) la hace ErpChatController; aquí solo se
 * validan los parámetros que llegan del navegador antes de reenviarlos.
 */
class ErpChatSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.*' => 'El número de resultados debe estar entre 1 y 100.',
            'offset.*' => 'El desplazamiento no es válido.',
            'status.*' => 'El estado no es válido.',
            'year.*' => 'El año no es válido.',
            'from.*' => 'La fecha inicial no es válida (AAAA-MM-DD).',
            'to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'to.*' => 'La fecha final no es válida (AAAA-MM-DD).',
        ];
    }

    /**
     * @return array<string, scalar>
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->except('force'),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    public function fresh(): bool
    {
        return $this->boolean('force');
    }
}
