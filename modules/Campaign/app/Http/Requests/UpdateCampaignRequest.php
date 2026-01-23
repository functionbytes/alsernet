<?php

namespace Modules\Campaign\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
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
        $campaignId = $this->route('campaign')->id ?? null;

        return [
            'name' => 'sometimes|required|string|max:255|unique:helpdesk.helpdesk_campaigns,name,'.$campaignId,
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|required|in:popup,banner,slide-in,full-screen',
            'status' => 'sometimes|required|in:draft,scheduled,active,ended,paused',

            // Content blocks
            'content' => 'nullable|array',
            'content.*.type' => 'required|in:text,heading,button,image,html',
            'content.*.value' => 'required_if:content.*.type,text,heading',
            'content.*.label' => 'required_if:content.*.type,button',
            'content.*.url' => 'required_if:content.*.type,button,image|url',

            // Appearance backups
            'appearance' => 'nullable|array',
            'appearance.background_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'appearance.text_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'appearance.primary_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'appearance.font_size' => 'nullable|in:small,medium,large,xlarge',
            'appearance.position' => 'nullable|in:top-left,top-center,top-right,center,bottom-left,bottom-center,bottom-right',
            'appearance.max_width' => 'nullable|integer|min:300|max:1200',
            'appearance.border_radius' => 'nullable|integer|min:0|max:50',
            'appearance.padding' => 'nullable|integer|min:0|max:50',

            // Conditions
            'conditions' => 'nullable|array',
            'conditions.*.field' => 'required_with:conditions|string',
            'conditions.*.operator' => 'required_with:conditions|in:equals,not_equals,contains,not_contains,greater_than,less_than,starts_with,ends_with',
            'conditions.*.value' => 'required_with:conditions',

            // Metadata
            'metadata' => 'nullable|array',

            // Dates
            'published_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:published_at',
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
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'name.unique' => 'Ya existe otra campaña con este nombre.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',

            'type.required' => 'Debe seleccionar un tipo de campaña.',
            'type.in' => 'El tipo de campaña seleccionado no es válido.',
            'status.required' => 'Debe seleccionar un estado para la campaña.',
            'status.in' => 'El estado seleccionado no es válido.',

            // Content validation messages
            'content.array' => 'El contenido debe ser un arreglo válido.',
            'content.*.type.required' => 'Cada bloque de contenido debe tener un tipo.',
            'content.*.type.in' => 'El tipo de bloque no es válido.',
            'content.*.value.required_if' => 'Este campo es obligatorio para bloques de texto.',
            'content.*.label.required_if' => 'El botón debe tener un texto.',
            'content.*.url.required_if' => 'Debe proporcionar una URL válida.',
            'content.*.url.url' => 'La URL proporcionada no es válida.',

            // Appearance validation messages
            'appearance.background_color.regex' => 'El color de fondo debe ser un código hexadecimal válido (ej: #FFFFFF).',
            'appearance.text_color.regex' => 'El color de texto debe ser un código hexadecimal válido.',
            'appearance.primary_color.regex' => 'El color primario debe ser un código hexadecimal válido.',
            'appearance.font_size.in' => 'El tamaño de fuente seleccionado no es válido.',
            'appearance.position.in' => 'La posición seleccionada no es válida.',
            'appearance.max_width.min' => 'El ancho mínimo es 300px.',
            'appearance.max_width.max' => 'El ancho máximo es 1200px.',
            'appearance.border_radius.min' => 'El radio de borde mínimo es 0.',
            'appearance.border_radius.max' => 'El radio de borde máximo es 50.',

            // Conditions validation messages
            'conditions.array' => 'Las condiciones deben ser un arreglo válido.',
            'conditions.*.field.required_with' => 'Debe seleccionar un campo para la condición.',
            'conditions.*.operator.required_with' => 'Debe seleccionar un operador.',
            'conditions.*.operator.in' => 'El operador seleccionado no es válido.',
            'conditions.*.value.required_with' => 'Debe proporcionar un valor para la condición.',

            // Date validation messages
            'published_at.date' => 'La fecha de publicación debe ser una fecha válida.',
            'ends_at.date' => 'La fecha de finalización debe ser una fecha válida.',
            'ends_at.after' => 'La fecha de finalización debe ser posterior a la fecha de publicación.',
        ];
    }
}
