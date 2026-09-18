<?php

namespace Modules\GiftMessage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadGiftMessageImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.update');
    }

    public function rules(): array
    {
        return [
            'envelope_image' => ['nullable', 'file', 'image', 'max:5120'],
            'card_image' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'envelope_image.image' => 'La imagen del sobre debe ser un archivo de imagen valido.',
            'envelope_image.max' => 'La imagen del sobre no puede superar los 5 MB.',
            'card_image.image' => 'La imagen de la tarjeta debe ser un archivo de imagen valido.',
            'card_image.max' => 'La imagen de la tarjeta no puede superar los 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'envelope_image' => 'imagen del sobre',
            'card_image' => 'imagen de la tarjeta',
        ];
    }
}
