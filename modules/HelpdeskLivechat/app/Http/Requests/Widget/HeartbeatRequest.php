<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cabecera o body: el widget envía ambos (el body sirve también a
        // clientes que no pueden poner cabeceras).
        $token = (string) ($this->header('X-Website-Token') ?: $this->input('website_token', ''));

        if ($token === '') {
            return false;
        }

        return Cache::remember(
            'helpdesklivechat:web:token:'.$token.':exists',
            now()->addMinutes(5),
            fn (): bool => Web::query()->where('website_token', $token)->exists()
        );
    }

    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string', 'max:64'],
            'url' => ['required', 'url'],
            'title' => ['nullable', 'string', 'max:255'],
            // Producto que el visitante está viendo (opcional). Reportado por el
            // snippet en la ficha de producto para la covisualización.
            'product' => ['nullable', 'array'],
            'product.id' => ['required_with:product', 'string', 'max:64'],
            'product.title' => ['nullable', 'string', 'max:255'],
            'product.image_url' => ['nullable', 'string', 'max:1000'],
            'product.url' => ['nullable', 'string', 'max:1000'],
            'product.price' => ['nullable', 'numeric'],
            'product.currency' => ['nullable', 'string', 'max:8'],
            'product.id_product_attribute' => ['nullable', 'integer', 'min:0'],
            // Cesta en vivo leída de la tienda (null = el visitante no tiene cesta).
            'cart' => ['nullable', 'array'],
            'cart.id' => ['required_with:cart', 'integer', 'min:1'],
            'cart.products_count' => ['nullable', 'integer', 'min:0'],
            'cart.total' => ['nullable', 'numeric'],
            'cart.total_products' => ['nullable', 'numeric'],
            'cart.currency' => ['nullable', 'string', 'max:8'],
            'cart.customer_logged' => ['nullable', 'boolean'],
            // Token de la tienda para editar la cesta de invitado (se guarda aparte).
            'cart.token' => ['nullable', 'string', 'max:512'],
            'cart.lines' => ['nullable', 'array', 'max:50'],
            'cart.lines.*.id_product' => ['required', 'integer', 'min:1'],
            'cart.lines.*.id_product_attribute' => ['nullable', 'integer', 'min:0'],
            'cart.lines.*.name' => ['nullable', 'string', 'max:255'],
            'cart.lines.*.attributes' => ['nullable', 'string', 'max:255'],
            'cart.lines.*.reference' => ['nullable', 'string', 'max:64'],
            'cart.lines.*.qty' => ['required', 'integer', 'min:0'],
            'cart.lines.*.price' => ['nullable', 'numeric'],
            'cart.lines.*.total' => ['nullable', 'numeric'],
            'cart.lines.*.image_url' => ['nullable', 'string', 'max:1000'],
            'cart.lines.*.url' => ['nullable', 'string', 'max:1000'],
            // Productos vistos recientemente (lista local del widget).
            'viewed_products' => ['nullable', 'array', 'max:20'],
            'viewed_products.*.id' => ['required', 'string', 'max:64'],
            'viewed_products.*.title' => ['nullable', 'string', 'max:255'],
            'viewed_products.*.image_url' => ['nullable', 'string', 'max:1000'],
            'viewed_products.*.url' => ['nullable', 'string', 'max:1000'],
            'viewed_products.*.price' => ['nullable', 'numeric'],
            'viewed_products.*.currency' => ['nullable', 'string', 'max:8'],
            'viewed_products.*.viewed_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_token.required' => 'El token de sesión es obligatorio.',
            'session_token.max' => 'El token de sesión no puede superar 64 caracteres.',
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'La URL no tiene un formato válido.',
            'title.max' => 'El título no puede superar 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'session_token' => 'token de sesión',
            'url' => 'URL',
            'title' => 'título',
        ];
    }
}
