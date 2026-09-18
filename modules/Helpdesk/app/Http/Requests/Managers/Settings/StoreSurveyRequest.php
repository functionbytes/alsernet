<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.surveys.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'in:conversation_closed,manual,csat_low,tag_added'],
            'trigger_config' => ['nullable', 'array'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:rating_1_5,rating_0_10,multi_choice,single_choice,open_text,boolean'],
            'questions.*.label' => ['required', 'string'],
            'questions.*.required' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'trigger_type.required' => 'El tipo de disparador es obligatorio.',
            'trigger_type.in' => 'El tipo de disparador seleccionado no es válido.',
            'questions.required' => 'Debes agregar al menos una pregunta.',
            'questions.min' => 'Debes agregar al menos una pregunta.',
            'questions.*.id.required' => 'El identificador de la pregunta es obligatorio.',
            'questions.*.type.required' => 'El tipo de la pregunta es obligatorio.',
            'questions.*.type.in' => 'El tipo de pregunta seleccionado no es válido.',
            'questions.*.label.required' => 'La etiqueta de la pregunta es obligatoria.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'trigger_type' => 'tipo de disparador',
            'trigger_config' => 'configuración del disparador',
            'questions' => 'preguntas',
            'questions.*.id' => 'identificador de la pregunta',
            'questions.*.type' => 'tipo de pregunta',
            'questions.*.label' => 'etiqueta de la pregunta',
            'questions.*.required' => 'obligatoria',
        ];
    }
}
