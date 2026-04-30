<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Profile;

use App\Http\Api\V1\BaseApiRequest;

class UploadAvatarRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes adjuntar una imagen.',
            'file.image' => 'El archivo debe ser una imagen.',
            'file.mimes' => 'Formatos permitidos: jpg, jpeg, png, webp.',
            'file.max' => 'El archivo no puede superar 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'avatar' => ['description' => 'Imagen de perfil (JPG/PNG/GIF, máx 2MB).', 'example' => '(binary file)'],
        ];
    }
}
