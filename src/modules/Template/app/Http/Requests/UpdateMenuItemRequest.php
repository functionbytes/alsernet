<?php

namespace Modules\Template\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('menu.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank,_parent,_top',
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
            'css_class' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-_]*$/'],
            'type' => 'nullable|string|in:custom,page,post,category,route',
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
        ];
    }
}
