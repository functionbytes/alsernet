<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitIndexNowUrlsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.indexnow.submit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'urls' => ['required', 'string', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'urls.required' => 'Debes indicar al menos una URL (una por línea).',
        ];
    }
}
