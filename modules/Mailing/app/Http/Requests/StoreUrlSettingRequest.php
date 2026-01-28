<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUrlSettingRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_url' => [
                'required',
                'url',
                'max:500',
            ],
            'webhook_url' => [
                'required',
                'url',
                'max:500',
            ],
            'tracking_url' => [
                'required',
                'url',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'app_url.required' => 'La URL de la aplicación es obligatoria.',
            'app_url.url' => 'La URL de la aplicación debe ser una URL válida.',
            'app_url.max' => 'La URL de la aplicación no debe exceder 500 caracteres.',
            'webhook_url.required' => 'La URL del webhook es obligatoria.',
            'webhook_url.url' => 'La URL del webhook debe ser una URL válida.',
            'webhook_url.max' => 'La URL del webhook no debe exceder 500 caracteres.',
            'tracking_url.required' => 'La URL de seguimiento es obligatoria.',
            'tracking_url.url' => 'La URL de seguimiento debe ser una URL válida.',
            'tracking_url.max' => 'La URL de seguimiento no debe exceder 500 caracteres.',
        ];
    }
}
