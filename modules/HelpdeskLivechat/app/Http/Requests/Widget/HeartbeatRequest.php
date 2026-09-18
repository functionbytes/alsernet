<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $token = (string) $this->header('X-Website-Token', '');

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
