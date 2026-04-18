<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.settings.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sync_strategy' => ['required', Rule::in(['oauth', 'places_api', 'serpapi', 'manual'])],
            'place_id' => [
                'nullable',
                'string',
                'max:255',
                'required_if:sync_strategy,places_api',
                'required_if:sync_strategy,serpapi',
            ],
            'connection_id' => [
                'nullable',
                'integer',
                'exists:review_google_connections,id',
                'required_if:sync_strategy,oauth',
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la ubicación es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'sync_strategy.required' => 'La estrategia de sincronización es obligatoria.',
            'sync_strategy.in' => 'La estrategia de sincronización seleccionada no es válida.',
            'place_id.required_if' => 'El Place ID es obligatorio para la estrategia seleccionada.',
            'place_id.max' => 'El Place ID no puede superar los 255 caracteres.',
            'connection_id.required_if' => 'Debe seleccionar una conexión OAuth para esta estrategia.',
            'connection_id.exists' => 'La conexión seleccionada no existe.',
            'address.max' => 'La dirección no puede superar los 500 caracteres.',
            'phone.max' => 'El teléfono no puede superar los 50 caracteres.',
            'website_url.url' => 'La URL del sitio web no tiene un formato válido.',
            'website_url.max' => 'La URL no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'sync_strategy' => 'estrategia de sincronización',
            'place_id' => 'Place ID',
            'connection_id' => 'conexión',
            'address' => 'dirección',
            'phone' => 'teléfono',
            'website_url' => 'URL del sitio web',
        ];
    }
}
