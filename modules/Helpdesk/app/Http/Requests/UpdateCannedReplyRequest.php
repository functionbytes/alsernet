<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCannedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.canned-replies.update');
    }

    public function rules(): array
    {
        $cannedReplyId = $this->route('canned_reply')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'shortcut' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('helpdesk.helpdesk_canned_replies', 'shortcut')
                    ->ignore($cannedReplyId)
                    ->whereNull('deleted_at'),
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string'],
            'is_global' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El titulo es obligatorio.',
            'title.max' => 'El titulo no puede superar los 255 caracteres.',
            'shortcut.max' => 'El atajo no puede superar los 50 caracteres.',
            'shortcut.unique' => 'Este atajo ya esta en uso. Elige uno diferente.',
            'category.max' => 'La categoria no puede superar los 100 caracteres.',
            'body.required' => 'El cuerpo de la respuesta es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titulo',
            'shortcut' => 'atajo',
            'category' => 'categoria',
            'body' => 'cuerpo',
            'is_global' => 'alcance global',
        ];
    }
}
