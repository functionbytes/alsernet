<?php

namespace Modules\Template\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|exists:menu_items,id',
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank,_parent,_top',
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
            'css_class' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
            'type' => 'required|string|in:custom,page,post,category,route',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'item padre',
            'title' => 'título',
            'url' => 'URL',
            'target' => 'destino del enlace',
            'icon' => 'clase de icono',
            'css_class' => 'clase CSS',
            'type' => 'tipo de item',
            'reference_id' => 'ID de referencia',
            'reference_type' => 'tipo de referencia',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título del item es obligatorio.',
            'title.max' => 'El título no puede superar los :max caracteres.',
            'url.url' => 'La URL no tiene un formato válido.',
            'target.in' => 'El destino de apertura no es válido.',
            'type.required' => 'El tipo de item es obligatorio.',
            'type.in' => 'El tipo de item seleccionado no es válido.',
        ];
    }
}
