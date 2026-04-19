<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateRobotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'string'],
            'robots' => ['required', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos un registro.',
            'robots.required' => 'La directiva robots es obligatoria.',
            'robots.in' => 'La directiva robots no es válida.',
        ];
    }
}
