<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailerSettingRequest extends FormRequest
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
            'from_email' => [
                'required',
                'email',
                'max:255',
            ],
            'from_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'reply_to' => [
                'nullable',
                'email',
                'max:255',
            ],
            'return_path' => [
                'nullable',
                'string',
                'max:255',
            ],
            'bounce_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'complaint_email' => [
                'nullable',
                'email',
                'max:255',
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
            'from_email.required' => 'El correo de envío es obligatorio.',
            'from_email.email' => 'El correo de envío debe ser una dirección de correo válida.',
            'from_email.max' => 'El correo de envío no debe exceder 255 caracteres.',
            'from_name.string' => 'El nombre debe ser una cadena de texto.',
            'from_name.max' => 'El nombre no debe exceder 255 caracteres.',
            'reply_to.email' => 'El correo de respuesta debe ser una dirección de correo válida.',
            'reply_to.max' => 'El correo de respuesta no debe exceder 255 caracteres.',
            'return_path.string' => 'La ruta de devolución debe ser una cadena de texto.',
            'return_path.max' => 'La ruta de devolución no debe exceder 255 caracteres.',
            'bounce_email.email' => 'El correo de rebote debe ser una dirección de correo válida.',
            'bounce_email.max' => 'El correo de rebote no debe exceder 255 caracteres.',
            'complaint_email.email' => 'El correo de reclamación debe ser una dirección de correo válida.',
            'complaint_email.max' => 'El correo de reclamación no debe exceder 255 caracteres.',
        ];
    }
}
