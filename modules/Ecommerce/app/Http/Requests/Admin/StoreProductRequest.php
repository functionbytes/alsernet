<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:ecommerce_products,slug'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,draft,pending'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:ecommerce_products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'brand_id' => ['nullable', 'exists:ecommerce_brands,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:ecommerce_product_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:ecommerce_product_tags,id'],
            'collections' => ['nullable', 'array'],
            'collections.*' => ['exists:ecommerce_product_collections,id'],
            'images' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'wide' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'maximum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'allow_checkout_when_out_of_stock' => ['nullable', 'boolean'],
            'with_storehouse_management' => ['nullable', 'boolean'],
            'is_variation' => ['nullable', 'boolean'],
            'sale_type' => ['nullable', 'string', 'in:default,percentage,fixed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'stock_status' => ['nullable', 'string', 'in:in_stock,out_of_stock,backorder'],
            'order' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'description' => 'descripcion',
            'content' => 'contenido',
            'status' => 'estado',
            'sku' => 'SKU',
            'price' => 'precio',
            'sale_price' => 'precio de oferta',
            'quantity' => 'cantidad',
            'brand_id' => 'marca',
            'categories' => 'categorias',
            'images' => 'imagenes',
            'featured_image' => 'imagen destacada',
            'weight' => 'peso',
            'length' => 'largo',
            'wide' => 'ancho',
            'height' => 'alto',
            'minimum_order_quantity' => 'cantidad minima de orden',
            'maximum_order_quantity' => 'cantidad maxima de orden',
            'allow_checkout_when_out_of_stock' => 'permitir checkout sin stock',
            'with_storehouse_management' => 'gestionar almacen',
            'is_variation' => 'es variacion',
            'sale_type' => 'tipo de oferta',
            'start_date' => 'fecha inicio oferta',
            'end_date' => 'fecha fin oferta',
            'cost_per_item' => 'costo por item',
            'barcode' => 'codigo de barras',
            'stock_status' => 'estado de stock',
            'order' => 'orden',
        ];
    }
}
