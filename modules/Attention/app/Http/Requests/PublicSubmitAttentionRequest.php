<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Captcha\Facades\Captcha;

/**
 * Request validation for public peticiones submission (external form with captcha)
 */
class PublicSubmitAttentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'type_id' => [
                'required',
                'integer',
                Rule::exists('attention_types', 'id')->where('is_active', true),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('attention_categories', 'id')->where('is_active', true),
            ],
            'subject' => ['required', 'string', 'max:255', 'min:10'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'sede_id' => [
                'nullable',
                'integer',
                Rule::exists('attention_sedes', 'id')->where('is_active', true),
            ],
            'is_anonymous' => ['sometimes', 'boolean'],
            'customer_firstname' => ['required_if:is_anonymous,false', 'nullable', 'string', 'max:100', 'min:2'],
            'customer_lastname' => ['required_if:is_anonymous,false', 'nullable', 'string', 'max:100', 'min:2'],
            'customer_email' => ['required_if:is_anonymous,false', 'nullable', 'email', 'max:150'],
            'customer_cellphone' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'customer_dni' => ['nullable', 'string', 'max:20'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt,zip,rar'],
            'response_type' => ['nullable', 'string', 'in:email,presencial,correo_fisico,telefono,no_requiere'],
        ];

        if (config('attention.captcha.enabled', false)) {
            $rules = array_merge($rules, Captcha::rules());
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type_id.required' => 'Debe seleccionar el tipo de solicitud (Peticion, Queja, Reclamo, etc.)',
            'type_id.exists' => 'El tipo de solicitud seleccionado no es valido',
            'category_id.required' => 'Debe seleccionar una categoria para su solicitud',
            'category_id.exists' => 'La categoria seleccionada no es valida',
            'subject.required' => 'El asunto es obligatorio',
            'subject.min' => 'El asunto debe tener al menos :min caracteres',
            'subject.max' => 'El asunto no puede exceder :max caracteres',
            'description.required' => 'La descripcion es obligatoria',
            'description.min' => 'La descripcion debe tener al menos :min caracteres',
            'description.max' => 'La descripcion no puede exceder :max caracteres',
            'customer_firstname.required_if' => 'El nombre es obligatorio para solicitudes no anonimas',
            'customer_firstname.min' => 'El nombre debe tener al menos :min caracteres',
            'customer_lastname.required_if' => 'El apellido es obligatorio para solicitudes no anonimas',
            'customer_lastname.min' => 'El apellido debe tener al menos :min caracteres',
            'customer_email.required_if' => 'El correo electronico es obligatorio para solicitudes no anonimas',
            'customer_email.email' => 'Debe proporcionar un correo electronico valido',
            'customer_cellphone.regex' => 'El numero de celular debe tener 10 digitos',
            'attachments.max' => 'No puede adjuntar mas de :max archivos',
            'attachments.*.max' => 'Cada archivo no puede exceder 10MB',
            'attachments.*.mimes' => 'Solo se permiten archivos PDF, Word, imagenes, texto y comprimidos',
            'sede_id.exists' => 'La sede seleccionada no es valida',
            'g-recaptcha-response.required' => 'Debe completar la verificacion de captcha',
            'g-recaptcha-response.captcha' => 'La verificacion de captcha fallo. Intentelo nuevamente.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type_id' => 'tipo de solicitud',
            'category_id' => 'categoria',
            'sede_id' => 'sede',
            'subject' => 'asunto',
            'description' => 'descripcion',
            'customer_firstname' => 'nombre',
            'customer_lastname' => 'apellido',
            'customer_email' => 'correo electronico',
            'customer_cellphone' => 'celular',
            'customer_dni' => 'documento de identidad',
            'customer_address' => 'direccion',
            'attachments' => 'archivos adjuntos',
            'response_type' => 'tipo de respuesta',
            'g-recaptcha-response' => 'captcha',
        ];
    }
}
