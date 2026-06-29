<?php

namespace Modules\Engagement\Http\Requests\Sdk;

use Illuminate\Foundation\Http\FormRequest;

class InitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitorId' => ['nullable', 'string', 'max:64'],
            'fingerprint' => ['nullable', 'string', 'max:32'],
            'page' => ['nullable', 'array'],
            'page.url' => ['nullable', 'string', 'max:2048'],
            'page.title' => ['nullable', 'string', 'max:512'],
            'page.referrer' => ['nullable', 'string', 'max:2048'],
            'device' => ['nullable', 'array'],
            'device.userAgent' => ['nullable', 'string', 'max:512'],
            'device.language' => ['nullable', 'string', 'max:20'],
            'device.timezone' => ['nullable', 'string', 'max:64'],
            'device.viewport' => ['nullable', 'array'],
            'device.viewport.width' => ['nullable', 'integer'],
            'device.viewport.height' => ['nullable', 'integer'],
        ];
    }
}
