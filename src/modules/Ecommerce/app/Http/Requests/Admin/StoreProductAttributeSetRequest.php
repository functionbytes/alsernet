<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductAttributeSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.product-attribute-sets.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:ecommerce_product_attribute_sets,slug'],
            'display_layout' => ['required', 'in:swatch_dropdown,text,color,image'],
            'is_searchable' => ['boolean'],
            'is_comparable' => ['boolean'],
            'is_use_in_product_listing' => ['boolean'],
            'status' => ['required', 'in:published,draft'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'slug.max' => 'El slug no puede superar los 120 caracteres.',
            'slug.unique' => 'Este slug ya está en uso.',
            'display_layout.required' => 'El diseño de visualización es obligatorio.',
            'display_layout.in' => 'El diseño de visualización no es válido.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser publicado o borrador.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden no puede ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'slug' => 'slug',
            'display_layout' => 'diseño de visualización',
            'is_searchable' => 'buscable',
            'is_comparable' => 'comparable',
            'is_use_in_product_listing' => 'usar en listado de productos',
            'status' => 'estado',
            'order' => 'orden',
        ];
    }
}
