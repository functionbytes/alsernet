<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAiReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.reviews.view');
    }

    public function rules(): array
    {
        return [
            'tone' => ['nullable', 'string', 'in:professional,friendly,apologetic,grateful,concise'],
        ];
    }

    public function messages(): array
    {
        return [
            'tone.in' => 'El tono seleccionado no es válido.',
        ];
    }
}
